[<-- Back to Index](../api_documentation.md)

# Zoom-Specific Management

**Note:** All endpoints in this section require the `edit meetings` permission.

### 1. Create a Zoom Meeting (Legacy)

-   **Method:** `POST`
-   **Endpoint:** `/api/zoom/meetings`
-   **Description:** A legacy endpoint for creating a purely online Zoom meeting. It functions as an alias for `POST /api/meetings` with the `type` set to `online`.
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `topic` | string | required, max:255 | The title or topic of the meeting. |
    | `start_time`| date | required | The meeting's start time. |
    | `duration` | integer | required, min:1 | The meeting's duration in minutes. |
    | `settings` | object | nullable | An object of Zoom-specific settings. |
-   **Success Response (201):** Returns the newly created meeting object with its relations.

### 2. Authenticate with Zoom

-   **Method:** `POST`
-   **Endpoint:** `/api/zoom/auth`
-   **Description:** Manually forces a re-authentication with the Zoom API to refresh the access token. This is handled automatically by the service, so manual calls are rarely needed.
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):**
    ```json
    {
        "message": "Zoom authentication successful."
    }
    ```

### 3. Get Zoom Meeting (List or Single)

-   **Method:** `GET`
-   **Endpoint:** `/api/zoom/meetings`
-   **Description:** Retrieves meeting data directly from Zoom.
-   **Headers:** `Authorization: Bearer <token>`
-   **To list all Zoom meetings:**
    -   **Endpoint:** `/api/zoom/meetings`
-   **To get a single Zoom meeting:**
    -   **Endpoint:** `/api/zoom/meetings?meetingId=85746065`
-   **Success Response (200):** Returns the raw JSON response from the Zoom API.

### 4. Update a Zoom Meeting

-   **Method:** `PATCH`
-   **Endpoint:** `/api/zoom/meetings?meetingId=85746065`
-   **Description:** Updates a meeting directly on Zoom and syncs the changes to the local database.
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload:** The body can contain any updatable field from the [Zoom API documentation for updating a meeting](https://developers.zoom.us/docs/api/rest/reference/zoom-api/methods/#operation/meetingUpdate). Common fields include `topic`, `duration`, `agenda`, and `settings`.
-   **Success Response (200):**
    ```json
    {
        "message": "Meeting updated successfully."
    }
    ```

### 5. Delete a Zoom Meeting

-   **Method:** `DELETE`
-   **Endpoint:** `/api/zoom/meetings?meetingId=85746065`
-   **Description:** Deletes a meeting from Zoom and the local database.
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):**
    ```json
    {
        "message": "Meeting deleted successfully."
    }
    ```

### 6. Get Meeting Summary

-   **Method:** `GET`
-   **Endpoint:** `/api/zoom/meetings/{meetingUuid}/summary`
-   **Description:** Retrieves the summary for a specific meeting. The `{meetingUuid}` must be the UUID of the meeting (e.g., `k9vd10Q2TE+R4emk4LGNig==`).
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):** Returns the raw summary JSON from the Zoom API.

### 7. Get Past Meeting Details

-   **Method:** `GET`
-   **Endpoint:** `/api/zoom/past_meetings?meetingId=85746065`
-   **Description:** Retrieves details for a past meeting instance.
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):** Returns the raw JSON from the Zoom API.

### 8. Sync Zoom Data

-   **Method:** `GET`
-   **Endpoint:** `/api/zoom/meetings/{meetingId}/sync`
-   **Description:** Forces a synchronization of Zoom data (status, recordings, summaries) to the local database. Useful if webhooks fail or to get the latest recording URL.
-   **Headers:** `Authorization: Bearer <token>`
-   **Success Response (200):**
    ```json
    {
        "message": "Zoom data synced successfully.",
        "data": { ... }
    }
    ```

---

## Common Error Responses

-   **401 Unauthorized:** The request is missing a valid authentication token.
-   **403 Forbidden:** The authenticated user does not have the `manage meetings` permission.
-   **422 Unprocessable Entity:** The request payload contains validation errors or the Zoom API returns an error.
-   **500 Internal Server Error:** A generic error indicating a problem on the server.
