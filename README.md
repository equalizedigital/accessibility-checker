[![CS](https://github.com/equalizedigital/accessibility-checker/actions/workflows/cs.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/cs.yml)
[![Lint](https://github.com/equalizedigital/accessibility-checker/actions/workflows/lint-php.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/lint-php.yml)
[![Lint](https://github.com/equalizedigital/accessibility-checker/actions/workflows/lint-js.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/lint-js.yml)
[![Security](https://github.com/equalizedigital/accessibility-checker/actions/workflows/security.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/security.yml)
[![Test](https://github.com/equalizedigital/accessibility-checker/actions/workflows/phpunit.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/phpunit.yml)
[![Coverage Status](https://coveralls.io/repos/github/equalizedigital/accessibility-checker/badge.svg?branch=develop)](https://coveralls.io/github/equalizedigital/accessibility-checker?branch=develop)

# Equalize Digital Accessibility Checker

## What is this?
Audit and check your website for accessibility before you hit publish. In-post accessibility scanner and guidance for WCAG compliance. No API or per page fees.

* [Plugin Website](https://equalizedigital.com/accessibility-checker/)
* [Documentation](https://equalizedigital.com/accessibility-checker/documentation/)
* [Compare Free to Pro](https://equalizedigital.com/accessibility-checker/features/#comparison)
* [Get Pro](https://equalizedigital.com/accessibility-checker/pricing/)
* [WP Accessibility Meetup](https://equalizedigital.com/wordpress-accessibility-meetup/)
* [WP Accessibility Facebook Group](https://www.facebook.com/groups/wordpress.accessibility)

## Want to contribute?

### Prerequisites
At Equalize Digital, we make use of a specific toolset to develop our code. Please ensure you have the following tools installed before contributing.

* [Composer](https://getcomposer.org/)
* [NPM](https://www.npmjs.com/)

### Getting started

Check out this repository from GitHub, then run:

```shell
composer install
npm install
npm run build
```

### Dev environment setup

There are no real special requirements for the dev environment, use what ever local environment you prefer. Some suggestions are Local by Flywheel, DesktopServer, or LocalWP.

So long as you follow the getting started instructions above, you should be able to run the plugin in your local environment.

### Running tests

This plugin includes unit tests for the PHP code and Jest tests for the JavaScript code. The Jest tests have no pre-requisites, but the PHP tests require a local WordPress installation.

#### Jest tests

To run the Jest tests, you can use the following command:

```shell
npm run test:jest
```

#### PHP unit tests

The PHP tests are a little more involved, as they requre a local WordPress installation. A docker container setup is provided in the plugin to run this.

This should be started to run the tests and stopped when you are finished development.
To run the commands (will start the containers if not already running):

```shell
npm run test:php
```

To stop the PHP unit test container, run:

```shell
npm run test:php:stop
```

### Package scripts
- `npm run build` - builds JavaScript & CSS
- `npm run dev` - watches and automatically builds JavaScript & CSS
- `npm run dist` - builds a distributable .zip for the plugin into ./dist
- `npm run dist:dotorg` - builds a distributable .zip for dotorg, keeps build folder
- `npm run lint` - lints the plugin's PHP and JavaScript
- `npm run lint-staged-precommit` - runs lint-staged and JS lint for precommit
- `npm run lint:php` - lints the plugin's PHP
- `npm run lint:php:fix` - fixes linting issues in the plugin's PHP
- `npm run lint:js` - lints the plugin's JavaScript
- `npm run lint:js:fix` - fixes linting issues in the plugin's JavaScript
- `npm run test:php` - sets up PHP unit test
- `npm run test:php:run` - runs the plugin's PHP unit test
- `npm run test:php:coverage` - runs PHP unit test with coverage report
- `npm run test:php:stop` - stops the PHP unit test container
- `npm run test:jest` - runs Jest tests
- `npm run prepare` - runs husky
- `npm run phpstan` - runs PHPStan static analysis

## Support

This is a developer portal for Accessibility Checker and should not be used for support. Please visit the [support forums](https://wordpress.org/support/plugin/accessibility-checker/) for support.

## Contributions

Anyone is welcome to contribute to Accessibility Checker. Please [read the guidelines](.github/CONTRIBUTING.md) for contributing to this repository.

There are various ways you can contribute:

* [Raise an issue](https://github.com/equalizedigital/accessibility-checker/issues) on GitHub.
* Send us a Pull Request with your bug fixes and/or new features.
