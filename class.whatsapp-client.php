<?php
class WhatsappClient{
    private $logger;
	private $apiUrl;
	private $token;
	
	function __construct($apiUrl,$token,$logger) {
	   $this->apiUrl = $apiUrl;
	   $this->token = $token;
	   $this->logger = $logger;
	}
	
	public function sendMessage($phone,$message){
		$data = [
			'phone' => $phone,
			'body' => $message
		];
		$json = json_encode($data);

		$options = stream_context_create(['http' => [
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => $json
			]
		]);
		
		$r =  file_get_contents($this->apiUrl."message?token=".$this->token, false, $options);		
		if($r === FALSE){
			return FALSE;
		}
		

		$this->logger->info("Message '".$message."' sent to ".$phone);
		return json_decode($r);
	}
}