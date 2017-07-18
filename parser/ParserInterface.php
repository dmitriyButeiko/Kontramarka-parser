<?php 

	interface Parser 
	{
		public function getCategoriesList();
		public function getEventsUrlsByCategoriesUrls($categoriesList, $numberOfThreads);
		public function getSeveralEventsFromArray($eventsUrlsArray, $numberOfThreads);
		public function getSiteUrl();
	}

?>