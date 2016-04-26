<?php

	namespace WSS;

	include('autoload.php');

	class WebSocketServer extends DataProcess implements IWebSocketServer, WbConfig{
		
		/**
		 *  Construct
		 *
		 *  @access Public
		 *  @param
		 *  @return
		 *
		 */
		public function __construct() {
			
			// Server Config
			$this->server = self::SERVER;  // where is the websocket server
			$this->portocol = self::PORTOCOL; // for security
			$this->port = self::PORT;
			$this->pemfile = self::PEMFILE;
			
			// Define
			$this->clients = [];
			$this->clientsData = array();
			$this->handshakes = [];
			$this->headersUpgrade = [];
			$this->currentConn = null;
			
			// Start Server
			$this->createServer();
		}		
		
		/**
		 *  Create WSS Server 
		 *
		 *  @access Private
		 *  @param null
		 *  @return null
		 *
		 */
		private function createServer () {
		
			$errno = null;
			$errorMessage = '';
			$context = stream_context_create();

			// local_cert must be in PEM format
			stream_context_set_option($context, 'ssl', 'local_cert', $this->pemfile);
			stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
			stream_context_set_option($context, 'ssl', 'verify_peer', false);	
		
			$server = stream_socket_server(
					$this->portocol . $this->server . ":" . $this->port,
					$errno, 
					$errorMessage,
					STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
					$context
			);
			
			if ($server === false) {
				die('Could not bind to socket: ' . $errno . ' - ' . $errorMessage . PHP_EOL);
			} else {	
				echo "Server Started : " . date('Y-m-d H:i:s') . "\n";
				echo "Master socket  : " . $context . "\n";
				echo "Listening on   : " . $this->portocol . $this->server . ":" . $this->port . "\n\n";
				$this->eventListener($server);
			}
		}
		
		/**
		 *  Loop for listen event 
		 *
		 *  @access Private
		 *  @param String
		 *  @return null
		 *
		 */
		private function eventListener($server) {

			while(true) {
				//prepare readable sockets
				$readSocks = $this->clients;
				$readSocks[] = $server;

				//start reading and use a large timeout
				if (!stream_select($readSocks, $write, $except, self::STREAM_SELECT_TIMEOUT)) {
					die('something went wrong while selecting');
				}
				
				//new client
				if (in_array($server, $readSocks)) {
					$newClient = stream_socket_accept($server, 0); // must be 0 to non-block          
					if ($newClient) {
						// print remote client information, ip and port number
						$socketName = stream_socket_get_name($newClient, true);
						echo "New Client = " . $socketName . "\n";
						
						// important to read from headers here coz later client will change and there will be only msgs on pipe
						$headers = fread($newClient, self::HEADER_BYTES_READ);

						if ($newClient) {
							$hKey = $this->handShake($newClient, $headers);
							if(!empty($hKey)) {
								$this->clients[] = $newClient;
							}
							echo "Handshake key = " . $hKey . "\n";
						}
					}
					//delete the server socket from the read sockets
					unset($readSocks[array_search($server, $readSocks)]);
				}
				
				 //message from existing client
				foreach ($readSocks as $kSock => $sock) {
					$data = $this->decode(fread($sock, self::MAX_BYTES_READ));
					
					if ($data) {
						$dataType = $data['type'];
						$dataPayload = $data['payload'];
						$this->currentConn = $sock;
						
						if (empty($data) || $dataType === self::EVENT_TYPE_CLOSE) { // close event triggered from client - browser tab or close socket event
							// trigger CLOSE event
							try {
								echo "Close: " . $this->currentConn . "\n";
								$this->close($this->currentConn);
							} catch (Exception $e) {
								echo "Exception: " . $e . "\n";
							}
							$clientIndex = array_search($sock, $this->clients);
							unset($this->clients[$clientIndex]);
							unset($this->clientsData[$clientIndex]);
							unset($readSocks[$kSock]); // to avoid event leaks
							continue;
						}
						
						if ($dataType === self::EVENT_TYPE_TEXT) {
							// trigger MESSAGE event
							try {
								echo "Trigger MESSAGE event \n";
								$this->onMessage($this->currentConn, $dataPayload);
							} catch (Exception $e) {
								echo "Exception: " . $e . "\n";
							}
						}
					}
				}
			}
		}
		
		
		/**
		 *  Get current connection 
		 *
		 *  @access Private
		 *  @param String
		 *  @return String
		 *
		 */
		private function handShake($client, $headers) {
			$match = [];
			$key = empty($this->handshakes[intval($client)]) ? 0 : $this->handshakes[intval($client)];
			preg_match(self::SEC_WEBSOCKET_KEY_PTRN, $headers, $match);
			if (empty($match[1])) {
				return false;
			}

			$key = $match[1];
			$this->handshakes[intval($client)] = $key;

			// sending header according to WebSocket Protocol
			$secWebSocketAccept = base64_encode(sha1(trim($key) . self::HEADER_WEBSOCKET_ACCEPT_HASH, true));
			$this->setHeadersUpgrade($secWebSocketAccept);
			$upgradeHeaders = $this->getHeadersUpgrade();

			echo "upgradeHeaders = " . $upgradeHeaders ."\n";
			fwrite($client, $upgradeHeaders);
			return $key;
		}
		
		/**
		 *  Set header information 
		 *
		 *  @access Private
		 *  @param String
		 *  @return null
		 *
		 */
		private function setHeadersUpgrade($secWebSocketAccept) {
			$this->headersUpgrade = [
				self::HEADERS_UPGRADE_KEY => self::HEADERS_UPGRADE_VALUE,
				self::HEADERS_CONNECTION_KEY => self::HEADERS_CONNECTION_VALUE,
				self::HEADERS_SEC_WEBSOCKET_ACCEPT_KEY => ' '. $secWebSocketAccept // the space before key is really important
			];
		}
		
		/**
		 *  Get header information 
		 *
		 *  @access Private
		 *  @param String
		 *  @return String
		 *
		 */
		private function getHeadersUpgrade() {
			$handShakeHeaders = self::HEADER_HTTP1_1 . self::HEADERS_EOL;
			if (empty($this->headersUpgrade)) {
				die('Headers array is not set' . PHP_EOL);
			}
			foreach ($this->headersUpgrade as $key => $header) {
				$handShakeHeaders .= $key . ':' . $header . self::HEADERS_EOL;
				if ($key === self::HEADERS_SEC_WEBSOCKET_ACCEPT_KEY) { // add additional EOL fo Sec-WebSocket-Accept
					$handShakeHeaders .= self::HEADERS_EOL;
				}
			}
			return $handShakeHeaders;
		}
		
		/**
		 *  Close connection
		 *
		 *  @access Private
		 *  @param String
		 *  @return null
		 *
		 */
		private function close($sockConn) {
			if (is_resource($sockConn)) {
				fclose($sockConn);
			}
		}
		
		/**
		 *  Handle data
		 *
		 *  @access Private
		 *  @param String, String
		 *  @return null
		 *
		 */
		private function onMessage ($sock, $dataPayload) {

			$jsonArray = json_decode($dataPayload);

			$recevier = $jsonArray->recevier;
			$msg = $jsonArray->text;
			
			// Put client into array
			$this->clientsData[array_search($sock, $this->clients)]['socket'] = $sock;
			$this->clientsData[array_search($sock, $this->clients)]['name'] = $jsonArray->name;
			$this->clientsData[array_search($sock, $this->clients)]['recevier'] = $recevier;
			$this->clientsData[array_search($sock, $this->clients)]['message'] = $msg;
			
			// For register
			$this->send($sock, json_encode(array(0)));
			
			foreach ($this->clientsData as $key => $val) {	
				$result = false;
				
				if($this->clientsData[$key]['socket'] !== $sock
					&& $this->clientsData[$key]['name'] == $recevier
				) {
					$sendText = array('type' => 'message', 'data'=>array('time' => date('Y-m-d H:i:s'), 'text' => $msg));
					$result = $this->send($this->clientsData[$key]['socket'], json_encode($sendText));
					echo "Data Send to = " . $recevier  . ", data = " . $msg . "\n";
				}
			}	
		}
		
		/**
		 *  Send data
		 *
		 *  @access Private
		 *  @param String, String
		 *  @return boolean
		 *
		 */	
		private function send($client, $data) {
			$len = fwrite($client, $this->encode($data));
			echo "Len = $len\n";
			if ($len !== 0) {
				return true;
			} else {
				return false;
			}
		}
	}
?>
