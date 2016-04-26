<?php

	namespace WSS;

	interface IWebSocketServer {
		
		const STREAM_SELECT_TIMEOUT = 3600;
		const MAX_BYTES_READ = 8192,
			  HEADER_BYTES_READ = 1024;
			  
		// Headers 
		const HEADER_HTTP1_1 = 'HTTP/1.1 101 Web Socket Protocol Handshake',
			  HEADER_WEBSOCKET_ACCEPT_HASH = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		const HEADERS_UPGRADE_KEY = 'Upgrade',
			  HEADERS_CONNECTION_KEY = 'Connection',
			  HEADERS_SEC_WEBSOCKET_ACCEPT_KEY = 'Sec-WebSocket-Accept';
		const HEADERS_UPGRADE_VALUE = 'websocket',
			  HEADERS_CONNECTION_VALUE = 'Upgrade';
		const HEADERS_EOL = "\r\n";
		const SEC_WEBSOCKET_KEY_PTRN = '/Sec-WebSocket-Key:\s(.*)\n/';
		
		// ENCODER/DECODER ERRORS
		const ERR_PROTOCOL = 'protocol error (1002)',
			  ERR_UNKNOWN_OPCODE = 'unknown opcode (1003)',
			  ERR_FRAME_TOO_LARGE = 'frame too large (1004)';
		
		// DADA types
		const EVENT_TYPE_PING = 'ping',
			  EVENT_TYPE_PONG = 'pong',
			  EVENT_TYPE_TEXT = 'text',
			  EVENT_TYPE_CLOSE = 'close',
			  EVENT_TYPE_BINARY = 'binary';
	
		// DECODE FRAMES
		const DECODE_TEXT = 1,
  			  DECODE_BINARY = 2,
			  DECODE_CLOSE = 8,
			  DECODE_PING = 9,
			  DECODE_PONG = 10;
		
		// ENCODE FRAMES
		const ENCODE_TEXT = 129,
			  ENCODE_CLOSE = 136,
			  ENCODE_PING = 137,
			  ENCODE_PONG = 138;
		
		// MASKS
		const MASK_125 = 125,
			  MASK_126 = 126,
			  MASK_127 = 127,
			  MASK_128 = 128,
			  MASK_254 = 254,
			  MASK_255 = 255;
		
		// PAYLOADS
		const PAYLOAD_CHUNK = 8;
		const PAYLOAD_MAX_BITS = 65535;
		
		// PAYLOAD OFFSETS
		const PAYLOAD_OFFSET_6 = 6, 
			  PAYLOAD_OFFSET_8 = 8,
			  PAYLOAD_OFFSET_14 = 14;		
	}

?>
