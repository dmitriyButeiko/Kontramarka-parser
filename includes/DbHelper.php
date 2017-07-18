<?php

$currentWorkedDirectory = getcwd();


/*
require_once $currentWorkedDirectory . "/httpdocs/parser/config/DbConfig.php";
require_once $currentWorkedDirectory . "/httpdocs/parser/config/ParserConfig.php";*/

/*require_once "config/DbConfig.php";
require_once "config/ParserConfig.php";*/

require_once $currentWorkedDirectory . "/config/DbConfig.php";
require_once $currentWorkedDirectory . "/config/ParserConfig.php";



class DbHelper
{
	private $db;
	private $parser;
	private $dbConfig;
	private $siteId;
	private $geocoder;
	public function __construct($parser)
	{
		global $dbConfig;
		
		
			
		$curl     = new \Ivory\HttpAdapter\CurlHttpAdapter();
        $geocoder = new \Geocoder\Provider\GoogleMaps($curl);
		$this->geocoder = $geocoder;
		
		$this->parser = $parser;
		$this->dbConfig = $dbConfig;
		$this->db = new mysqli($dbConfig["db_host"], $dbConfig["db_user"], $dbConfig["db_password"], $dbConfig["db_name"]);
		
		/* Проверка соединения */
		if (mysqli_connect_errno()) {
			printf("Подключение не удалось: %s\n", mysqli_connect_error());
			exit();
		}

		$this->db->query("SET NAMES utf8");
	}
	
	public function createInitialTables()
    {
		$this->db->query("CREATE TABLE IF NOT EXISTS sites (id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, last_parse_timestamp BIGINT(12), site_url TEXT, events_url TEXT, sitename VARCHAR(255), creator VARCHAR(20), e_mail VARCHAR(50)) DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci ENGINE=InnoDB");
		if($this->db->errno) print_r($this->db->error.'<br>');
		$this->db->query("CREATE TABLE IF NOT EXISTS events_inf (id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, a_title TEXT, a_descr TEXT) DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci ENGINE=InnoDB");
		if($this->db->errno) print_r($this->db->error.'<br>');
		$this->db->query("CREATE TABLE IF NOT EXISTS events_pic (id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, a_pic TEXT) DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci ENGINE=InnoDB");
		if($this->db->errno) print_r($this->db->error.'<br>');
		$this->db->query("CREATE TABLE IF NOT EXISTS places (id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, place_descr TEXT, zip VARCHAR(10), country VARCHAR(255), region VARCHAR(255), city VARCHAR(255), address TEXT, latitude DOUBLE, longitude DOUBLE) DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci ENGINE=InnoDB");
		if($this->db->errno) print_r($this->db->error.'<br>');
		$this->db->query("CREATE TABLE IF NOT EXISTS events (id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, site_id BIGINT(20), inf_id BIGINT(20), category_ids TEXT, pic_ids TEXT, place_id BIGINT(20), date_start BIGINT(12), date_end BIGINT(12), time VARCHAR(255), url VARCHAR(255), checked_status INT(1), checked_at BIGINT(12)) DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci ENGINE=InnoDB");
		if($this->db->errno) print_r($this->db->error.'<br>');
		$this->db->query("CREATE TABLE IF NOT EXISTS categories (id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, category TEXT) DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci ENGINE=InnoDB");
		$result = $this->db->query("SELECT * FROM categories LIMIT 1");
		if($result->num_rows < 1) $this->db->query("INSERT INTO `categories` (`id`, `category`) VALUES
(1, 'Аукционы'),
(2, 'Встречи'),
(3, 'Выставки'),
(4, 'Дети'),
(5, 'Дискотеки'),
(6, 'Другое'),
(7, 'Кино'),
(8, 'Конференции'),
(9, 'Концерты'),
(10, 'Праздники'),
(11, 'Презентации'),
(12, 'Прогулки'),
(13, 'Распродажи'),
(14, 'Семинары'),
(15, 'Собрания'),
(16, 'Спорт'),
(17, 'Танцы'),
(18, 'Театр'),
(19, 'Цирк'),
(20, 'Экскурсии'),
(21, 'Юбилеи');
");	
		if($this->db->errno) print_r($this->db->error.'<br>');if($this->db->errno) print_r($this->db->error.'<br>');
    }
	
	public function updateTables()
	{	
		$startMemory = memory_get_usage();
	
		$this->createInitialTables();
	
		global $parserConfig;
		
	
		$siteUrl = $this->parser->getSiteUrl();
		$siteTableRowInfo = array(
			"last_parse_timestamp" => time(),
			"site_url" => $siteUrl
		);
				
		
		$this->siteId = $this->updateSitesTable($siteTableRowInfo);	
	
		$categoriesList = $this->parser->getCategoriesList();
		
		var_dump($categoriesList);

		$defaultCategories = $this->getDefaultCategories();

		var_dump($categoriesList);
		
		/*$currentMemory = memory_get_usage() - $startMemory;*/
	
		$events = array();
		$counter = 0;
		
		$categoriesEventsUrls = $this->parser->getEventsUrlsByCategoriesUrls($categoriesList, $parserConfig["numberOfThreads"]);
		
		foreach($categoriesEventsUrls as $singleCategoryEventsUrls)
		{
			$events[$counter] = array();
			$events[$counter]["categoryUrl"] = $singleCategoryEventsUrls["url"]; 
			$events[$counter]["events"] = $this->parser->getSeveralEventsFromArray($singleCategoryEventsUrls["eventsUrls"],  $parserConfig["numberOfThreads"]);	
			$counter++;
		}	


		$newEvents = $this->defineNewEvents($events);

		$counter = 0;
		foreach($newEvents as $singleEventCategory)
		{
			$categoryUrl = $singleEventCategory["categoryUrl"];
			
			foreach($categoriesList as $singleCategoryFromList)
			{
				if($singleCategoryFromList["url"] == $categoryUrl)
				{
					$newEvents[$counter]["categoryName"] = $singleCategoryFromList["name"];
				}
			}
			
			$counter++;
		}
		$counter = 0;
		
		foreach($newEvents as $singleNewEventCategory)
		{  
			$newEventCategoryName = $singleNewEventCategory["categoryName"];
					
			$actualCategory = ""; 
			
			if($newEventCategoryName == "Концерты" || $newEventCategoryName == "Шоу и мюзиклы")
			{
				$actualCategory = "Концерты";
			}
			
			if($newEventCategoryName == "Детям")
			{
				$actualCategory = "Дети";
			}		

			if($newEventCategoryName == "Юмор")
			{
				$actualCategory = "Концерты";
			}
			
			if($newEventCategoryName == "Театры")
			{
				$actualCategory = "Театр";
			}
		
			
			foreach($defaultCategories as $singleDefaultCategory)
			{	
				if($actualCategory == $singleDefaultCategory["category"])
				{
					$newEvents[$counter]["categoryId"] = $singleDefaultCategory["id"];
					$newEvents[$counter]["categoryName"] = $singleDefaultCategory["category"];
					break;
				}
			}
			
			$counter++;
		}
		
		var_dump($newEvents);

		/*foreach($categoriesList as $singleCategory)
		{
			for($i = 0;$i < count($newEvents);$i++)
			{
				if($singleCategory["url"] == $newEvents[$i]["categoryUrl"])
				{
					$newEvents[$i]["categoryId"] =$singleCategory["id"];
					break;
				}
			}
		}		*/	
				
		$this->addEvents($newEvents);
	}
		
	private function generateRandomString($length = 10) 
	{
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
		
        return $randomString;
    }
	
	
	
	public function saveAndGetImagePath($url)
	{
		
		$fileInfo = pathinfo($url);
		$fileExtension = $fileInfo["extension"];
		
		$data = file_get_contents($url);
		
		$currentWorkedDirectory = getcwd();
		
	    $newFileName = $this->generateRandomString() . "." . $fileExtension;
		
		$fullFilePath = $currentWorkedDirectory . "/images/" . $newFileName;
		
		file_put_contents($fullFilePath, $data);
		
		
		return "images/" . $newFileName;
	}
	
	private function addEvents($newEvents)
	{
		foreach($newEvents as $singleNewEventCategory)
		{	
			$currentCategoryId = $singleNewEventCategory["categoryId"];
			
			foreach($singleNewEventCategory["events"] as $singleNewEvent)
			{		
				$eventsInfoData = array(
					"a_title" => $singleNewEvent["title"],
					"a_descr" => $singleNewEvent["description"]
				);
				
				$eventInfoId = $this->insertIntoEventsInfoTable($eventsInfoData);	
				
				
				$eventsPicData = array(
					"a_pic" => $singleNewEvent["imageUrl"]
				);
				
				$eventPicId = $this->insertIntoEventsPicTable($eventsPicData);
				
				
				$placesData = array(
					"place_descr" => $singleNewEvent["place"]
				);
				
				$categories = array(
					$currentCategoryId
				);
				
				$categories = $this->parser->makeDataSequenceString($categories);		
				
				$placeId = $this->updatePlacesTable($placesData);
				
				$date = DateTime::createFromFormat('j.m.Y', $singleNewEvent["date"]);
				$dateStart = $date->format('U');
				
				$timePattern = "/ \d{2}:\d{2}/";
				
				preg_match($timePattern, $singleNewEvent["date"], $matches);	
				$time = trim($matches[0]);
			
				$eventData = array(
					"site_id" => $this->siteId,
					"inf_id" => $eventInfoId,
					"category_ids" => $categories,
					"pic_ids" => $eventPicId,
					"place_id" => $placeId,
					"time" => $time,
					"url" => $singleNewEvent["url"],
					"date_start" => $dateStart
				);	

				
				$eventId = $this->insertIntoEventsTable($eventData);
			}
		}
	}
	
	private function updateEventsPicsTable($eventsPicsData)
	{
		
	}
		
	private function updateEventsInfoTable($eventsInfoData)
	{
		
	}
	
	private function updatePlacesTable($placesData)
	{
		$placesFromTable = $this->db->query("select * from places WHERE place_descr='" . $placesData["place_descr"] . "'");
	
			try {
				
		if($placesFromTable->num_rows < 1)
		{
				$info = $this->geocoder->geocode($placesData["place_descr"])->first();
				$coordinates = $info->getCoordinates();
				$longitude = $coordinates->getLongitude();
				$latitude = $coordinates->getLatitude();
				$zip = $info->getPostalCode();
				$country = $info->getCountry();
				$city = $info->getLocality();
				$address = $info->getStreetName();
				$sql = "insert into places(place_descr,zip,country,city,address,latitude,longitude) VALUES('" . $placesData["place_descr"] . "','" . $zip . "','" . $country . "','" . $city . "','" . $address . "'," . $latitude . "," . $longitude . ")";
				$this->db->query($sql);
				return $this->db->insert_id;
		}
			else
			{
				$existingPlaceRow = $placesFromTable->fetch_assoc();
			     return intval($existingPlaceRow["id"]);
			}
			} catch (Exception $e) {
    
			}
	}
	
	
	
	private function getDefaultCategories()
	{
		$categories = array();
		
		$result = $this->db->query("select * from categories");
		
		while($singleCategory = $result->fetch_assoc())
		{
			$categories[] = $singleCategory;
		}
		
		return $categories;
	}
	
	
	private function defineNewEvents($parsedEvents)
	{
		$newEvents = array();
			
		$eventsFromTable = $this->db->query("select * from events");
	
		$eventsFromTableFetched = array();
	
		while($singleEventFromTable = $eventsFromTable->fetch_assoc())
		{
			$eventsFromTableFetched[] = $singleEventFromTable;
		}
		
		$counter = 0;
		
		if($eventsFromTable->num_rows > 0)
		{	
			foreach($parsedEvents as $singleParsedEventCategory)
			{		
				$newEvents[$counter] = array();
				$newEvents[$counter]["categoryUrl"] = $singleParsedEventCategory["categoryUrl"];
				//$newEvents[$counter]["categoryName"] = $singleParsedEventCategory["categoryName"];
				$newEvents[$counter]["events"] = array();
				foreach($singleParsedEventCategory["events"] as $singleParsedEvent)
				{
					$eventExists = false;
					
					foreach($eventsFromTableFetched as $singleEventFromTable)
					{
						$eventFromTableUrl = $singleEventFromTable["url"];
						$parsedEventUrl = $singleParsedEvent["url"];

						if($eventFromTableUrl == $parsedEventUrl)
						{
							$eventExists = true;
							break;
						}
					}
					
					if(!$eventExists)
					{
						$eventsFromTableFetched[] =  $singleParsedEvent;
						$newEvents[$counter]["events"][] = $singleParsedEvent;
					}
				}
				
				$counter++;
			}
		}
		else
		{
			$urlsAlreadyExisted = array();
			
			foreach($parsedEvents as $singleParsedEventCategory)
			{		
				$newEvents[$counter] = array();
				$newEvents[$counter]["categoryUrl"] = $singleParsedEventCategory["categoryUrl"];
				$newEvents[$counter]["events"] = array();
				foreach($singleParsedEventCategory["events"] as $singleParsedEvent)
				{
					$eventExists = false;
					
					foreach($urlsAlreadyExisted as $singleUrlAlreadyExisted)
					{
						$eventUrl = $singleParsedEvent["url"];
						if($eventUrl == $singleUrlAlreadyExisted)
						{
							$eventExists = true;
						}
					}
					
					if(!$eventExists)
					{
						$urlsAlreadyExisted[] =  $singleParsedEvent["url"];
						$newEvents[$counter]["events"][] = $singleParsedEvent;
					}
				}
				
				$counter++;
			}
		}
		
		return $newEvents;
	}
	
	private function updateEventsTable($events)
	{
		
	}
		
	private function updateCategoriesTable($categoriesList)
	{
		if($result = $this->db->query("select * from categories"))
		{	
			$existingCategories = array();
			while($singleExistingCategoryRow = $result->fetch_assoc())
			{
				$existingCategories[] = $singleExistingCategoryRow;
			}
			
			$counter = 0;
	
			foreach($categoriesList as $singleCategory)
			{
	
				$categoryExists = false;
				$singleCategoryName = $singleCategory["name"];
				
				
				
				foreach($existingCategories as $singleExistingCategoryRow)
				{
					$existingCategoryName = $singleExistingCategoryRow["category"];
					
					if($existingCategoryName == $singleCategoryName)
					{	
						
						$categoriesList[$counter]["id"] = $singleExistingCategoryRow["id"];
						$categoryExists = true;
						break;
					}
				}
				
				if(!$categoryExists)
				{					
					$this->db->query("insert into categories(Category) VALUES('". $singleCategoryName ."')");
					$categoriesList[$counter]["id"] = $this->db->insert_id;
				}
			
				
				$counter++;
			}
		}
		
		return $categoriesList;
	}
		
	private function updateSitesTable($siteData)
	{	
		$result = $this->db->query("select * from sites WHERE site_url='". $siteData["site_url"] ."'");
		
		if($this->db->errno) print_r($this->db->error.'<br>');
		if($result->num_rows < 1)
		{
			$this->db->query("insert into sites(site_url,last_parse_timestamp) VALUES('". $siteData["site_url"] ."'," . $siteData["last_parse_timestamp"] . ")");
			if($this->db->errno) print_r($this->db->error.'<br>');
			
			return $this->db->insert_id;
		}
		else
		{
			$this->db->query("update sites set last_parse_timestamp=".$siteData["last_parse_timestamp"]." WHERE site_url='". $siteData["site_url"] ."'");
			if($this->db->errno) print_r($this->db->error.'<br>');
			
			$result = $result->fetch_assoc();
		    return $result["id"];
		}
	}
		
    private function insertIntoSitesTable($data)
    {
		$sql = "Insert into sites(last_parse_timestamp,site_url,events_url,sitename,creator,e_mail) VALUES(". $data["last_parse_timestamp"] .", '". $data["site_url"] ."','". $data["events_url"] ."','". $data["sitename"] ."','". $data["creator"] ."','". $data["e_mail"] ."')";
		$this->db->query($sql);
    }
	
    public function insertIntoEventsInfoTable($data)
    {
        $sql = "Insert into events_inf(a_title,a_descr) VALUES('". $data["a_title"] . "','" . $data["a_descr"] . "'" . ")";
        $this->db->query($sql);
		
		return $this->db->insert_id;
    }
	
    public function insertIntoEventsPicTable($data)
    {
		$sql = "Insert into events_pic(a_pic) VALUES(";
		
		if(isset($data["a_pic"]))
		{
			$sql .= "'" . $data["a_pic"] . "'";
		}
		
		$sql .= ")";
		
        $this->db->query($sql);
		
		return $this->db->insert_id;
    }

    public function insertIntoPlacesTable($data)
    {
		$sql = "Insert into places(place_descr,zip,country,region,city,address,latitude,longitude) VALUES(". $data["place_descr"] . "','" . $data["zip"] . "','" . $data["country"] . "','" . $data["region"] . "','" . $data["city"] . "','" . $data["address"] . "'," . $data["latitude"] . "," . $data["longitude"] . ")";
        $this->db->query($sql);
		
		return $this->db->insert_id;
    }
	
    public function insertIntoEventsTable($data)
    {
        $sql = "Insert into events(site_id,inf_id,category_ids,pic_ids,place_id,url,time,date_start) VALUES(" . $data["site_id"] . "," . $data["inf_id"] . ",'" . $data["category_ids"] . "','" . $data["pic_ids"] . "'," .  $data["place_id"] . ",'" . $data["url"] . "','" . $data["time"]  . "'," . $data["date_start"] . ")";
        $this->db->query($sql);
		if($this->db->errno) print_r($this->db->error.'<br>');
    }
	
    private function insertIntoCategoriesTable($data)
    {
        $sql = "Insert into categories(category) VALUES('". $data["category"] . "')";
        $this->db->query($sql);
		if($this->db->errno) print_r($this->db->error.'<br>');
    }
}



