# A powerful FilamentPHP 3.x plugin that replaces static navigation with a database-driven menu system. Manage your panel navigation through a visual interface without touching code.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ranjeet/filament-panel-menu-manager.svg?style=flat-square)](https://packagist.org/packages/ranjeet/filament-panel-menu-manager)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ranjeet/filament-panel-menu-manager/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ranjeet/filament-panel-menu-manager/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ranjeet/filament-panel-menu-manager/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ranjeet/filament-panel-menu-manager/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ranjeet/filament-panel-menu-manager.svg?style=flat-square)](https://packagist.org/packages/ranjeet/filament-panel-menu-manager)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require ranjeet/filament-panel-menu-manager
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-panel-menu-manager-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-panel-menu-manager-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-panel-menu-manager-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentPanelMenuManager = new Ranjeet\FilamentPanelMenuManager();
echo $filamentPanelMenuManager->echoPhrase('Hello, Ranjeet!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ranjeet Mali](https://github.com/ranjeetmali)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
