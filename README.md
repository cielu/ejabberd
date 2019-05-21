# php ejabberd

- PHP Library for the ejabberd REST API

### Installation
```bash
composer require cielu/easy-ejabberd
```

### Usage
```php
<?php

use Cielu\Ejabberd;

$ejabberd = new Ejabberd();


```

### Examples
```php

// Register User
$user = 'test@example.com';
$ejabberd->register($user,$server,$password);

// Ban Account
$user = 'john@doe.com';
$reason = 'Acting too smart';
$ejabberd->banAccount($user, $reason);
```
