# Copilot Instructions for Accessibility Checker

## Project Overview

This is a WordPress plugin called "Accessibility Checker" developed by Equalize Digital. The plugin provides in-post accessibility scanning and guidance to help users audit their websites for accessibility compliance before and after publishing content. It focuses on WCAG (Web Content Accessibility Guidelines) compliance and does not require any API or per-page fees.

**Key Details:**
- Plugin Name: Accessibility Checker
- Minimum PHP: 7.4
- Text Domain: accessibility-checker
- Namespace: EqualizeDigital\AccessibilityChecker

## Architecture & Structure

### Core Plugin Structure
- **Main file**: `accessibility-checker.php` - Plugin bootstrap and constants
- **Admin classes**: Located in `/admin/` directory with class-based architecture
- **Core functionality**: Located in `/includes/` directory
- **Frontend assets**: Built files in `/build/`, source files in `/src/`
- **Language files**: Located in `/languages/` directory

### Coding Standards
- Follow WordPress coding standards (WPCS) in all php files
- Use PSR-4 autoloading with EqualizeDigital\AccessibilityChecker namespace
- Legacy files are autoloaded using EDAC namespace.
- Class names use `ClassNameConvention` (CamelCase)
- Legacy class names use `Class_Name_Convention` (WordPress style)
- File names use `ClassNameConvention.php` for new classes (CamelCase)
- Legacy file names use `class-class-name.php` (WordPress style)
- Use WordPress hooks and filters appropriately
- Use `edac_` prefix for custom hooks and filters

### PHP Guidelines
- Minimum PHP 7.4 compatibility
- Use type hints where appropriate
- Follow WordPress security best practices (sanitization, validation, nonces)
- Use WordPress database API (wpdb) for database operations
- Prefix all functions and classes with `edac_` when in global namespace

### WordPress Integration
- Use WordPress hooks (actions/filters) for extensibility
- Follow WordPress plugin development best practices
- Implement proper activation/deactivation hooks
- Use WordPress transients for caching
- Follow WordPress internationalization (i18n) practices

## Development Workflow

Commit lock files (`composer.lock`, `package-lock.json`) only when adding or updating packages. Run `composer install` and `npm install` to get dependencies matching the lock file.

Code should always be linted by phpcs and eslint before committing. Tests should be added for new functionality. Tests should also be added for any bug fixes. Use the following commands to run tests and linting:

```bash
npm run lint:php
npm run lint:js
npm run test:php
npm run test:jest
```

There are autofixers available for both PHP and JavaScript linting. Use the following commands to fix linting issues:

```bash
npm run lint:php:fix
npm run lint:js:fix
```

### Complete Command Reference

#### Testing
- `npm run test:jest` - Run JavaScript tests
- `npm run test:php` - Setup and run PHP tests with Docker
- `npm run test:php:run` - Run PHP tests in existing container
- `npm run test:php:coverage` - PHP unit tests with coverage report
- `composer test` - Run PHP tests directly
- `docker-compose exec phpunit ./vendor/bin/phpunit ./tests/phpunit/path/to/TestFile.php` - Run a single PHPUnit test file (container must be running first)

#### Linting & Code Quality
- `npm run lint` - Run all linters (PHP + JS)
- `npm run lint:php` - Run PHP linter (PHPCS)
- `npm run lint:js` - Run JavaScript linter (ESLint)
- `npm run lint:php:fix` - Fix PHP linting issues
- `npm run lint:js:fix` - Fix JavaScript linting issues
- `composer lint` - Run PHP parallel lint
- `composer check-cs` - Check PHP code standards
- `composer fix-cs` - Fix PHP code standards

#### Building
- `npm run build` - Build production assets with webpack
- `npm run dev` - Build development assets with watch mode

### Docker Environment

PHP tests use Docker containers:
- **MySQL 5.7** (`db-phpunit`) - Database for WordPress testing
- **WordPress Testing Environment** (`phpunit`) - Custom container with WordPress + PHPUnit

### Troubleshooting Development Issues

- **Cypress Issues**: If Cypress download fails, run `npm config set ignore-scripts false && npm run postinstall`
- **Composer Timeouts**: Handle network timeouts gracefully with partial installations
- **Docker Issues**: Containers may need to be restarted if they fail to initialize properly
- **PHP Tests**: WordPress installation in Docker may occasionally need retry

### Code Quality Tools
- **PHP CodeSniffer**: Configuration in `phpcs.xml`
- **PHPStan**: Configuration in `phpstan.neon`
- **PHPUnit**: Test configuration in `phpunit.xml.dist`
- **ESLint**: JavaScript linting with `.eslintrc`

### Build Process
- **Webpack**: Frontend asset compilation via `webpack.config.js`
- **Composer**: PHP dependency management
- **NPM**: JavaScript dependency management

### Testing
- PHPUnit tests located in `/tests/phpunit` directory
- Jest tests for JavaScript in `/tests/jest/` directory.
- Use `npm run test:jest` for JavaScript tests
- Use `npm run test:php` for PHP unit tests
- Use `npm run test:php:coverage` for PHP unit tests with coverage report
- Use WordPress testing framework where applicable

## Accessibility Focus

This plugin is specifically designed for accessibility auditing, so when contributing:

### WCAG Compliance
- Follow WCAG 2.1 AA guidelines in all code
- Ensure proper semantic HTML structure
- Implement proper ARIA attributes
- Maintain keyboard navigation support
- Ensure proper color contrast

### Accessibility Patterns
- Implement proper focus management
- Provide alternative text for images
- Use proper heading hierarchy
- Ensure screen reader compatibility

## Key Components

### Scanner Engine
- Automated accessibility rule checking
- WCAG guideline implementation
- Issue detection and reporting
- Powered by axe-core library with custom rules

### Admin Interface
- Settings pages with accessibility considerations
- Meta boxes for post-level scanning
- Dashboard widgets and statistics
- Welcome pages and upgrade promotions

### Frontend Features
- Highlight accessibility issues on frontend
- User-facing accessibility fixes
- Statement generation capabilities

## Dependencies & Libraries

### PHP Dependencies (Composer)
- WordPress coding standards
- PHPStan for static analysis
- Custom forked textstatistics library

### JavaScript Dependencies (NPM)
- Webpack for bundling
- ESLint for code quality
- Various build tools and utilities

## File Naming Conventions

### PHP Files
- Classes: `ClassName.php`
- Legacy Classes: `class-class-name.php`
- Functions: `functions-purpose.php`
- Includes: `purpose.php`

### JavaScript Files
- Components: `ComponentName.js`
- Utilities: `utilityName.js`
- Bundles: `bundleName.bundle.js`

### CSS Files
- Stylesheets: `style-name.css`
- Admin styles: `admin-purpose.css`

## Database & Performance

- Use WordPress database API exclusively
- Implement proper caching strategies
- Consider performance impact at all times
- Use WordPress transients for temporary data storage where appropriate

## Internationalization

- All user-facing text must be translatable
- Use `accessibility-checker` text domain
- Follow WordPress i18n best practices
- Support RTL languages where applicable
- Strings in javascript also need translation support using `wp.i18n` functions

## Security Considerations

- Sanitize all user inputs
- Validate and escape all outputs
- Use WordPress nonces for form submissions
- Follow WordPress security guidelines
- Implement proper capability checks

## Plugin Hooks & Filters

When adding new functionality:
- Provide appropriate hooks for extensibility
- Use descriptive hook names with `edac_` prefix
- Document all custom hooks in docblocks
- Consider backward compatibility


## When Working on This Codebase

1. **Always consider accessibility implications** of any changes
2. **Test with screen readers** when modifying frontend components
3. **Follow WordPress plugin guidelines** strictly
4. **Maintain backward compatibility** with existing installations
5. **Document any new features** thoroughly
6. **Write tests** for new functionality
7. **Consider performance impact** of any changes

## Common Patterns

### Class Structure
```php
namespace EqualizeDigital\AccessibilityChecker;

class ExampleClass {
    public function __construct() {
        // never add actions right in the constructor
    }

    public function init() {
        // Implementation
    }
}
```

### Hook Implementation
```php
// Add filter with proper priority
add_filter( 'edac_custom_filter', [ $this, 'filter_callback' ], 10, 2 );

// Use descriptive hook names
do_action( 'edac_after_scan_complete', $post_id, $results );
```

### Error Handling, Code Review & Collaboration, and Performance Optimization
- Use WordPress error handling functions (e.g., WP_Error) for PHP errors.
- Log errors and warnings in a way that does not expose sensitive information.
- Gracefully handle JavaScript errors to avoid breaking accessibility features.
- Submit pull requests with clear descriptions and testing instructions.
- Tag accessibility-related changes for focused review.
- Use GitHub Actions workflows for automated linting and testing.
- Minimize DOM operations in JavaScript for frontend scanning.
- Avoid blocking queries in PHP, especially during scans.
- Profile and optimize accessibility scans for large posts/pages.

### Accessibility Testing Tools
- Use axe-core browser extension for manual accessibility checks.
- Test with multiple screen readers (NVDA, JAWS, VoiceOver).
- Validate color contrast with tools like Color Contrast Analyzer.

### Release Management
- Follow semantic versioning for plugin releases.
- Update changelog.txt and readme.txt for each release.
- Tag releases in GitHub and WordPress.org.

## Additional Guidance for Copilot and Contributors

### Documentation
- Use PHPDoc for all public classes, methods, and properties.
- Document custom hooks and filters with clear descriptions and parameter types.
- Update README.md and changelog.txt for any user-facing or API changes.
- Add inline comments for complex accessibility logic or non-obvious code.

### Testing Best Practices
- Write unit tests for new PHP functions and classes.
- Add integration tests for major features and accessibility rules.
- Use Jest for JavaScript unit tests and axe-core for accessibility assertions.
- Test with multiple browsers and assistive technologies.

### Accessibility-First Development
- Prioritize accessibility in UI/UX decisions and code reviews.
- Avoid introducing overlays or solutions that mask real accessibility issues.
- Prefer native HTML elements over custom widgets unless necessary.
- Ensure all interactive elements are reachable and usable by keyboard.

### Performance & Scalability
- Profile accessibility scans for large posts and optimize queries.
- Use batch processing for bulk scans to avoid timeouts.
- Cache scan results where possible, but always allow for manual refresh.

### Security & Privacy
- Never log or expose sensitive user data in error messages or debug output.
- Validate all AJAX requests and REST endpoints with nonces and capability checks.
- Escape all output, especially in admin screens and user-generated content.

### Release & Maintenance
- Use semantic versioning and tag releases in both GitHub and WordPress.org.
- Deprecate legacy code with clear docblocks and migration notes.
- Monitor for new WCAG updates and update rules as needed.

---

## Accessibility Issue Types (for reference)
- Color contrast
- Missing or incorrect alt text
- Improper heading structure
- ARIA misuse or missing attributes
- Keyboard navigation issues
- Form labeling and instructions
- Link purpose and context
- Dynamic content updates (ARIA live regions)

---

## Quick Reference: Accessibility Checklist
- Semantic HTML structure
- ARIA attributes used correctly
- Keyboard navigation supported
- Sufficient color contrast
- Focus management implemented
- Images have descriptive alt text
- Heading hierarchy logical
- Screen reader compatibility
- Forms are accessible and labeled
- No accessibility regressions introduced

---

## Resources

### Documentation & Guidelines
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [axe-core Documentation](https://github.com/dequelabs/axe-core)
- [WordPress Accessibility Coding Standards](https://make.wordpress.org/core/handbook/best-practices/accessibility/)

### Testing & Development Tools
- [WAVE Web Accessibility Evaluation Tool](https://wave.webaim.org/)
- [Deque axe DevTools](https://www.deque.com/axe/devtools/)
- [Color Contrast Analyzer](https://developer.paciellogroup.com/resources/contrastanalyser/)

### Screen Readers
- [NVDA Screen Reader](https://www.nvaccess.org/)
- [JAWS Screen Reader](https://www.freedomscientific.com/products/software/jaws/)
- [VoiceOver (macOS/iOS)](https://www.apple.com/accessibility/)

This plugin helps make the web more accessible - keep that mission in mind with every contribution!
