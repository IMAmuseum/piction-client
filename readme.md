## Piction Client

Piction API SDK

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

### TODO: Add private repository composer installation instructions.
