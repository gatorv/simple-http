SimpleHttpRequest Class
================

Simple PHP Http Request Wrapper using cURL under the hood.

## Installation

```sh
$ php composer.phar require gatorv/simple-http
```

## Usage

Basic Usage

```php
use Gatorv\Web\SimpleHttpRequest as Request;

$req = new Request();
list($headers, $body) = $req->get('https://url/');
```

### Main Methods

Perform a GET Request
```php
$req->get($url); 
```

Perform a POST Request:
```php
$req->post($url, $data); 
```


## Methods

The following options can be customized on constructing the object (or after construction:
  1. redirects - The number of redirects to perform if a Location header is sent.
  1. proxy - The proxy and port to use
  1. ssl - Wether to verify the SSL certificate or not (for testing)

Also the following methods are available:

Use a Desktop User-Agent:
```php
$req->useDesktopAgent(); 
```

Use a Mobile User-Agent:
```php
$req->useMobileAgent(); 
```

Reset Headers:
```php
$req->resetHeaders(); 
```

Request Compression:
```php
$req->requestCompression(); 
```

Add a HTTP Cookie:
```php
$req->addCookie(); 
```

