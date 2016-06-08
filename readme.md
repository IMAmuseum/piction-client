## Piction Client

This package will work independently of the Laravel Framework.

### Composer Setup
```sh
composer require imamuseum/piction-client
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

## Laravel Specific

### Service Provider
In `config\app.php` add to the autoloaded providers -
```php
Imamuseum\PictionClient\PictionServiceProvider::class,
```
### Publish Config
```php
php artisan vendor:publish
```
