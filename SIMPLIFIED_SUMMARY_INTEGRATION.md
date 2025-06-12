# Simplified Summary Integration Components

This document outlines the three integration methods created for displaying simplified summaries from the Accessibility Checker plugin.

## Components Created

### 1. WordPress Gutenberg Block
**File**: `includes/classes/class-simplified-summary-block.php`
**JavaScript**: `src/blocks/simplified-summary-block.js`
**CSS**: `src/blocks/simplified-summary.scss`

**Usage in Editor**:
- Search for "Simplified Summary" in the block inserter
- Add the block to your content
- Configure options in the sidebar:
  - Show/hide heading
  - Set heading level (H1-H6)
  - Custom heading text
  - Post ID (0 for current post)

**Block Name**: `accessibility-checker/simplified-summary`

### 2. WordPress Shortcodes
**File**: `includes/classes/class-simplified-summary-shortcode.php`

**Available Shortcodes**:
- `[accessibility_summary]` - Main shortcode
- `[edac_summary]` - Alias shortcode

**Shortcode Parameters**:
- `post_id` - ID of the post (default: current post)
- `show_heading` - Display heading (true/false, default: true)
- `heading_level` - Heading level 1-6 (default: 2)
- `custom_heading` - Custom heading text (default: "Simplified Summary")
- `class` - Additional CSS classes

**Usage Examples**:
```
[accessibility_summary]
[accessibility_summary post_id="123"]
[accessibility_summary show_heading="false"]
[accessibility_summary heading_level="3" custom_heading="Easy Summary"]
[edac_summary class="my-custom-class"]
```

### 3. Elementor Widget
**File**: `includes/classes/class-simplified-summary-elementor-widget.php`

**Widget Name**: "Accessibility Summary"
**Category**: General

**Available Controls**:
- **Content Tab**:
  - Post ID selection
  - Show/hide heading toggle
  - Heading level dropdown
  - Custom heading text input

- **Style Tab**:
  - **Heading Style**:
    - Color picker
    - Typography controls
    - Text shadow
    - Margin controls
  - **Content Style**:
    - Text color
    - Typography controls
    - Margin controls
  - **Container Style**:
    - Background color
    - Padding controls
    - Margin controls
    - Border controls
    - Border radius

## Integration Manager
**File**: `includes/classes/class-simplified-summary-integrations.php`

This class coordinates all integration components and provides:
- Centralized initialization
- Admin documentation page
- Usage examples and documentation

**Admin Page**: Available under "Accessibility Checker" > "Simplified Summary Integration"

## CSS Styling
**File**: `src/blocks/simplified-summary.scss` (compiled to `build/css/simplified-summary-block.css`)

**Features**:
- Responsive design
- Accessibility-focused styling
- High contrast mode support
- Reduced motion support
- Print-friendly styles
- Elementor-specific adjustments

**CSS Classes**:
- `.edac-simplified-summary` - Main container
- `.wp-block-accessibility-checker-simplified-summary` - Block wrapper
- `.elementor-widget-edac_simplified_summary` - Elementor widget wrapper

## Installation and Setup

### Prerequisites
- WordPress 5.0+ (for Gutenberg blocks)
- Elementor Pro (optional, for Elementor widget)
- Accessibility Checker plugin activated

### Automatic Setup
The components are automatically initialized when the plugin loads. No additional setup required.

### Manual Testing
1. **Block**: Go to any post/page editor and search for "Simplified Summary"
2. **Shortcode**: Add `[accessibility_summary]` to any post content
3. **Elementor**: Look for "Accessibility Summary" widget in Elementor editor

## File Structure
```
includes/classes/
├── class-simplified-summary-block.php
├── class-simplified-summary-shortcode.php
├── class-simplified-summary-elementor-widget.php
└── class-simplified-summary-integrations.php

src/blocks/
├── simplified-summary-block.js
└── simplified-summary.scss

build/
├── simplified-summary-block.bundle.js
└── css/simplified-summary-block.css
```

## Technical Notes

### Block Registration
- Uses server-side rendering for better SEO and caching
- Includes editor controls for all options
- Supports wide and full alignment
- Supports spacing controls (margin/padding)

### Shortcode Implementation
- Two shortcode aliases for flexibility
- Comprehensive attribute validation
- Integration with existing Simplified_Summary class
- Proper escaping and sanitization

### Elementor Integration
- Extends Elementor Widget_Base class
- Full styling controls for all elements
- Preview support in Elementor editor
- Responsive design controls

### Error Handling
- Graceful degradation when dependencies unavailable
- Fallback to current post ID when needed
- Proper validation of all user inputs
- Admin notices for configuration issues

## Support and Troubleshooting

### Common Issues
1. **Block not appearing**: Ensure WordPress 5.0+ and check for JavaScript errors
2. **Shortcode not working**: Verify plugin activation and shortcode syntax
3. **Elementor widget missing**: Confirm Elementor is active and up to date
4. **No output displayed**: Check if the post has a generated simplified summary

### Debug Mode
Enable WordPress debug mode to see any error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements
- Block patterns/templates
- Additional styling options
- Integration with page builders beyond Elementor
- REST API endpoint for external integrations
- Custom post type support filters
