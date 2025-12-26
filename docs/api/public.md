[<-- Back to Index](../api_documentation.md)

# Public Endpoints

This section describes endpoints that are publicly accessible and do not require authentication.

---

### 1. Get Public Meetings for Calendar

-   **Method:** `GET`
-   **Endpoint:** `/api/public/calendar`
-   **Description:** Retrieves a list of meetings within a specific date range, formatted for public display. This endpoint only returns a safe subset of meeting data.

-   **Query Parameters:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `start_date` | date | required | The start of the date range (e.g., `2025-08-01`). |
    | `end_date` | date | required | The end of the date range (e.g., `2025-08-31`). |

-   **Success Response (200):** A collection of public-safe meeting objects.
    ```json
    {
        "data": [
            {
                "id": 1,
                "topic": "Public Town Hall",
                "description": "A meeting open to the public.",
                "start_time": "2025-08-15T10:00:00.000000Z",
                "duration": 90,
                "type": "hybrid",
                "location": {
                    "name": "City Hall",
                    "address": "123 Public Square",
                    "room_name": "Main Auditorium"
                }
            }
        ]
    }
    ```

### 2. Submit Attendance (Check-in)

-   **Method:** `POST`
-   **Endpoint:** `/api/public/meetings/{uuid}/attendance`
-   **Description:** Allows a guest/participant to check in to a meeting using its UUID.
-   **Payload:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `name` | string | required | Full name of the participant. |
    | `email` | string | required, email | Email address. |
    | `agency` | string | required | Instance/Agency name. |
    | `signature` | string (base64) | required | Base64 encoded signature image. |
-   **Success Response (201):** `{"message": "Check-in successful."}`

---

## Common Error Responses

-   **422 Unprocessable Entity:** The request payload contains validation errors (e.g., `start_date` is missing or invalid).
-   **500 Internal Server Error:** A generic error indicating a problem on the server.
