<?php
	
	namespace WSS;
	
	include('autoload.php');
	
	class WebSocketClient extends DataProcess implements WbConfig  {
		
		/**
		 *  Construct
		 *
		 *  @access Public
		 *  @param
		 *  @return
		 *
		 */
		public function __construct() {
			$this->server = self::SERVER;  // where is the websocket server
			$this->portocol = self::PORTOCOL; // for security
			$this->port = self::PORT;
			$this->local = self::LOCAL;  // url where this script run
		}
		
		/**
		 *  WebSocket Handshake
		 *
		 *  @access Public
		 *  @param null
		 *  @return String
		 *
		 */
		public function connection() {
			$randKey = MD5(time());
			$requestHead = "GET / HTTP/1.1"."\r\n".
				"Host: " . $this->server . "\r\n".
				"Upgrade: websocket"."\r\n".
				"Origin: " . $this->local ."\r\n".
				"Connection: Upgrade"."\r\n".
				"Sec-WebSocket-Key: $randKey \r\n".
				"Sec-WebSocket-Version: 13"."\r\n".
				"Content-Length: 0 \r\n"."\r\n";

			$socket = fsockopen($this->portocol . $this->server, $this->port, $errno, $errstr, 2);
			$result = fwrite($socket, $requestHead);
			// die ('error:' . $errno . ':' . $errstr);
			
			if ($socket) {
				$responseHeader = fread($socket, 2000);
				$result = $socket;
			} else {
				$result  = -1;
			}	
			return $result;
		}
		
		/**
		 *  WebSocket Send Data
		 *
		 *  @access Public
		 *  @param String, String
		 *  @return String
		 *
		 */
		public function send($socket, $data) {
			$result = fwrite($socket, $this->encodeClient($data));
			return ($this->receive($socket));
		}
		
		/**
		 *  WebSocket Receive Data
		 *
		 *  @access Public
		 *  @param String
		 *  @return String
		 *
		 */
		public function receive($socket) {
			//receives the data included in the websocket package "\x00DATA\xff"
			$wsData = fread($socket, 2000);
			//extracts data
			$retData = $this->decodeClient($wsData);
			return $retData;
		}
		
		/**
		 *  Close WebSocket Connection
		 *
		 *  @access Public
		 *  @param String
		 *  @return null
		 *
		 */
		public function close($socket) {
			fclose($socket);
		}
	}
?>
