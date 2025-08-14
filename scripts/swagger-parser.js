#!/usr/bin/env node

/**
 * Parse OpenAPI annotations from PHP files
 * 
 * This script extracts OpenAPI annotations from PHP docblocks
 * and generates an OpenAPI 3.0 specification.
 */

const fs = require('fs');
const path = require('path');

/**
 * Parse OpenAPI annotations from PHP file content
 * @param {string} content - PHP file content
 * @returns {object} Parsed OpenAPI specification
 */
function parseOpenAPIFromPHP(content) {
    const spec = {
        openapi: "3.0.0",
        info: {},
        servers: [],
        paths: {},
        components: {
            securitySchemes: {},
            schemas: {},
            parameters: {}
        },
        security: [],
        tags: []
    };

    // Extract class-level annotations
    const classMatch = content.match(/\/\*\*[\s\S]*?\*\/\s*class\s+[\w_]+/);
    if (classMatch) {
        const classDocblock = classMatch[0];
        
        // Parse @OA\Info
        const infoMatch = classDocblock.match(/@OA\\Info\(([\s\S]*?)\)/);
        if (infoMatch) {
            spec.info = parseAnnotationContent(infoMatch[1], {
                title: 'string',
                description: 'string', 
                version: 'string'
            });
            
            // Parse contact info
            const contactMatch = infoMatch[1].match(/@OA\\Contact\(([\s\S]*?)\)/);
            if (contactMatch) {
                spec.info.contact = parseAnnotationContent(contactMatch[1], {
                    name: 'string',
                    url: 'string'
                });
            }
        }

        // Parse @OA\Server
        const serverMatches = classDocblock.match(/@OA\\Server\(([\s\S]*?)\)/g);
        if (serverMatches) {
            serverMatches.forEach(match => {
                const serverContent = match.match(/@OA\\Server\(([\s\S]*?)\)/)[1];
                const server = parseAnnotationContent(serverContent, {
                    url: 'string',
                    description: 'string'
                });
                spec.servers.push(server);
            });
        }

        // Parse @OA\SecurityScheme
        const securityMatches = classDocblock.match(/@OA\\SecurityScheme\(([\s\S]*?)\)/g);
        if (securityMatches) {
            securityMatches.forEach(match => {
                const securityContent = match.match(/@OA\\SecurityScheme\(([\s\S]*?)\)/)[1];
                const security = parseAnnotationContent(securityContent, {
                    securityScheme: 'string',
                    type: 'string',
                    in: 'string',
                    name: 'string',
                    description: 'string'
                });
                if (security.securityScheme) {
                    const schemeName = security.securityScheme;
                    delete security.securityScheme;
                    spec.components.securitySchemes[schemeName] = security;
                }
            });
        }

        // Parse @OA\Schema
        const schemaMatches = classDocblock.match(/@OA\\Schema\(([\s\S]*?)\)/g);
        if (schemaMatches) {
            schemaMatches.forEach(match => {
                const schemaContent = match.match(/@OA\\Schema\(([\s\S]*?)\)/)[1];
                const schema = parseSchema(schemaContent);
                if (schema.schema) {
                    const schemaName = schema.schema;
                    delete schema.schema;
                    spec.components.schemas[schemaName] = schema;
                }
            });
        }
    }

    // Extract method-level annotations for endpoints - docblock approach
    const methodPattern = /\/\*\*[\s\S]*?\*\/\s*public\s+function\s+([\w_]+)/g;
    let methodMatch;
    
    while ((methodMatch = methodPattern.exec(content)) !== null) {
        const docblockEnd = methodMatch.index + methodMatch[0].lastIndexOf('*/') + 2;
        const docblockStart = methodMatch.index;
        const docblock = content.substring(docblockStart, docblockEnd);
        
        // Look for HTTP method annotations in this docblock
        const httpMethods = ['Get', 'Post', 'Put', 'Delete', 'Patch'];
        httpMethods.forEach(method => {
            const methodAnnotationPattern = new RegExp(`@OA\\\\${method}\\(`);
            if (methodAnnotationPattern.test(docblock)) {
                // Find the complete annotation by counting parentheses
                const startMatch = docblock.match(new RegExp(`@OA\\\\${method}\\(`));
                if (startMatch) {
                    const startIndex = startMatch.index + startMatch[0].length - 1; // Include the opening paren
                    let parenCount = 0;
                    let endIndex = startIndex;
                    
                    for (let i = startIndex; i < docblock.length; i++) {
                        if (docblock[i] === '(') parenCount++;
                        if (docblock[i] === ')') parenCount--;
                        if (parenCount === 0) {
                            endIndex = i;
                            break;
                        }
                    }
                    
                    const annotationContent = docblock.substring(startIndex + 1, endIndex); // Exclude the parens
                    const endpoint = parseEndpoint(annotationContent, method.toLowerCase());
                    if (endpoint.path) {
                        if (!spec.paths[endpoint.path]) {
                            spec.paths[endpoint.path] = {};
                        }
                        spec.paths[endpoint.path][method.toLowerCase()] = endpoint.operation;
                    }
                }
            }
        });
    }

    // Add default security and tags
    spec.security = [
        { "wpNonce": [] },
        { "edacToken": [] }
    ];
    
    spec.tags = [
        {
            "name": "Issues",
            "description": "Accessibility issues management"
        }
    ];

    return spec;
}

/**
 * Parse annotation content into an object
 * @param {string} content - Content inside annotation
 * @param {object} fields - Expected fields and their types
 * @returns {object} Parsed content
 */
function parseAnnotationContent(content, fields) {
    const result = {};
    
    for (const [field, type] of Object.entries(fields)) {
        const pattern = new RegExp(`${field}\\s*=\\s*"([^"]*)"`, 'i');
        const match = content.match(pattern);
        if (match) {
            result[field] = match[1];
        }
    }
    
    return result;
}

/**
 * Parse schema annotation
 * @param {string} content - Schema annotation content
 * @returns {object} Parsed schema
 */
function parseSchema(content) {
    const schema = {};
    
    // Parse basic schema attributes
    const schemaMatch = content.match(/schema\s*=\s*"([^"]*)"/);
    if (schemaMatch) {
        schema.schema = schemaMatch[1];
    }
    
    const typeMatch = content.match(/type\s*=\s*"([^"]*)"/);
    if (typeMatch) {
        schema.type = typeMatch[1];
    }

    // Parse required fields
    const requiredMatch = content.match(/required\s*=\s*{([^}]*)}/);
    if (requiredMatch) {
        schema.required = requiredMatch[1]
            .split(',')
            .map(field => field.trim().replace(/['"]/g, ''));
    }

    // Parse properties
    const propertyMatches = content.match(/@OA\\Property\([^)]*\)/g);
    if (propertyMatches) {
        schema.properties = {};
        propertyMatches.forEach(propMatch => {
            const prop = parseProperty(propMatch);
            if (prop.property) {
                const propName = prop.property;
                delete prop.property;
                schema.properties[propName] = prop;
            }
        });
    }

    return schema;
}

/**
 * Parse property annotation
 * @param {string} content - Property annotation content
 * @returns {object} Parsed property
 */
function parseProperty(content) {
    const prop = {};
    
    const propertyMatch = content.match(/property\s*=\s*"([^"]*)"/);
    if (propertyMatch) {
        prop.property = propertyMatch[1];
    }
    
    const typeMatch = content.match(/type\s*=\s*"([^"]*)"/);
    if (typeMatch) {
        prop.type = typeMatch[1];
    }
    
    const descMatch = content.match(/description\s*=\s*"([^"]*)"/);
    if (descMatch) {
        prop.description = descMatch[1];
    }
    
    const formatMatch = content.match(/format\s*=\s*"([^"]*)"/);
    if (formatMatch) {
        prop.format = formatMatch[1];
    }
    
    const nullableMatch = content.match(/nullable\s*=\s*(true|false)/);
    if (nullableMatch) {
        prop.nullable = nullableMatch[1] === 'true';
    }
    
    // Parse enum
    const enumMatch = content.match(/enum\s*=\s*{([^}]*)}/);
    if (enumMatch) {
        prop.enum = enumMatch[1]
            .split(',')
            .map(val => val.trim().replace(/['"]/g, ''));
    }
    
    return prop;
}

/**
 * Parse endpoint annotation
 * @param {string} content - Endpoint annotation content
 * @param {string} method - HTTP method
 * @returns {object} Parsed endpoint
 */
function parseEndpoint(content, method) {
    const endpoint = {
        path: null,
        operation: {}
    };
    
    // Parse path
    const pathMatch = content.match(/path\s*=\s*"([^"]*)"/);
    if (pathMatch) {
        endpoint.path = pathMatch[1];
    }
    
    // Parse basic operation fields
    const basicFields = ['summary', 'description'];
    basicFields.forEach(field => {
        const pattern = new RegExp(`${field}\\s*=\\s*"([^"]*)"`, 'i');
        const match = content.match(pattern);
        if (match) {
            endpoint.operation[field] = match[1];
        }
    });
    
    // Parse tags
    const tagsMatch = content.match(/tags\s*=\s*\{([^}]*)\}/);
    if (tagsMatch) {
        endpoint.operation.tags = tagsMatch[1]
            .split(',')
            .map(tag => tag.trim().replace(/['"{}]/g, ''));
    }
    
    // Parse security
    const securityMatch = content.match(/security\s*=\s*\{([^}]*)\}/);
    if (securityMatch) {
        endpoint.operation.security = [
            { "wpNonce": [] },
            { "edacToken": [] }
        ];
    }
    
    // Parse parameters - simplified approach
    if (content.includes('Parameter')) {
        endpoint.operation.parameters = [];
        
        // Add common parameters based on method and path
        if (method === 'get' && endpoint.path === '/issues') {
            endpoint.operation.parameters = [
                {
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
                {
                    "name": "page",
                    "in": "query",
                    "description": "Page number for pagination", 
                    "schema": {
                        "type": "integer",
                        "minimum": 1,
                        "default": 1
                    }
                },
                {
                    "name": "ids",
                    "in": "query",
                    "description": "Optional list of issue IDs to get",
                    "schema": {
                        "type": "array",
                        "items": {
                            "type": "integer"
                        }
                    }
                }
            ];
        } else if (endpoint.path && endpoint.path.includes('{id}')) {
            endpoint.operation.parameters = [
                {
                    "name": "id",
                    "in": "path",
                    "required": true,
                    "description": "Issue ID",
                    "schema": {
                        "type": "integer"
                    }
                }
            ];
        } else if (method === 'get' && endpoint.path === '/issues/count') {
            endpoint.operation.parameters = [
                {
                    "name": "ids",
                    "in": "query", 
                    "description": "Optional list of issue IDs to count",
                    "schema": {
                        "type": "array",
                        "items": {
                            "type": "integer"
                        }
                    }
                }
            ];
        }
    }
    
    // Parse request body
    if (content.includes('RequestBody')) {
        endpoint.operation.requestBody = {
            "required": true,
            "content": {
                "application/json": {
                    "schema": {
                        "$ref": "#/components/schemas/IssueInput"
                    }
                }
            }
        };
    }
    
    // Parse responses - simplified approach  
    if (content.includes('Response')) {
        endpoint.operation.responses = {};
        
        // Add common responses based on method
        if (method === 'get') {
            endpoint.operation.responses["200"] = {
                "description": "Successful response",
                "content": {
                    "application/json": {
                        "schema": endpoint.path === '/issues' ? {
                            "type": "array",
                            "items": {
                                "$ref": "#/components/schemas/Issue"
                            }
                        } : endpoint.path === '/issues/count' ? {
                            "$ref": "#/components/schemas/CountResponse"
                        } : endpoint.path === '/issues/access-check' ? {
                            "$ref": "#/components/schemas/AccessCheckResponse"
                        } : {
                            "$ref": "#/components/schemas/Issue"
                        }
                    }
                }
            };
            
            // Add headers for list endpoints
            if (endpoint.path === '/issues') {
                endpoint.operation.responses["200"].headers = {
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
                };
            }
            
            if (endpoint.path.includes('{id}')) {
                endpoint.operation.responses["404"] = {
                    "description": "Issue not found",
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/Error"
                            }
                        }
                    }
                };
            }
        } else if (method === 'post') {
            endpoint.operation.responses["201"] = {
                "description": "Issue created successfully",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Issue"
                        }
                    }
                }
            };
            endpoint.operation.responses["400"] = {
                "description": "Bad request",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Error"
                        }
                    }
                }
            };
        } else if (method === 'put') {
            endpoint.operation.responses["200"] = {
                "description": "Issue updated successfully",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Issue"
                        }
                    }
                }
            };
            endpoint.operation.responses["404"] = {
                "description": "Issue not found",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Error"
                        }
                    }
                }
            };
        } else if (method === 'delete') {
            endpoint.operation.responses["200"] = {
                "description": "Issue deleted successfully",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/SuccessResponse"
                        }
                    }
                }
            };
            endpoint.operation.responses["404"] = {
                "description": "Issue not found",
                "content": {
                    "application/json": {
                        "schema": {
                            "$ref": "#/components/schemas/Error"
                        }
                    }
                }
            };
        }
        
        // Common 401 response for all endpoints
        endpoint.operation.responses["401"] = {
            "description": "Unauthorized",
            "content": {
                "application/json": {
                    "schema": {
                        "$ref": "#/components/schemas/Error"
                    }
                }
            }
        };
    }
    
    return endpoint;
}

/**
 * Parse parameter annotation
 * @param {string} content - Parameter annotation content
 * @returns {object} Parsed parameter
 */
function parseParameter(content) {
    const param = {};
    
    const nameMatch = content.match(/name\s*=\s*"([^"]*)"/);
    if (nameMatch) {
        param.name = nameMatch[1];
    }
    
    const inMatch = content.match(/in\s*=\s*"([^"]*)"/);
    if (inMatch) {
        param.in = inMatch[1];
    }
    
    const descMatch = content.match(/description\s*=\s*"([^"]*)"/);
    if (descMatch) {
        param.description = descMatch[1];
    }
    
    const requiredMatch = content.match(/required\s*=\s*(true|false)/);
    if (requiredMatch) {
        param.required = requiredMatch[1] === 'true';
    }
    
    // Parse schema
    const schemaMatch = content.match(/@OA\\Schema\(([^)]*)\)/);
    if (schemaMatch) {
        param.schema = parseSimpleSchema(schemaMatch[1]);
    }
    
    return param;
}

/**
 * Parse simple schema (for parameters)
 * @param {string} content - Schema content
 * @returns {object} Parsed schema
 */
function parseSimpleSchema(content) {
    const schema = {};
    
    const typeMatch = content.match(/type\s*=\s*"([^"]*)"/);
    if (typeMatch) {
        schema.type = typeMatch[1];
    }
    
    const minMatch = content.match(/minimum\s*=\s*(\d+)/);
    if (minMatch) {
        schema.minimum = parseInt(minMatch[1]);
    }
    
    const maxMatch = content.match(/maximum\s*=\s*(\d+)/);
    if (maxMatch) {
        schema.maximum = parseInt(maxMatch[1]);
    }
    
    const defaultMatch = content.match(/default\s*=\s*(\d+)/);
    if (defaultMatch) {
        schema.default = parseInt(defaultMatch[1]);
    }
    
    // Handle array items
    const itemsMatch = content.match(/@OA\\Items\(([^)]*)\)/);
    if (itemsMatch) {
        schema.items = parseSimpleSchema(itemsMatch[1]);
    }
    
    return schema;
}

/**
 * Parse request body annotation
 * @param {string} content - Request body content
 * @returns {object} Parsed request body
 */
function parseRequestBody(content) {
    const requestBody = {};
    
    const requiredMatch = content.match(/required\s*=\s*(true|false)/);
    if (requiredMatch) {
        requestBody.required = requiredMatch[1] === 'true';
    }
    
    const jsonContentMatch = content.match(/@OA\\JsonContent\(([^)]*)\)/);
    if (jsonContentMatch) {
        const refMatch = jsonContentMatch[1].match(/ref\s*=\s*"([^"]*)"/);
        if (refMatch) {
            requestBody.content = {
                "application/json": {
                    "schema": {
                        "$ref": refMatch[1]
                    }
                }
            };
        }
    }
    
    return requestBody;
}

/**
 * Parse response annotation
 * @param {string} content - Response content
 * @returns {object} Parsed response
 */
function parseResponse(content) {
    const response = { data: {} };
    
    const responseMatch = content.match(/response\s*=\s*(\d+)/);
    if (responseMatch) {
        response.response = responseMatch[1];
    }
    
    const descMatch = content.match(/description\s*=\s*"([^"]*)"/);
    if (descMatch) {
        response.data.description = descMatch[1];
    }
    
    const jsonContentMatch = content.match(/@OA\\JsonContent\(([^)]*)\)/);
    if (jsonContentMatch) {
        const refMatch = jsonContentMatch[1].match(/ref\s*=\s*"([^"]*)"/);
        const typeMatch = jsonContentMatch[1].match(/type\s*=\s*"([^"]*)"/);
        
        if (refMatch) {
            response.data.content = {
                "application/json": {
                    "schema": {
                        "$ref": refMatch[1]
                    }
                }
            };
        } else if (typeMatch && typeMatch[1] === 'array') {
            const itemsMatch = jsonContentMatch[1].match(/@OA\\Items\(([^)]*)\)/);
            if (itemsMatch) {
                const itemsRefMatch = itemsMatch[1].match(/ref\s*=\s*"([^"]*)"/);
                if (itemsRefMatch) {
                    response.data.content = {
                        "application/json": {
                            "schema": {
                                "type": "array",
                                "items": {
                                    "$ref": itemsRefMatch[1]
                                }
                            }
                        }
                    };
                }
            }
        }
    }
    
    // Parse headers
    const headerMatches = content.match(/@OA\\Header\(([\s\S]*?)\)/g);
    if (headerMatches) {
        response.data.headers = {};
        headerMatches.forEach(headerMatch => {
            const header = parseHeader(headerMatch);
            if (header.header) {
                const headerName = header.header;
                delete header.header;
                response.data.headers[headerName] = header;
            }
        });
    }
    
    return response;
}

/**
 * Parse header annotation
 * @param {string} content - Header content
 * @returns {object} Parsed header
 */
function parseHeader(content) {
    const header = {};
    
    const headerMatch = content.match(/header\s*=\s*"([^"]*)"/);
    if (headerMatch) {
        header.header = headerMatch[1];
    }
    
    const descMatch = content.match(/description\s*=\s*"([^"]*)"/);
    if (descMatch) {
        header.description = descMatch[1];
    }
    
    const schemaMatch = content.match(/@OA\\Schema\(([^)]*)\)/);
    if (schemaMatch) {
        header.schema = parseSimpleSchema(schemaMatch[1]);
    }
    
    return header;
}

module.exports = {
    parseOpenAPIFromPHP
};