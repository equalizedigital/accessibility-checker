# Accessibility Checker Issues API Documentation

This directory contains the OpenAPI/Swagger documentation for the Accessibility Checker Issues API.

## Accessing the Documentation

### Live Documentation Site
The Swagger UI documentation is available at:
```
https://yoursite.com/wp-json/accessibility-checker/v1/docs
```

### OpenAPI Specification
The raw OpenAPI specification (swagger.json) is available at:
```
https://yoursite.com/wp-json/accessibility-checker/v1/swagger.json
```

## API Overview

The Issues API provides endpoints for managing accessibility issues detected by the Accessibility Checker plugin. All endpoints require authentication via WordPress nonce or EDAC API token.

### Available Endpoints

1. **GET /issues** - Get a paginated collection of issues
2. **POST /issues** - Create a new accessibility issue
3. **GET /issues/{id}** - Get a specific issue by ID
4. **PUT /issues/{id}** - Update an existing issue
5. **DELETE /issues/{id}** - Delete an issue
6. **GET /issues/access-check** - Verify API access
7. **GET /issues/count** - Get total count of issues

### Authentication

The API supports two authentication methods:

1. **WordPress Nonce** (recommended for browser requests)
   - Send nonce in `X-WP-Nonce` header
   - Get nonce from `wp.apiFetch.nonceMiddleware.nonce` in JavaScript

2. **EDAC API Token** (for external applications)
   - Send token in `X-EDAD-Token` header
   - Token authentication is currently disabled but can be enabled

### Example Usage

#### Get Issues (JavaScript)
```javascript
wp.apiFetch({
    path: '/accessibility-checker/v1/issues?per_page=20&page=1'
}).then(issues => {
    console.log('Issues:', issues);
});
```

#### Create Issue (JavaScript)
```javascript
wp.apiFetch({
    path: '/accessibility-checker/v1/issues',
    method: 'POST',
    data: {
        postid: 123,
        rule: 'missing_alt_text',
        ruletype: 'WCAG2AA',
        object: '<img src="image.jpg">',
        user: 1
    }
}).then(response => {
    console.log('Created issue:', response.id);
});
```

#### Get Issues (cURL)
```bash
curl -X GET "https://yoursite.com/wp-json/accessibility-checker/v1/issues" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE"
```

### Response Format

All successful responses return JSON data. Error responses follow the WordPress REST API error format:

```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "data": {
    "status": 400
  }
}
```

### Pagination

Collection endpoints (like GET /issues) support pagination:

- `page` - Current page number (default: 1)
- `per_page` - Items per page (default: 10, max: 500)

Pagination info is returned in response headers:
- `X-WP-Total` - Total number of items
- `X-WP-TotalPages` - Total number of pages

### Testing the API

You can test the API using:

1. **Swagger UI** - Interactive documentation at `/wp-json/accessibility-checker/v1/docs`
2. **WordPress REST API** - Built-in testing tools
3. **Postman** - Import the OpenAPI spec from `/wp-json/accessibility-checker/v1/swagger.json`
4. **cURL** - Command line testing

## Development

### Updating Documentation

When making changes to the Issues API:

1. Update the OpenAPI annotations in `/includes/classes/Rest/IssuesAPI.php`
2. Regenerate the swagger.json file
3. Test the updated documentation

### File Structure

```
docs/swagger/
├── index.html          # Static Swagger UI page
├── swagger.json        # OpenAPI 3.0 specification
└── README.md          # This documentation
```

The live documentation is served dynamically through WordPress REST API endpoints, not from these static files.