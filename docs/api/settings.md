[<-- Back to Index](../api_documentation.md)

# Application Settings Management

Endpoints for administrators to manage application-wide settings.

**Note:** All endpoints in this section require the `manage settings` permission.

### 1. List Settings

-   **Method:** `GET`
-   **Endpoint:** `/api/settings`
-   **Description:** Retrieves a list of all settings. Can be filtered by group.
-   **Headers:** `Authorization: Bearer <token>`
-   **Query Parameters:**
    | Parameter | Type | Description |
    |---|---|---|
    | `group` | string | Optional. The group to filter settings by (e.g., "zoom"). |
-   **Success Response (200):** An array of setting objects.

### 2. Known Setting Groups & Payloads

While the settings API is flexible, the application expects certain keys for specific functionality:

#### Zoom Configuration

-   **Group:** `zoom`
-   **Payload Structure:**
    ```json
    {
        "client_id": "Required. The OAuth client ID from Zoom Marketplace.",
        "client_secret": "Required. The OAuth client secret.",
        "account_id": "Required. The Account ID.",
        "host_key": "Optional. Helper key for claiming host privileges."
    }
    ```

### 3. Create a Setting

-   **Method:** `POST`
-   **Endpoint:** `/api/settings`
-   **Description:** Creates a new setting.
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload Parameters:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `name` | string | required, unique | The unique name for the setting (e.g., "Default Zoom Account"). |
    | `group` | string | sometimes | The group for the setting (e.g., "zoom"). |
    | `payload` | object | required | A JSON object containing the setting data. |

-   **Example Payload for a Zoom Account:**
    ```json
    {
        "name": "Default Zoom Account",
        "group": "zoom",
        "payload": {
            "client_id": "YOUR_ZOOM_CLIENT_ID",
            "client_secret": "YOUR_ZOOM_CLIENT_SECRET",
            "account_id": "YOUR_ZOOM_ACCOUNT_ID",
            "host_key": "YOUR_ZOOM_HOST_KEY"
        }
    }
    ```
-   **Success Response (201):** Returns the newly created setting object.

### 4. Get a Specific Setting

-   **Method:** `GET`
-   **Endpoint:** `/api/settings/{id}`
-   **Description:** Retrieves a single setting by its ID.
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):** Returns the setting object.

### 5. Update a Setting

-   **Method:** `PUT` or `PATCH`
-   **Endpoint:** `/api/settings/{id}`
-   **Description:** Updates an existing setting.
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload Parameters:** (All are optional)
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `name` | string | sometimes, unique | The unique name for the setting. |
    | `group` | string | sometimes | The group for the setting. |
    | `payload` | object | sometimes | A JSON object containing the setting data. |
-   **Success Response (200):** Returns the updated setting object.

### 6. Delete a Setting

-   **Method:** `DELETE`
-   **Endpoint:** `/api/settings/{id}`
-   **Description:** Deletes a setting.
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):** `{"message": "Setting deleted successfully."}`

---

## Common Error Responses

-   **401 Unauthorized:** The request is missing a valid authentication token.
-   **403 Forbidden:** The authenticated user does not have the `manage settings` permission.
-   **404 Not Found:** The requested setting does not exist.
-   **422 Unprocessable Entity:** The request payload contains validation errors (e.g., the setting name already exists).
-   **500 Internal Server Error:** A generic error indicating a problem on the server.
