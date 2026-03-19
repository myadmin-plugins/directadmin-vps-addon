# DirectAdmin VPS Addon for MyAdmin

[![Tests](https://github.com/detain/myadmin-directadmin-vps-addon/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-directadmin-vps-addon/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-directadmin-vps-addon/version)](https://packagist.org/packages/detain/myadmin-directadmin-vps-addon)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-directadmin-vps-addon/downloads)](https://packagist.org/packages/detain/myadmin-directadmin-vps-addon)
[![License](https://poser.pugx.org/detain/myadmin-directadmin-vps-addon/license)](https://packagist.org/packages/detain/myadmin-directadmin-vps-addon)

A MyAdmin plugin that provides DirectAdmin control panel licensing as a VPS addon. This module enables automated provisioning and deprovisioning of DirectAdmin licenses for virtual private servers managed through the MyAdmin platform.

## Features

- Sells DirectAdmin licenses as an addon for VPS services
- Automated license activation and deactivation tied to the VPS lifecycle
- Configurable pricing through the MyAdmin admin settings panel
- Event-driven architecture using Symfony EventDispatcher

## Installation

```sh
composer require detain/myadmin-directadmin-vps-addon
```

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1. See the [LICENSE](LICENSE) file for details.
