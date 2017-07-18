<?php 

    $currentWorkedDirectory = getcwd();


	/*require_once "/../includes/SimpleHtmlDom.php";
	require_once "/../parser/ParserInterface.php";*/

    require_once $currentWorkedDirectory . "/includes/SimpleHtmlDom.php";
    require_once $currentWorkedDirectory . "/parser/ParserInterface.php";
    require_once $currentWorkedDirectory . "/includes/HttpHelper.php";

	class KontramarkaParser implements Parser
	{
		private $siteUrl = "https://www.kontramarka.de/";

		public static function getParser()
		{
			$instance = null;

			if($instance == null)
			{
				$instance = new KontramarkaParser();
			}

			return $instance;
		}

		public function makeDataSequenceString($dataList)
    	{
        	$resultString = "";

        	$arrayLength = count($dataList);
        	$counter     = -1;

        	foreach ($dataList as $singleValue) {
            	if ($counter != -1) {
                	$resultString .= ", ";
            	}
			
				$resultString .= $singleValue;
	
            	$counter++;
        	}

        	return $resultString;
    	}

		public function getSiteUrl()
		{
			return $this->siteUrl;
		}

		public function getCategoriesList()
		{
			$categoriesList = array();

        	$mainPageHtml = $this->httpHelper->getHtml($this->siteUrl);
        	$categoriesList = $this->parseCategoriesList($mainPageHtml);

        	return $categoriesList;
		}

    	public function getEventsUrlsByCategoriesUrls($categoriesList, $numberOfThreads = 4)
    	{
        	$categoriesUrls = array();	
		
        	foreach ($categoriesList as $singleCategory) {
            	$categoriesUrls[] = $singleCategory["url"];
        	}

        	$currentIndex     = 0;
        	$htmlFilesArray   = array();
        	$eventsUrls       = array();
        	$eventsDataList   = array();
		

		
        	$amountOfElements = count($categoriesUrls);

        	if ($numberOfThreads > $amountOfElements) {
            	$numberOfThreads = $amountOfElements;
        	}
		
	
        	// делит массив url на части в соотвествии с количеством потоков установленных пользователем
        	for ($i = 0; $i < $amountOfElements; $i = $i + $numberOfThreads) {
		
            	$urlsList = array();
            	for ($j = $i; $j < $i + $numberOfThreads; $j++) {
                	if (isset($categoriesUrls[$j]) && !(is_null($categoriesUrls[$j]))) {
                    	$urlsList[] = $categoriesUrls[$j];
                	}
            	}		
			

			
            	$curlDescriptors = $this->httpHelper->getCurlDescriptors(count($urlsList), $urlsList);
            	$curlMulti       = $this->httpHelper->createCurlMulti($curlDescriptors);
            	$htmlArray       = $this->httpHelper->executeCurlMultiAndGetHtmlArray($curlMulti, $curlDescriptors);
						
            	for ($htmlArrayCounter = 0; $htmlArrayCounter < count($htmlArray); $htmlArrayCounter++) {
                    $htmlFilesArray[] = $htmlArray[$htmlArrayCounter];
            	}
        	}

        	$counter = 0;

        	foreach ($htmlFilesArray as $singleHtmlFile) {
            	$eventsUrls[$counter]        = array();
            	$eventsUrls[$counter]["url"] = $singleHtmlFile["url"];


            	$eventsUrls[$counter]["eventsUrls"] = $this->parseEventsUrlsList($singleHtmlFile["html"]);

            	$counter++;
        	}

        	return $eventsUrls;
    	}


        public function getSeveralEventsFromArray($eventsUrlsArray, $numberOfThreads = 4)
        {
            $currentIndex     = 0;
            $htmlFilesArray   = array();
            $eventsDataList   = array();
            $amountOfElements = count($eventsUrlsArray);
            $htmlArray = array();
        
            if ($numberOfThreads > $amountOfElements) {
                $numberOfThreads = $amountOfElements;
            }

            // делит массив url на части в соотвествии с количеством потоков установленных пользователем
            for ($i = 0; $i < $amountOfElements; $i = $i + $numberOfThreads) {
                $urlsList = array();
                for ($j = $i; $j < $i + $numberOfThreads; $j++) {
                    if (isset($eventsUrlsArray[$j]) && !(is_null($eventsUrlsArray[$j]))) {
                        $urlsList[] = $eventsUrlsArray[$j];
                    }
                }

                $curlDescriptors = $this->httpHelper->getCurlDescriptors(count($urlsList), $urlsList);          
                $curlMulti       = $this->httpHelper->createCurlMulti($curlDescriptors);
                $htmlArray       = $this->httpHelper->executeCurlMultiAndGetHtmlArray($curlMulti, $curlDescriptors);
            
                for ($htmlArrayCounter = 0; $htmlArrayCounter < count($htmlArray); $htmlArrayCounter++) {
                    //$htmlFilesArray[] = $htmlArray[$htmlArrayCounter]["html"];
                }
            }

            $counter = 0;

            foreach ($htmlArray as $singleEventHtml) {
                $eventsDataList[$counter] = $this->parseSingleEvent($singleEventHtml["html"]);
                $eventsDataList[$counter]["url"] = $singleEventHtml["url"];
                $counter++;
            }
        

            return $eventsDataList;
        }


		public function __construct()
		{
			$this->httpHelper = HttpHelper::getHelper();
		}

		public function parseCategoriesList($html)
		{
			$categoriesList = array();
        	$html = str_get_html($html);
        	$categories = $html->find("div.topmenu ul li a");

        	$counter = 0;

        	foreach ($categories as $singleCategory) {
            	$categoriesList[$counter]         = array();

                $singleCategoryUrl = $singleCategory->href;

                if($singleCategoryUrl[0] == "/")
                {
                    $singleCategoryUrl = substr($singleCategoryUrl, 1);
                }

				
				$parsedUrl = parse_url($this->siteUrl . $singleCategoryUrl);
				
				$singleCategoryUrl = $parsedUrl["scheme"] . "://" . $parsedUrl["host"] . $parsedUrl["path"];
				
				
				
            	$categoriesList[$counter]["url"]  = $singleCategoryUrl;
            	$categoriesList[$counter]["name"] = $singleCategory->innertext;
            	$counter++;
        	}

        	return $categoriesList;
		}

		public function parseSingleEvent($html)
		{
			$singleEvent = array();

        	$html = str_get_html($html);


            $imageUrl = $html->find("#concert_block div.concert_desc img", 0)->src;

            if($imageUrl[0] == "/")
            {
                $imageUrl = substr($imageUrl, 1);
            }

            $imageUrl = $this->siteUrl . $imageUrl;

        	$singleEvent["imageUrl"] = $imageUrl; 
		
			$description = "";
		
			foreach($html->find("#concert_block .concert_desc p") as $singleSpanContent)
			{
				$description .= $singleSpanContent->innertext;
			}
		
			//$datePattern = "/\d{2}.\d{2}.\d{4} \d{2}:\d{2}/i";
        
			$singleEvent["description"] = $this->prepareString($description);
		
		
			$datePattern = "/\d{2}.\d{2}.\d{4} \d{2}:\d{2}/i";
       
            $concertDate = $this->prepareString($html->find("#concert_select > .inner > .concert_date > div", 0)->innertext);

			//preg_match($datePattern, @strip_tags(trim($html->find("#page div.date", 0)->innertext)), $matches);
			$singleEvent["date"] = $concertDate;
			//$singleEvent["price"] = $html->find("#page div.price", 0)->innertext;

            $concertPlace = $html->find("#concert_select > .inner > .concert_city", 0)->innertext;
        	$singleEvent["place"] = $this->prepareString($concertPlace);

            $concertTitle = $html->find("#concert_block .concert_desc h1", 0)->innertext;
        	$singleEvent["title"] = $this->prepareString($concertTitle);

        	return $singleEvent;
		}

		public function prepareString($string)
		{
			$string = strip_tags($string);
			$string = trim($string);

			return $string;
		}

		public function parseEventsUrlsList($html)
    	{
        	$eventsList = array();

        	$html = str_get_html($html);
        	$events = $html->find("ul.events > li");
        	$counter = 0;

        	foreach ($html->find(".events_block a") as $singleEventLink) {
            	$url = $singleEventLink->href;

                if($url[0] == "/")
                {
                    $url = substr($url, 1);
                }

            	/*if (!is_bool(strpos($url, "http"))) {
                	$eventsList[$counter] = $singleEvent->find("a", 0)->href;
            	}*/

				$parsedUrl = parse_url($this->siteUrl . $url);
				
				$singleEventUrl = $parsedUrl["scheme"] . "://" . $parsedUrl["host"] . $parsedUrl["path"];
				
            	$eventsList[$counter] = $singleEventUrl;

            	$counter++;
        	}

        	return $eventsList;
    	}

        public function getSingleEventByUrl($url)
        {
            $singleEventHtml = $this->httpHelper->getHtml($url);
            $eventInfo = $this->parseSingleEvent($singleEventHtml);
            return $eventInfo;
        }

        public function getCurlDescriptors($number, $list)
        {
            return $this->httpHelper->getCurlDescriptors($number, $list);
        }

	}


?>