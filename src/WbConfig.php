<?php

    namespace WSS;
    
    interface WbConfig {
        const SERVER = '<YOUR_WEBSOCKET_SERVER_DOMIN_OR_IP>';   // eg. www.example.com
        const PORTOCOL = 'ssl://';                              // for security
        const PORT = '<YOUR_WEBSOCKET_SERVER_PORT>';            // eg. 12345
        const PEMFILE = "/path/folder/server.pem";              // pem file
        const LOCAL = '<YOUR_WEBSOCKET_CLIENT/HOST>';
    }

?>
