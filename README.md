# php-websocket
- For push notification to web browser.

## How to use?

#### Step1. Set up WbConfig.php
  
```php
  interface WbConfig {
    const SERVER = '<YOUR_WEBSOCKET_SERVER_DOMIN_OR_IP>';   // eg. www.example.com
    const PORTOCOL = 'ssl://';                              // for security
    const PORT = '<YOUR_WEBSOCKET_SERVER_PORT>';            // eg. 12345
    const PEMFILE = "/path/folder/server.pem";              // pem file
    const LOCAL = '<YOUR_WEBSOCKET_CLIENT/HOST>';
  }
```

#### Step2. Run WebSocket Server

###### Modify runServer.php
```php  	
  require_once('/path/folder/WebSocketServer.php');
  $myServer = new WSS\WebSocketServer;
```
###### Command
```bash
  $ php -q runServer.php 
```

#### Step3. Run WebSocket Client

###### Modify runClient.php
```php
  require_once('/path/folder/WebSocketClient.php'); 
  
  $wb = new WSS\WebSocketClient;
  $data = '{"name": "XXX", "recevier": "XXX", "text": "XXX"}';  //data to be send
  $wbSocket = $wb->connection();
  
  if ($wbSocket !== -1) {
  	$result =  $wb->send($wbSocket, $data);
  }
  
  $wb->close($wbSocket);
```

###### Command
```bash
  $ php -q runClient.php
```
