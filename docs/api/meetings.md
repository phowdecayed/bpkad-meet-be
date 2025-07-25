[<-- Back to Index](../api_documentation.md)

# Core Meeting Management

### 1. Get Meetings for Calendar

- **Method:** `GET`
- **Endpoint:** `/api/calendar`
- **Description:** Retrieves all meetings within a specific date range, suitable for a calendar view. **Requires `view meetings` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Query Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `start_date` | date | required | The start of the date range (e.g., `2025-08-01`). |
| `end_date` | date | required | The end of the date range (e.g., `2025-08-31`). |
- **Success Response (200):** A collection of meeting objects within the specified range.

### 2. List All Meetings (Paginated)

- **Method:** `GET`
- **Endpoint:** `/api/meetings`
- **Description:** Retrieves a paginated list of meetings. If the user has the `view meetings` permission, it returns all meetings. Otherwise, it returns only the meetings organized by the authenticated user.
- **Headers:** `Authorization: Bearer <token>`
- **Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `page` | integer | The page number for pagination. |
- **Success Response (200):** A paginated list of meeting objects.

### 3. Create a Meeting

- **Method:** `POST`
- **Endpoint:** `/api/meetings`
- **Description:** Creates a new meeting. **Requires `create meetings` permission.**
- **Headers:** `Authorization: Bearer <token>`

- **Payload Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `topic` | string | required, max:255 | The title or topic of the meeting. |
| `description` | string | nullable | A longer description of the meeting. |
| `start_time`| date | required | The meeting's start time in a valid date format (e.g., ISO 8601). |
| `duration` | integer | required, min:1 | The meeting's duration in minutes. |
| `type` | string | required, in:online,offline,hybrid | The type of meeting. |
| `location_id`| integer | required_if:type=offline,hybrid | The ID of a `MeetingLocation`. |
| `password` | string | nullable, max:10 | The meeting password (for online/hybrid meetings). |
| `settings` | object | nullable | An object of Zoom-specific settings. See Zoom API docs. |
| `participants` | array | nullable | An array of user IDs to invite to the meeting. |

- **Success Response (201):** Returns the newly created meeting object with its relations.

- **Example Success Response:**
  ```json
  {
    "data": {
        "id": 1,
        "organizer": {
            "id": 1,
            "name": "Example Admin",
            "email": "admin@example.com",
            "created_at": "2025-07-24T04:20:00Z",
            "updated_at": "2025-07-24T04:20:00Z",
            "roles": []
        },
        "topic": "New Online Meeting",
        "description": "A test meeting.",
        "start_time": "2025-08-01T10:00:00.000000Z",
        "duration": 60,
        "type": "online",
        "host_key": "123456",
        "location": null,
        "zoom_meeting": {
            "id": 1,
            "zoom_id": 123456789,
            "uuid": "abcdefg==",
            "..."
        }
    }
  }
  ```

### 4. Get a Specific Meeting

- **Method:** `GET`
- **Endpoint:** `/api/meetings/{id}`
- **Description:** Retrieves a single meeting by its primary ID. Requires the user to be the meeting organizer or have the `view meetings` permission.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** Returns the full meeting object with relations.

### 5. Update a Meeting

- **Method:** `PUT` or `PATCH`
- **Endpoint:** `/api/meetings/{id}`
- **Description:** Updates a meeting's details. Requires the user to be the meeting organizer or have the `edit meetings` permission.
- **Headers:** `Authorization: Bearer <token>`

- **Payload Parameters:** (All are optional)
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `topic` | string | sometimes, required, max:255 | The title of the meeting. |
| `description` | string | nullable | A longer description. |
| `start_time`| date | sometimes, required | The meeting's start time. |
| `duration` | integer | sometimes, required, min:1 | The meeting's duration in minutes. |
| `location_id`| integer | nullable, exists:meeting_locations,id | The ID of a `MeetingLocation`. |
| `settings` | object | nullable | An object of Zoom-specific settings. |

- **Success Response (200):** Returns the updated meeting object with relations.

### 6. Delete a Meeting

- **Method:** `DELETE`
- **Endpoint:** `/api/meetings/{id}`
- **Description:** Deletes a meeting. Requires the user to be the meeting organizer or have the `delete meetings` permission.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):**
  ```json
  {
      "message": "Meeting deleted successfully."
  }
  ```

### 7. List Participants

- **Method:** `GET`
- **Endpoint:** `/api/meetings/{id}/participants`
- **Description:** Retrieves a list of all users invited to a specific meeting. Requires the user to be the meeting organizer or have the `view meetings` permission.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** A collection of user objects.

### 8. Invite Participant

- **Method:** `POST`
- **Endpoint:** `/api/meetings/{id}/invite`
- **Description:** Invites a user to a meeting. Requires the user to be the meeting organizer or have the `edit meetings` permission.
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `user_id` | integer | required, exists:users,id | The ID of the user to invite. |
- **Success Response (200):** `{"message": "User invited successfully."}`

### 9. Remove Participant

- **Method:** `DELETE`
- **Endpoint:** `/api/meetings/{id}/participants/{userId}`
- **Description:** Removes a participant from a meeting. Requires the user to be the meeting organizer or have the `edit meetings` permission.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** `{"message": "Participant removed successfully."}`

---

## Common Error Responses

- **401 Unauthorized:** The request is missing a valid authentication token.
- **403 Forbidden:** The authenticated user does not have the required permissions.
- **404 Not Found:** The requested meeting does not exist.
- **422 Unprocessable Entity:** The request payload contains validation errors (e.g., a required field is missing, `start_date` is after `end_date`).
- **500 Internal Server Error:** A generic error indicating a problem on the server.
