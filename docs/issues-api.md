# Issues API

This document outlines the functionality and usage of the Accessibility Checker Issues API. The API provides programmatic access to manage accessibility issues within your WordPress site.

**API Namespace:** `accessibility-checker/v1`
**Base URL:** `/wp-json/accessibility-checker/v1/issues`

## Authentication

All API endpoints require authentication. The requesting user must have the `manage_options` capability. Requests are validated using a token or nonce via `REST_Api::check_token_or_nonce_and_capability_permissions_check`. Ensure your requests include a valid WordPress nonce or are made by an authenticated user with the appropriate permissions.

## Common Response Object Structure

Many API calls that return issue data will include objects with the following structure:

| Field           | Type    | Description                                                                 |
|-----------------|---------|-----------------------------------------------------------------------------|
| `id`            | integer | The unique identifier for the issue.                                        |
| `postid`        | integer | The ID of the post associated with the issue.                               |
| `siteid`        | integer | The ID of the site (blog) where the issue occurred.                         |
| `type`          | string  | The type of issue (e.g., 'error', 'warning').                               |
| `rule`          | string  | The specific accessibility rule that was violated.                          |
| `ruletype`      | string  | The type of rule (e.g., 'EDAC', 'WCAG2AA').                                 |
| `object`        | string  | The HTML object or element that caused the issue.                           |
| `recordcheck`   | boolean | Indicates if the record was checked.                                        |
| `created`       | string  | Timestamp (YYYY-MM-DD HH:MM:SS) of when the issue was created.              |
| `user`          | integer | The user ID of the person who discovered or created the issue.              |
| `ignre`         | boolean | Whether the issue is ignored.                                               |
| `ignre_global`  | boolean | Whether the issue is ignored globally.                                      |
| `ignre_user`    | integer | User ID of the user who ignored the issue (if applicable).                  |
| `ignre_date`    | string  | Timestamp (YYYY-MM-DD HH:MM:SS) of when the issue was ignored (if applicable). |
| `ignre_comment` | string  | Comment provided when ignoring the issue (if applicable).                   |
| `post_title`    | string  | The title of the post associated with the issue.                            |
| `post_permalink`| string  | The permalink to the post associated with the issue.                        |
| `discoverer_username` | string | The display name of the user who discovered the issue.                 |
| `ignre_username`| string  | The display name of the user who ignored the issue (if applicable).         |
| `meta`          | object  | Contains metadata for the response.                                         |
| `meta.links`    | object  | Contains HATEOAS links (`self`, `collection`).                              |
| `meta.pagination`| object | Contains pagination details (`page`, `per_page`, `total_pages`).             |

## Error Responses

Errors are returned using standard WordPress `WP_Error` objects. Common error codes include:

*   `rest_issue_invalid_id`: Invalid issue ID provided. (Status: 404)
*   `rest_issue_delete_failed`: Failed to delete the issue. (Status: 500)
*   `rest_issue_invalid_fields`: Missing required fields for creation. (Status: 400)
*   `rest_issue_invalid_post`: Invalid post ID provided. (Status: 400)
*   Generic authentication/authorization errors if permissions are not met. (Status: 401 or 403)

## Endpoints

### GET /issues

Retrieves a paginated list of issues.

**Example Request:**
```
GET /wp-json/accessibility-checker/v1/issues?per_page=5&page=2
```

**Query Parameters:**

| Parameter | Type    | Default | Description                                          |
|-----------|---------|---------|------------------------------------------------------|
| `page`    | integer | 1       | Current page of the collection.                      |
| `per_page`| integer | 10      | Maximum number of items to be returned (max 500).    |
| `ids`     | array   | []      | Optional. An array of specific issue IDs to retrieve. |

**Response:**
*   Status: `200 OK`
*   Headers:
    *   `X-WP-Total`: Total number of issues.
    *   `X-WP-TotalPages`: Total number of pages.
*   Body: An array of issue objects (see Common Response Object Structure).

### POST /issues

Creates a new issue.

**Example Request:**
```json
POST /wp-json/accessibility-checker/v1/issues
{
  "postid": 1,
  "rule": "WCAG2AA.Principle1.Guideline1_4.1_4_3.G18.Fail",
  "ruletype": "WCAG2AA",
  "object": "<button>Submit</button>",
  "user": 1
}
```

**Request Body Parameters:**

| Parameter  | Type    | Required | Description                                      |
|------------|---------|----------|--------------------------------------------------|
| `postid`   | integer | Yes      | The ID of the post to associate the issue with.  |
| `rule`     | string  | Yes      | The specific accessibility rule violated.        |
| `ruletype` | string  | Yes      | The type of rule (e.g., 'EDAC', 'WCAG2AA').      |
| `object`   | string  | Yes      | The HTML snippet or identifier of the element.   |
| `user`     | integer | Yes      | The user ID to associate with creating the issue. |

**Response:**
*   Status: `201 Created` on success.
*   Body:
    ```json
    {
      "id": <inserted_issue_id>
    }
    ```
*   Status: `400 Bad Request` if required fields are missing or invalid (e.g., `rest_issue_invalid_fields`, `rest_issue_invalid_post`).

### GET /issues/{id}

Retrieves a specific issue by its ID.

**Example Request:**
```
GET /wp-json/accessibility-checker/v1/issues/123
```

**Path Parameters:**

| Parameter | Type    | Description          |
|-----------|---------|----------------------|
| `id`      | integer | The ID of the issue. |

**Response:**
*   Status: `200 OK`
*   Body: A single issue object (see Common Response Object Structure).
*   Status: `404 Not Found` if the issue ID is invalid (`rest_issue_invalid_id`).

### PUT /issues/{id}

Updates an existing issue.
**Note:** The current implementation of this endpoint maps to the `create_issue` method. This means it expects the same parameters as `POST /issues` and will attempt to create a new issue. The behavior for "updating" an existing issue with this endpoint is therefore not a standard PUT operation and its direct utility for updates is unclear. It effectively acts like `POST /issues` but with an ID in the URL (which is not typically used by the creation logic).

**Path Parameters:**

| Parameter | Type    | Description          |
|-----------|---------|----------------------|
| `id`      | integer | The ID of the issue. |

**Request Body Parameters:** (Same as `POST /issues`)

| Parameter  | Type    | Required | Description                                      |
|------------|---------|----------|--------------------------------------------------|
| `postid`   | integer | Yes      | The ID of the post to associate the issue with.  |
| `rule`     | string  | Yes      | The specific accessibility rule violated.        |
| `ruletype` | string  | Yes      | The type of rule (e.g., 'EDAC', 'WCAG2AA').      |
| `object`   | string  | Yes      | The HTML snippet or identifier of the element.   |
| `user`     | integer | Yes      | The user ID to associate with creating the issue. |

**Response:** (Same as `POST /issues`)
*   Status: `201 Created` if a new record is technically created by the underlying `create_issue` call.
*   Body:
    ```json
    {
      "id": <inserted_issue_id>
    }
    ```
*   Status: `400 Bad Request` if required fields are missing or invalid.

### DELETE /issues/{id}

Deletes an issue by its ID.

**Example Request:**
```
DELETE /wp-json/accessibility-checker/v1/issues/123
```

**Path Parameters:**

| Parameter | Type    | Description          |
|-----------|---------|----------------------|
| `id`      | integer | The ID of the issue. |

**Response:**
*   Status: `204 No Content` on successful deletion.
*   Body:
    ```json
    {
      "success": true
    }
    ```
*   Status: `404 Not Found` if the issue ID is invalid (`rest_issue_invalid_id`).
*   Status: `500 Internal Server Error` if deletion fails (`rest_issue_delete_failed`).

### GET /issues/access-check

Checks API accessibility and permissions. This is a simple endpoint to verify that the current user/token can interact with the Issues API.

**Example Request:**
```
GET /wp-json/accessibility-checker/v1/issues/access-check
```

**Response:**
*   Status: `200 OK`
*   Body:
    ```json
    {
      "success": true
    }
    ```

### GET /issues/count

Retrieves the count of issues, optionally filtered by a list of issue IDs.

**Example Request:**
```
GET /wp-json/accessibility-checker/v1/issues/count
```
or with specific IDs:
```
GET /wp-json/accessibility-checker/v1/issues/count?ids[]=1&ids[]=2&ids[]=5
```

**Query Parameters:**

| Parameter | Type  | Default | Description                                      |
|-----------|-------|---------|--------------------------------------------------|
| `ids`     | array | []      | Optional. An array of issue IDs to count. If empty, counts all issues. |

**Response:**
*   Status: `200 OK`
*   Body:
    ```json
    {
      "count": <number_of_issues>
    }
    ```
