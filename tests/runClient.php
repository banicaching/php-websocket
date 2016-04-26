<?php

	require_once('/path/folder/WebSocketClient.php'); 
	
	$wb = new WSS\WebSocketClient;
	$data = '{"name": "XXX", "recevier": "XXX", "text": "XXX"}';  //data to be send
	$wbSocket = $wb->connection();
	
	if ($wbSocket !== -1) {
		$result =  $wb->send($wbSocket, $data);
	}
	
	$wb->close($wbSocket);
		
?>
