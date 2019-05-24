# php ejabberd

- PHP Library for the ejabberd REST API

### Installation
```bash
composer require cielu/ejabberd
```

### Usage
```php
<?php

use Cielu\Ejabberd\EjabberdClient;

$ejabberd = new EjabberdClient([
   'baseUri' => 'http://localhost:5281' , // must use http or https
   'authorization' => "Bearer vmsTwBYFMJpRUOD8YvnyCdafEUxxxxx"
]);


```

### Examples
```php

// Register User
$res = $ejabberd->register('ciel','123456');

// create room
$res = $ejabberd->createRoom('room name');
```
