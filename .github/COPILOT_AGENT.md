# Copilot Agent Environment

This repository includes a GitHub Actions workflow specifically designed for GitHub Copilot Agent environments, providing a pre-configured development setup with all dependencies and testing tools ready to use.

## Workflow: `.github/workflows/copilot-agent.yml`

### What it does:

1. **Environment Setup**: Configures Node.js 22 and PHP 8.1 environments
2. **Dependency Installation**: Installs and caches both npm and Composer dependencies
3. **Testing Framework**: Sets up Jest for JavaScript tests and Docker containers for PHP tests
4. **Build Tools**: Prepares webpack and all build tools
5. **Docker Integration**: Configures MySQL and WordPress testing environment

### Key Features:

- **Smart Caching**: Uses GitHub Actions cache for npm, Composer, and Docker images
- **Error Handling**: Gracefully handles network timeouts and installation issues
- **Testing Ready**: Runs Jest tests and prepares PHP test environment
- **Development Tools**: All linting, building, and testing commands available

### Available Commands:

#### Testing
- `npm run test:jest` - Run JavaScript tests (28 test suites, 484 tests)
- `npm run test:php` - Setup and run PHP tests with Docker
- `npm run test:php:run` - Run PHP tests in existing container

#### Linting & Code Quality
- `npm run lint` - Run all linters (PHP + JS)
- `npm run lint:php` - Run PHP linter (PHPCS)
- `npm run lint:js` - Run JavaScript linter (ESLint)
- `composer lint` - Run PHP parallel lint

#### Building
- `npm run build` - Build production assets with webpack
- `npm run dev` - Build development assets with watch mode

#### Direct PHP Commands
- `composer test` - Run PHP tests directly
- `composer check-cs` - Check PHP code standards
- `composer fix-cs` - Fix PHP code standards

### Docker Services:

The workflow sets up two Docker containers:
- **MySQL 5.7** (`db-phpunit`) - Database for WordPress testing
- **WordPress Testing Environment** (`phpunit`) - Custom container with WordPress + PHPUnit

### Usage in Copilot Agent:

When this workflow runs in a Copilot agent environment, it provides:
- Pre-installed development dependencies
- Cached packages for fast subsequent runs
- Ready-to-use testing environment
- Full build and development toolchain

### Manual Trigger:

The workflow can be manually triggered via GitHub Actions for testing or debugging purposes using the `workflow_dispatch` event.

### Troubleshooting:

- **Cypress Issues**: If Cypress download fails, run `npm config set ignore-scripts false && npm run postinstall`
- **Composer Timeouts**: The workflow handles network timeouts gracefully with partial installations
- **Docker Issues**: Containers are automatically restarted if they fail to initialize properly
- **PHP Tests**: WordPress installation in Docker may occasionally need retry

This setup ensures that Copilot agents have a fully functional development environment ready for immediate use with the Accessibility Checker WordPress plugin.