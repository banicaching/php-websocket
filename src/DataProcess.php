<?php

	namespace WSS;

	class DataProcess implements IWebSocketServer {
		
		public function __construct() {
			
		}
		
		public function encode($payload, $type = self::EVENT_TYPE_TEXT, $masked = false) {
			$frameHead = [];
			$payloadLength = strlen($payload);

			switch ($type) {
				case self::EVENT_TYPE_TEXT:
					// first byte indicates FIN, Text-Frame (10000001):
					$frameHead[0] = self::ENCODE_TEXT;
					break;

				case self::EVENT_TYPE_CLOSE:
					// first byte indicates FIN, Close Frame(10001000):
					$frameHead[0] = self::ENCODE_CLOSE;
					break;

				case self::EVENT_TYPE_PING:
					// first byte indicates FIN, Ping frame (10001001):
					$frameHead[0] = self::ENCODE_PING;
					break;

				case self::EVENT_TYPE_PONG:
					// first byte indicates FIN, Pong frame (10001010):
					$frameHead[0] = self::ENCODE_PONG;
					break;
			}

			// set mask and payload length (using 1, 3 or 9 bytes)
			if ($payloadLength > self::PAYLOAD_MAX_BITS) {
				$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), self::PAYLOAD_CHUNK);
				$frameHead[1] = ($masked === true) ? self::MASK_255 : self::MASK_127;
				for ($i = 0; $i < 8; $i++) {
					$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
				}
				// most significant bit MUST be 0
				if ($frameHead[2] > self::MASK_127) {
					return ['type' => $type, 'payload' => $payload, 'error' => self::ERR_FRAME_TOO_LARGE];
				}
			} elseif ($payloadLength > self::MASK_125) {
				$payloadLengthBin = str_split(sprintf('%016b', $payloadLength), self::PAYLOAD_CHUNK);
				$frameHead[1] = ($masked === true) ? self::MASK_254 : self::MASK_126;
				$frameHead[2] = bindec($payloadLengthBin[0]);
				$frameHead[3] = bindec($payloadLengthBin[1]);
			} else {
				$frameHead[1] = ($masked === true) ? $payloadLength + self::MASK_128 : $payloadLength;
			}

			// convert frame-head to string:
			foreach (array_keys($frameHead) as $i) {
				$frameHead[$i] = chr($frameHead[$i]);
			}
			if ($masked === true) {
				// generate a random mask:
				$mask = [];
				for ($i = 0; $i < 4; $i++) {
					$mask[$i] = chr(rand(0, self::MASK_255));
				}

				$frameHead = array_merge($frameHead, $mask);
			}
			$frame = implode('', $frameHead);

			// append payload to frame:
			for ($i = 0; $i < $payloadLength; $i++) {
				$frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
			}

			return $frame;
		}
		
		public function decode($data) {        
			if (empty($data)) {
				return null; // close has been sent
			}
			
			$unmaskedPayload = '';
			$decodedData = [];

			// estimate frame type:
			$firstByteBinary = sprintf('%08b', ord($data[0]));
			$secondByteBinary = sprintf('%08b', ord($data[1]));
			$opcode = bindec(substr($firstByteBinary, 4, 4));
			$isMasked = ($secondByteBinary[0] == '1') ? true : false;
			$payloadLength = ord($data[1]) & self::MASK_127;

			// unmasked frame is received:
			if (!$isMasked) {
				return ['type' => '', 'payload' => '', 'error' => self::ERR_PROTOCOL];
			}

			switch ($opcode) {
				// text frame:
				case self::DECODE_TEXT:
					$decodedData['type'] = self::EVENT_TYPE_TEXT;
					break;
				case self::DECODE_BINARY:
					$decodedData['type'] = self::EVENT_TYPE_BINARY;
					break;
				// connection close frame:
				case self::DECODE_CLOSE:
					$decodedData['type'] = self::EVENT_TYPE_CLOSE;
					break;
				// ping frame:
				case self::DECODE_PING:
					$decodedData['type'] = self::EVENT_TYPE_PING;
					break;
				// pong frame:
				case self::DECODE_PONG:
					$decodedData['type'] = self::EVENT_TYPE_PONG;
					break;
				default:
					return ['type' => '', 'payload' => '', 'error' => self::ERR_UNKNOWN_OPCODE];
			}

			if ($payloadLength === self::MASK_126) {
				$mask = substr($data, 4, 4);
				$payloadOffset = PAYLOAD_OFFSET_8;
				$dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
			} elseif ($payloadLength === self::MASK_127) {
				$mask = substr($data, 10, 4);
				$payloadOffset = self::PAYLOAD_OFFSET_14;
				$tmp = '';
				for ($i = 0; $i < 8; $i++) {
					$tmp .= sprintf('%08b', ord($data[$i + 2]));
				}
				$dataLength = bindec($tmp) + $payloadOffset;
				unset($tmp);
			} else {
				$mask = substr($data, 2, 4);
				$payloadOffset = self::PAYLOAD_OFFSET_6;
				$dataLength = $payloadLength + $payloadOffset;
			}

			if (strlen($data) < $dataLength) {
				return false;
			}

			if ($isMasked) {
				for ($i = $payloadOffset; $i < $dataLength; $i++) {
					$j = $i - $payloadOffset;
					if (isset($data[$i])) {
						$unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
					}
				}
				$decodedData['payload'] = $unmaskedPayload;
			} else {
				$payloadOffset = $payloadOffset - 4;
				$decodedData['payload'] = substr($data, $payloadOffset);
			}

			return $decodedData;
		}
		
		public function decodeClient($data) {
			$bytes = $data;
			$dataLength = '';
			$mask = '';
			$coded_data = '';
			$decodedData = '';
			$secondByte = sprintf('%08b', ord($bytes[1]));
			$masked = ($secondByte[0] == '1') ? true : false;
			$dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);

			if($masked === true)
			{
				if($dataLength === 126)
				{
				   $mask = substr($bytes, 4, 4);
				   $coded_data = substr($bytes, 8);
				}
				elseif($dataLength === 127)
				{
					$mask = substr($bytes, 10, 4);
					$coded_data = substr($bytes, 14);
				}
				else
				{
					$mask = substr($bytes, 2, 4);       
					$coded_data = substr($bytes, 6);        
				}   
				for($i = 0; $i < strlen($coded_data); $i++)
				{       
					$decodedData .= $coded_data[$i] ^ $mask[$i % 4];
				}
			}
			else
			{
				if($dataLength === 126)
				{          
				   $decodedData = substr($bytes, 4);
				}
				elseif($dataLength === 127)
				{           
					$decodedData = substr($bytes, 10);
				}
				else
				{               
					$decodedData = substr($bytes, 2);       
				}       
			}   

			return $decodedData;
		}


		public function encodeClient($payload, $type = 'text', $masked = true) {
			$frameHead = array();
			$frame = '';
			$payloadLength = strlen($payload);

			switch ($type) {
				case 'text':
					// first byte indicates FIN, Text-Frame (10000001):
					$frameHead[0] = 129;
					break;

				case 'close':
					// first byte indicates FIN, Close Frame(10001000):
					$frameHead[0] = 136;
					break;

				case 'ping':
					// first byte indicates FIN, Ping frame (10001001):
					$frameHead[0] = 137;
					break;

				case 'pong':
					// first byte indicates FIN, Pong frame (10001010):
					$frameHead[0] = 138;
					break;
			}

			// set mask and payload length (using 1, 3 or 9 bytes)
			if ($payloadLength > 65535) {
				$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
				$frameHead[1] = ($masked === true) ? 255 : 127;
				for ($i = 0; $i < 8; $i++) {
					$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
				}

				// most significant bit MUST be 0 (close connection if frame too big)
				if ($frameHead[2] > 127) {
					$this->close(1004);
					return false;
				}
			} elseif ($payloadLength > 125) {
				$payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
				$frameHead[1] = ($masked === true) ? 254 : 126;
				$frameHead[2] = bindec($payloadLengthBin[0]);
				$frameHead[3] = bindec($payloadLengthBin[1]);
			} else {
				$frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
			}

			// convert frame-head to string:
			foreach (array_keys($frameHead) as $i) {
				$frameHead[$i] = chr($frameHead[$i]);
			}

			if ($masked === true) {
				// generate a random mask:
				$mask = array();
				for ($i = 0; $i < 4; $i++) {
					$mask[$i] = chr(rand(0, 255));
				}

				$frameHead = array_merge($frameHead, $mask);
			}
			$frame = implode('', $frameHead);
			// append payload to frame:
			for ($i = 0; $i < $payloadLength; $i++) {
				$frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
			}

			return $frame;
		}
	}

?>
