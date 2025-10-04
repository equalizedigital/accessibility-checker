# Hook Documentation Generator

This document explains how to use and maintain the hooks documentation generator for the Accessibility Checker plugin.

## Overview

The hook documentation generator (`tools/generate-hooks-docs.php`) automatically scans the codebase for WordPress actions and filters defined by the plugin. It generates comprehensive documentation in Markdown format, which is saved to `docs/hooks.md`.

## Features

- Automatically finds all hooks prefixed with `edac_` or `edacp_`
- Extracts documentation from nearby PHPDoc blocks
- Generates usage examples for each hook
- Supports custom examples via `@example` tags in PHPDoc
- Filters out non-authoritative directories (tests, build, vendor, etc.)
- Includes hook type, parameters, return values, and source file information

## Running the Generator

To manually update the hook documentation:

```bash
cd /path/to/accessibility-checker
php tools/generate-hooks-docs.php
```

Alternatively, you can use the Composer script:

```bash
composer run generate-hooks-docs
```

Either command will scan the entire codebase and regenerate `docs/hooks.md`.

> **Note:** In most cases, you don't need to manually regenerate the documentation. The CI workflow will automatically create a PR to update the documentation when PHP files are changed in a pull request or when changes are pushed to the develop branch.

## Output Format

The generated documentation is organized into two main sections:
1. **Actions**: Hooks that do not return values but execute functionality
2. **Filters**: Hooks that modify and return values

For each hook, the documentation includes:

- Hook name
- Description (extracted from PHPDoc)
- File location and line number
- Since version (if available)
- Parameters with types and descriptions
- Return value information (for filters)
- Example usage code

## Documenting Hooks

For best results when defining hooks in the code:

### 1. Always add PHPDoc comments above hook definitions

```php
/**
 * Filters the post statuses that can be scanned.
 *
 * @since 1.5.0
 * @param array $statuses Array of post status names.
 * @return array Modified array of post status names.
 */
$scannable_post_statuses = apply_filters( 'edac_scannable_post_statuses', $statuses );
```

### 2. Use `@example` tags for custom examples

You can provide custom example code by adding an `@example` tag in the PHPDoc block:

```php
/**
 * Filters the types of elements that will be scanned.
 *
 * @since 1.8.0
 * @param array $element_types Array of element types.
 * @return array Modified array of element types.
 * 
 * @example
 * // Remove images from scanning
 * add_filter( 'edac_scannable_element_types', 'my_custom_element_types' );
 * 
 * function my_custom_element_types( $element_types ) {
 *     // Find and remove images from the element types
 *     $key = array_search( 'img', $element_types, true );
 *     if ( false !== $key ) {
 *         unset( $element_types[$key] );
 *     }
 *     return $element_types;
 * }
 */
$element_types = apply_filters( 'edac_scannable_element_types', $default_elements );
```

If no custom example is provided, the generator will create one based on the hook type, parameters, and return values.

## How It Works

The generator operates in multiple stages:

1. **Discovery**: Finds all `do_action`, `apply_filters`, `add_action`, and `add_filter` calls in the codebase
2. **Filtering**: Removes hooks that don't match the `edac_` or `edacp_` prefixes
3. **Documentation Extraction**: Finds nearby PHPDoc comments to extract descriptions, parameters, etc.
4. **Example Generation**: Creates examples based on hook type and parameters, or uses custom examples
5. **Markdown Generation**: Organizes everything into a structured Markdown document

## Maintenance

When updating the generator:

1. Ensure proper PHPDoc extraction remains intact
2. Test with hooks that have varied parameter counts and types
3. Check that custom examples are correctly extracted from docblocks
4. Verify filtering of non-authoritative directories works correctly

## CI Integration

The hook documentation is automatically regenerated whenever there are changes to PHP files in the codebase through the GitHub Actions workflow defined in `.github/workflows/verify-hooks-docs.yml`.

### Workflow Details

The CI workflow:

1. Triggers on:
   - Pull requests that change PHP files
   - Pushes to the `develop` branch

2. Performs the following steps:
   - Checks out the repository
   - Sets up PHP 8.0
   - Regenerates the hooks documentation by running `php tools/generate-hooks-docs.php`
   - Creates a new pull request if `docs/hooks.md` has changed

3. The automated PR will:
   - Have the title "chore: regenerate hooks docs"
   - Include a descriptive message indicating it was created by CI
   - Target the `develop` branch
   - Be authored by the GitHub Actions bot

This ensures that the documentation stays in sync with the codebase and developers don't need to manually update the hooks documentation when adding or changing hooks.

### Modifying the CI Workflow

The workflow file is located at `.github/workflows/verify-hooks-docs.yml`. If you need to modify how the documentation is generated or when the workflow is triggered:

## Best Practices

When adding new hooks to the plugin:

1. Always use the appropriate prefix (`edac_` or `edacp_`)
2. Add comprehensive PHPDoc comments above each hook
3. Include `@since` tags to indicate when hooks were added
4. Document all parameters and return values
5. Add custom examples for complex hooks using `@example` tags

### Handling Documentation Changes

When you add or modify hooks:

1. **Let CI Do the Work**: The automated workflow will detect your PHP changes and generate a PR to update the docs
2. **Review the Generated PR**: Make sure the auto-generated documentation accurately reflects your changes
3. **Manual Verification**: If needed, run the generator locally to preview changes before pushing

The CI workflow ensures that documentation remains accurate without requiring manual intervention, but it's always good practice to verify that your hooks are properly documented.