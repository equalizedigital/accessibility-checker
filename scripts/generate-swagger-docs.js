#!/usr/bin/env node

/**
 * Generate Swagger/OpenAPI documentation for the Issues API
 * 
 * This script creates:
 * 1. OpenAPI specification JSON file
 * 2. Swagger UI HTML documentation page
 * 
 * Usage: node scripts/generate-swagger-docs.js
 */

const fs = require('fs');
const path = require('path');

// Ensure docs/swagger directory exists
const docsDir = path.join(__dirname, '..', 'docs', 'swagger');
if (!fs.existsSync(docsDir)) {
    fs.mkdirSync(docsDir, { recursive: true });
}

// OpenAPI specification for Issues API
const swaggerSpec = {
    "openapi": "3.0.0",
    "info": {
        "title": "Accessibility Checker Issues API",
        "description": "REST API for managing accessibility issues detected by the Accessibility Checker plugin",
        "version": "1.0.0",
        "contact": {
            "name": "Equalize Digital",
            "url": "https://equalizedigital.com"
        }
    },
    "servers": [
        {
            "url": "/wp-json/accessibility-checker/v1",
            "description": "Accessibility Checker API Server"
        }
    ],
    "security": [
        {
            "wpNonce": []
        },
        {
            "edacToken": []
        }
    ],
    "components": {
        "securitySchemes": {
            "wpNonce": {
                "type": "apiKey",
                "in": "header",
                "name": "X-WP-Nonce",
                "description": "WordPress nonce for authentication"
            },
            "edacToken": {
                "type": "apiKey",
                "in": "header",
                "name": "X-EDAD-Token",
                "description": "EDAC API token for authentication"
            }
        },
        "schemas": {
            "Issue": {
                "type": "object",
                "required": ["id", "postid", "siteid", "type", "rule", "ruletype", "object", "recordcheck", "created", "user"],
                "properties": {
                    "id": {
                        "type": "integer",
                        "description": "Unique identifier for the issue"
                    },
                    "postid": {
                        "type": "integer",
                        "description": "ID of the post containing the issue"
                    },
                    "siteid": {
                        "type": "integer",
                        "description": "Site ID in multisite installations"
                    },
                    "type": {
                        "type": "string",
                        "description": "Post type (post, page, etc.)"
                    },
                    "rule": {
                        "type": "string",
                        "description": "Accessibility rule that was violated"
                    },
                    "ruletype": {
                        "type": "string",
                        "enum": ["error", "warning", "contrast"],
                        "description": "Severity level of the issue"
                    },
                    "object": {
                        "type": "string",
                        "description": "HTML element or content that caused the issue"
                    },
                    "recordcheck": {
                        "type": "integer",
                        "description": "Check status (0 = current, 1 = needs review)"
                    },
                    "created": {
                        "type": "string",
                        "format": "date-time",
                        "description": "When the issue was first detected"
                    },
                    "user": {
                        "type": "integer",
                        "description": "User ID who reported or owns the issue"
                    }
                }
            },
            "IssueInput": {
                "type": "object",
                "required": ["postid", "type", "rule", "ruletype", "object"],
                "properties": {
                    "postid": {
                        "type": "integer",
                        "description": "ID of the post containing the issue"
                    },
                    "type": {
                        "type": "string",
                        "description": "Post type (post, page, etc.)"
                    },
                    "rule": {
                        "type": "string",
                        "description": "Accessibility rule that was violated"
                    },
                    "ruletype": {
                        "type": "string",
                        "enum": ["error", "warning", "contrast"],
                        "description": "Severity level of the issue"
                    },
                    "object": {
                        "type": "string",
                        "description": "HTML element or content that caused the issue"
                    },
                    "user": {
                        "type": "integer",
                        "description": "User ID who reported the issue"
                    }
                }
            },
            "Error": {
                "type": "object",
                "properties": {
                    "code": {
                        "type": "string",
                        "description": "Error code"
                    },
                    "message": {
                        "type": "string",
                        "description": "Error message"
                    },
                    "data": {
                        "type": "object",
                        "properties": {
                            "status": {
                                "type": "integer",
                                "description": "HTTP status code"
                            }
                        }
                    }
                }
            },
            "SuccessResponse": {
                "type": "object",
                "properties": {
                    "success": {
                        "type": "boolean",
                        "description": "Whether the operation was successful"
                    },
                    "message": {
                        "type": "string",
                        "description": "Success message"
                    }
                }
            },
            "CountResponse": {
                "type": "object",
                "properties": {
                    "count": {
                        "type": "integer",
                        "description": "Total number of issues"
                    }
                }
            },
            "AccessCheckResponse": {
                "type": "object",
                "properties": {
                    "access": {
                        "type": "boolean",
                        "description": "Whether user has access to the API"
                    },
                    "message": {
                        "type": "string",
                        "description": "Access status message"
                    }
                }
            }
        },
        "parameters": {
            "perPage": {
                "name": "per_page",
                "in": "query",
                "description": "Number of issues to return per page",
                "schema": {
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 100,
                    "default": 10
                }
            },
            "page": {
                "name": "page",
                "in": "query",
                "description": "Page number for pagination",
                "schema": {
                    "type": "integer",
                    "minimum": 1,
                    "default": 1
                }
            },
            "offset": {
                "name": "offset",
                "in": "query",
                "description": "Number of issues to skip",
                "schema": {
                    "type": "integer",
                    "minimum": 0,
                    "default": 0
                }
            },
            "orderby": {
                "name": "orderby",
                "in": "query",
                "description": "Field to order results by",
                "schema": {
                    "type": "string",
                    "enum": ["id", "postid", "created", "rule", "ruletype"],
                    "default": "id"
                }
            },
            "order": {
                "name": "order",
                "in": "query",
                "description": "Order direction",
                "schema": {
                    "type": "string",
                    "enum": ["asc", "desc"],
                    "default": "desc"
                }
            },
            "postid": {
                "name": "postid",
                "in": "query",
                "description": "Filter by post ID",
                "schema": {
                    "type": "integer"
                }
            },
            "type": {
                "name": "type",
                "in": "query",
                "description": "Filter by post type",
                "schema": {
                    "type": "string"
                }
            },
            "rule": {
                "name": "rule",
                "in": "query",
                "description": "Filter by accessibility rule",
                "schema": {
                    "type": "string"
                }
            },
            "ruletype": {
                "name": "ruletype",
                "in": "query",
                "description": "Filter by rule severity",
                "schema": {
                    "type": "string",
                    "enum": ["error", "warning", "contrast"]
                }
            },
            "recordcheck": {
                "name": "recordcheck",
                "in": "query",
                "description": "Filter by check status",
                "schema": {
                    "type": "integer",
                    "enum": [0, 1]
                }
            },
            "issueId": {
                "name": "id",
                "in": "path",
                "required": true,
                "description": "Issue ID",
                "schema": {
                    "type": "integer"
                }
            }
        }
    },
    "paths": {
        "/issues": {
            "get": {
                "summary": "Retrieve accessibility issues",
                "description": "Get a paginated list of accessibility issues with optional filtering",
                "tags": ["Issues"],
                "parameters": [
                    { "$ref": "#/components/parameters/perPage" },
                    { "$ref": "#/components/parameters/page" },
                    { "$ref": "#/components/parameters/offset" },
                    { "$ref": "#/components/parameters/orderby" },
                    { "$ref": "#/components/parameters/order" },
                    { "$ref": "#/components/parameters/postid" },
                    { "$ref": "#/components/parameters/type" },
                    { "$ref": "#/components/parameters/rule" },
                    { "$ref": "#/components/parameters/ruletype" },
                    { "$ref": "#/components/parameters/recordcheck" }
                ],
                "responses": {
                    "200": {
                        "description": "Successful response",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "type": "array",
                                    "items": {
                                        "$ref": "#/components/schemas/Issue"
                                    }
                                }
                            }
                        },
                        "headers": {
                            "X-WP-Total": {
                                "description": "Total number of issues",
                                "schema": {
                                    "type": "integer"
                                }
                            },
                            "X-WP-TotalPages": {
                                "description": "Total number of pages",
                                "schema": {
                                    "type": "integer"
                                }
                            }
                        }
                    },
                    "401": {
                        "description": "Unauthorized",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    },
                    "500": {
                        "description": "Internal server error",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    }
                }
            },
            "post": {
                "summary": "Create a new accessibility issue",
                "description": "Add a new accessibility issue to the database",
                "tags": ["Issues"],
                "requestBody": {
                    "required": true,
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/IssueInput"
                            }
                        }
                    }
                },
                "responses": {
                    "201": {
                        "description": "Issue created successfully",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Issue"
                                }
                            }
                        }
                    },
                    "400": {
                        "description": "Bad request",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    },
                    "401": {
                        "description": "Unauthorized",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    }
                }
            }
        },
        "/issues/{id}": {
            "get": {
                "summary": "Get a specific issue",
                "description": "Retrieve details of a single accessibility issue by ID",
                "tags": ["Issues"],
                "parameters": [
                    { "$ref": "#/components/parameters/issueId" }
                ],
                "responses": {
                    "200": {
                        "description": "Successful response",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Issue"
                                }
                            }
                        }
                    },
                    "404": {
                        "description": "Issue not found",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    },
                    "401": {
                        "description": "Unauthorized",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    }
                }
            },
            "put": {
                "summary": "Update an issue",
                "description": "Update an existing accessibility issue",
                "tags": ["Issues"],
                "parameters": [
                    { "$ref": "#/components/parameters/issueId" }
                ],
                "requestBody": {
                    "required": true,
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/IssueInput"
                            }
                        }
                    }
                },
                "responses": {
                    "200": {
                        "description": "Issue updated successfully",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Issue"
                                }
                            }
                        }
                    },
                    "404": {
                        "description": "Issue not found",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    },
                    "401": {
                        "description": "Unauthorized",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    }
                }
            },
            "delete": {
                "summary": "Delete an issue",
                "description": "Remove an accessibility issue from the database",
                "tags": ["Issues"],
                "parameters": [
                    { "$ref": "#/components/parameters/issueId" }
                ],
                "responses": {
                    "200": {
                        "description": "Issue deleted successfully",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/SuccessResponse"
                                }
                            }
                        }
                    },
                    "404": {
                        "description": "Issue not found",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    },
                    "401": {
                        "description": "Unauthorized",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    }
                }
            }
        },
        "/issues/access-check": {
            "get": {
                "summary": "Check API access permissions",
                "description": "Verify if the current user has access to the Issues API",
                "tags": ["Issues"],
                "responses": {
                    "200": {
                        "description": "Access check response",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/AccessCheckResponse"
                                }
                            }
                        }
                    }
                }
            }
        },
        "/issues/count": {
            "get": {
                "summary": "Get total issue count",
                "description": "Get the total number of accessibility issues",
                "tags": ["Issues"],
                "parameters": [
                    { "$ref": "#/components/parameters/postid" },
                    { "$ref": "#/components/parameters/type" },
                    { "$ref": "#/components/parameters/rule" },
                    { "$ref": "#/components/parameters/ruletype" },
                    { "$ref": "#/components/parameters/recordcheck" }
                ],
                "responses": {
                    "200": {
                        "description": "Successful response",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/CountResponse"
                                }
                            }
                        }
                    },
                    "401": {
                        "description": "Unauthorized",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "$ref": "#/components/schemas/Error"
                                }
                            }
                        }
                    }
                }
            }
        }
    },
    "tags": [
        {
            "name": "Issues",
            "description": "Accessibility issues management"
        }
    ]
};

// Generate swagger.json
const swaggerJsonPath = path.join(docsDir, 'swagger.json');
fs.writeFileSync(swaggerJsonPath, JSON.stringify(swaggerSpec, null, 2));

// Generate index.html for Swagger UI
const swaggerHtml = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessibility Checker Issues API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            background-color: #1e3a8a;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: #fff;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "./swagger.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>`;

const indexHtmlPath = path.join(docsDir, 'index.html');
fs.writeFileSync(indexHtmlPath, swaggerHtml);

// Generate README
const readmeContent = `# Swagger Documentation for Accessibility Checker Issues API

This directory contains auto-generated OpenAPI/Swagger documentation for the Accessibility Checker Issues API.

## Files

- \`swagger.json\` - OpenAPI 3.0 specification
- \`index.html\` - Interactive Swagger UI documentation

## Usage

1. **Generate documentation**: Run \`npm run docs:swagger\` to generate these files
2. **View documentation**: Open \`index.html\` in a browser for interactive API documentation
3. **API specification**: Use \`swagger.json\` for importing into tools like Postman or generating SDKs

## Endpoints Documented

- \`GET /issues\` - Retrieve paginated collection of accessibility issues
- \`POST /issues\` - Create new accessibility issues
- \`GET /issues/{id}\` - Get specific issue details  
- \`PUT /issues/{id}\` - Update existing issues
- \`DELETE /issues/{id}\` - Delete issues
- \`GET /issues/access-check\` - Verify API access permissions
- \`GET /issues/count\` - Get total issue count

## Authentication

The API supports two authentication methods:
- WordPress nonce authentication (X-WP-Nonce header)
- EDAC token authentication (X-EDAD-Token header)

## Integration Examples

### JavaScript (fetch)
\`\`\`javascript
// Get issues with nonce authentication
const response = await fetch('/wp-json/accessibility-checker/v1/issues', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
const issues = await response.json();
\`\`\`

### cURL
\`\`\`bash
# Get issues count
curl -X GET \\
  -H "X-WP-Nonce: your-nonce-here" \\
  "/wp-json/accessibility-checker/v1/issues/count"
\`\`\`

---

*This documentation is auto-generated. Do not edit these files manually.*
`;

const readmePath = path.join(docsDir, 'README.md');
fs.writeFileSync(readmePath, readmeContent);

console.log('✅ Swagger documentation generated successfully!');
console.log(`📁 Files created in: ${docsDir}`);
console.log('📖 Open docs/swagger/index.html to view the documentation');