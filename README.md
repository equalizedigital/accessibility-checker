[![CS](https://github.com/equalizedigital/accessibility-checker/actions/workflows/cs.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/cs.yml)
[![Lint](https://github.com/equalizedigital/accessibility-checker/actions/workflows/lint.yml/badge.svg)](https://github.com/equalizedigital/accessibility-checker/actions/workflows/lint.yml)
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

This plugin uses a modified version of wp-env to support loopback:
https://github.com/equalizedigital/accessibility-checker-wp-env

See also:
https://developer.wordpress.org/block-editor/getting-started/devenv/get-started-with-wp-env/

To install:
1. Install docker, node, npm, and composer
2. If using the Pro plugin add the license key to env.txt and rename as .env
3. run `npm install`

This will start a Dev server on localhost:8888 and a Tests server on localhost:8889.

By default, the Dev server maps these plugin folders if they exist:
```shell
./../accessibility-checker/
./../accessibility-checker-pro/
./../accessibility-checker-audit-history/
```

### Package scripts
- `npm start` - starts wp-env
- `npm stop` - stops wp-env
- `npm run hard-reset` - destroys and rebuilds wp-env
- `npm run build` - builds JavaScript & CSS
- `npm run dev` - watches and automatically builds JavaScript & CSS
- `npm run lint` - lints the plugin's PHP and JavaScript
- `npm run lint:php` - lints the plugin's PHP
- `npm run lint:php:fix` - fixes linting issues in the plugin's PHP
- `npm run lint:js` - lints the plugin's JavaScript
- `npm run lint:js:fix` - fixes linting issues in the plugin's JavaScript
- `npm run dist` - builds a distributable .zip for the plugin into ./dist
- `npm run wp:clean` - resets the wp database for the Dev server 
- `npm run wp:sql` - opens a my-sql cli for the Dev server database
- `test:php` - runs the plugin's PHP unit test
- `test:e2e` - runs the plugin's End-to-End test

## Support

This is a developer portal for Accessibility Checker and should not be used for support. Please visit the [support forums](https://wordpress.org/support/plugin/accessibility-checker/) for support.

## Contributions

Anyone is welcome to contribute to Accessibility Checker. Please [read the guidelines](.github/CONTRIBUTING.md) for contributing to this repository.

There are various ways you can contribute:

* [Raise an issue](https://github.com/equalizedigital/accessibility-checker/issues) on GitHub.
* Send us a Pull Request with your bug fixes and/or new features.
