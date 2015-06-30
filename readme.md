## Piction Client

Piction API for Laravel 5

### Development Setup
Add `imamuseum` folder to the root of your project.
Clone this repo into `imamuseum` folder.
Run `composer install` from inside `piction-client` folder to install vendor depen

### Composer Setup
Set up `psr-4` autoload in `composer.json`:
```json
"psr-4": {
    "App\\": "app/",
    "Imamuseum\\": "imamuseum/"
}
```

### Laravel Setup
Open `/config/app.php` add service provider to `providers` array:
```php
'providers' => [
	Imamuseum\PictionClient\PictionServiceProvider::class,
]
```

Add the alias:
```php
'aliases' => [
	'Piction'   => Imamuseum\PictionClient\PictionFacade::class,
]
```

## Configuration
Run `php artisan vendor:publish` and modify the config file:
```
/config/piction-client.php
```

### Environmental Variables Setup
Add Piction Variables to your `.env`:
```
PICTION_ENDPOINT_URL=null
PICTION_IMAGE_URL=null
PICTION_USERNAME=null
PICTION_PASSWORD=null
PICTION_FORMAT=null
PICTION_SURL=null
```
