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
  require_once('/path/to/WebSocketServer.php');
  $myServer = new WSS\WebSocketServer;
```
###### Command
```bash
  $ php -q runServer.php 
```

#### Step3. Run WebSocket Client

###### Modify runClient.php
```php
  require_once('/path/to/WebSocketClient.php'); 
  
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

<br>
#### [OpenSSL changes in PHP 5.6.x](http://php.net/manual/en/migration56.openssl.php)
###### Stream wrappers now verify peer certificates and host names by default when using SSL/TLS
- Add openssl config in php.ini
```bash
  [openssl]
  openssl.cafile = "/path/to/server.pem"
  openssl.capath = "/path/to/"
```
- When you generate a server.pem, the CA's common name (CN) should be same as your server's domain name or IP.
