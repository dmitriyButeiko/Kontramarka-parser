<?php 
	
	/*
	*  Helper class for making http requests
	*/
	class HttpHelper
	{
		/*
		*  Methods to imlement Singleton pattern
		*/
		public static function getHelper()
		{
			$instance = null;
			if($instance == null)
			{
				$instance = new HttpHelper();
			}  
			return $instance;
		}

		/*
    	* Make GET request to specific url
    	* @param String $url Url for which url must be made
  	  	*/
    	public function getHtml($url)
    	{
        	$ch = curl_init($url);
        	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        	$html = curl_exec($ch);

        	return $html;
    	}

    	public function getCurlDescriptors($amount, $urls)
    	{
        	$curlDescriptors = array();

        	for ($i = 0; $i < $amount; $i++) {
            	$curlDescriptors[$i] = $this->getCurlDescriptor($urls[$i]);
        	}

        	return $curlDescriptors;
   		}

    	public function createCurlMulti($descriptors)
    	{
        	$mh = curl_multi_init();

        	foreach ($descriptors as $singleDescriptor) {
                curl_multi_add_handle($mh, $singleDescriptor);
        	}

        	return $mh;
    	}	

        public function executeCurlMultiAndGetHtmlArray($mh, $curlArr)
        {
        	$htmlArray = array();

		
        	//запускаем дескрипторы
        	do {
            	curl_multi_exec($mh,$running);
        	} while($running > 0);

        	$counter = 0;
			$node_count = count($curlArr);

			for($i = 0; $i < $node_count; $i++)
			{
				$htmlArray[$counter]         = array();
            	$htmlArray[$counter]["url"]  = curl_getinfo($curlArr[$i], CURLINFO_EFFECTIVE_URL);
            	$htmlArray[$counter]["html"] = curl_multi_getcontent( $curlArr[$i]  );
				$counter++;
            	curl_close($curlArr[$i]);
			}

        	curl_multi_close($mh);

        	return $htmlArray;
    	}

    	public function getCurlDescriptor($url)
    	{
        	$ch = curl_init($url);

        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        	return $ch;
    	}

    	/*
    	* Constructor
    	*/
		private function __construct()
		{

		}
	}

?>