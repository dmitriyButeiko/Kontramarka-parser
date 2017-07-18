<?php 

	class KontramarkaApiHelper
	{
		private $aid = 1003;
		private $secretKey = "9ad799f5ebbd32";
		private $apiUrl = "https://www.kontramarka.de/api.php";

		public function getAid()
		{
			return $this->aid;
		}

		public function getSecretKey()
		{
			return $this->secretKey;
		}

		public function apiRequest($data)
		{
			// сортируем массив по ключам в алфавитном порядку по убыванию
 			ksort($data);
 			$hash = $this->generateHash($data);
 			$data['hash'] = $hash;

		  	$dataString = json_encode($data);
		  	$ch = curl_init($this->apiUrl);                                       
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);                  
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                    
				  'Content-Type: application/json',                                                    
				  'Content-Length: ' . strlen($dataString)
			));
		  	$result = curl_exec($ch);
		  	curl_close($ch);
		  	$decodedResult = json_decode($result);

		  	return $decodedResult;
		}

		public function getHelper()
		{
			$instance = null;

			if($instance == null)
			{
				$instance = new KontramarkaApiHelper();
			}

			return $instance;
		}	

		private function __construct()
		{

		}

		private function generateHash($dataArr)
		{
			$hashKey = '';

			foreach($dataArr as $key => $val)
			{
  			    $hashKey .= $val;
			}

 			$hash = md5($hashKey . $this->secretKey);

			return $hash;
		}
	}

?>