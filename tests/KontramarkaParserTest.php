<?php 

	$currentWordkedDirectory = getcwd();
	include_once $currentWordkedDirectory . "/../SimpleHtmlDom.php";
	include_once $currentWordkedDirectory . "/../KontramarkaParser.php";
	include_once $currentWordkedDirectory . "/../HttpHelper.php";

	class KontramarkaParserTest extends PHPUnit_Framework_TestCase
	{
		private $kontramarkaParser;
		private $httpHelper;

		public function __construct()
		{
			$this->kontramarkaParser = KontramarkaParser::getParser();
		}

		public function test_getSiteUrl()
		{
			$siteUrl = $this->kontramarkaParser->getSiteUrl();
			$this->assertEquals($siteUrl, "https://www.kontramarka.de/");
		}

		/*public function test_getCategoriesList()
		{
			$categoriesList = $this->kontramarkaParser->getCategoriesList();
			//var_dump($categoriesList);
		}

		public function test_getEventsUrlsByCategoriesUrls()
		{
			$categoriesList = $this->kontramarkaParser->getCategoriesList();
			$eventsUrls = $this->kontramarkaParser->getEventsUrlsByCategoriesUrls($categoriesList);
			var_dump($eventsUrls);
		}*/

		public function test_parseSingleEvent()
		{
			/*$testEventUrl = "https://www.kontramarka.de/tour/view/fiksiki/?PHPSESSID=24a0f628eda89dfeabbc41e9971eb1c1";
			$parsedEvent = $this->kontramarkaParser->getSingleEventByUrl($testEventUrl);

			var_dump($parsedEvent);*/
		}

		public function test_getSeveralEventsFromArray()
		{
			
		}
	}

?>