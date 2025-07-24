# BPKAD Meeting API Documentation

This document provides a detailed overview of the API endpoints for managing online, offline, and hybrid meetings.

## Authentication & User Management

### 1. Register a New User (Admin Only)

- **Method:** `POST`
- **Endpoint:** `/api/register`
- **Description:** Creates a new user account with a specific role. This endpoint is for admin use only and requires the `manage users` permission.

- **Headers:** `Authorization: Bearer <token>`
- **Payload Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `name` | string | required, max:255 | The user's full name. |
| `email` | string | required, email, unique | The user's email address. Must be unique. |
| `password`| string | required, min:8, confirmed | The user's password. |
| `password_confirmation` | string | required | Must match the `password` field. |
| `role` | string | required, exists:roles,name | The name of the role to assign (e.g., "user", "admin"). |

- **Success Response (201):** Returns the newly created user object, formatted by the `UserResource`.
  ```json
  {
      "data": {
          "id": 4,
          "name": "New Staff Member",
          "email": "staff.member1@example.com",
          "created_at": "2025-07-24 02:30:00",
          "updated_at": "2025-07-24 02:30:00",
          "roles": [
              {
                  "id": 1,
                  "name": "user"
              }
          ]
      }
  }
  ```

### 2. Login

- **Method:** `POST`
- **Endpoint:** `/api/login`
- **Description:** Authenticates a user and returns an API token.

- **Payload Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `email` | string | required, email | The user's email address. |
| `password`| string | required | The user's password. |

- **Success Response (200):**
  ```json
  {
      "access_token": "2|yyyyyyyyyyyyyyyyyyyyyyyy",
      "token_type": "Bearer"
  }
  ```

### 3. Get Authenticated User

- **Method:** `GET`
- **Endpoint:** `/api/user`
- **Description:** Retrieves the details of the currently authenticated user, including their assigned roles and the permissions inherited through those roles. The response is formatted by the `UserResource`.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):**
  ```json
  {
    "data": {
        "id": 2,
        "name": "Example Admin",
        "email": "admin@example.com",
        "created_at": "2025-07-24 02:30:00",
        "updated_at": "2025-07-24 02:30:00",
        "roles": [
            {
                "id": 2,
                "name": "admin",
                "permissions": [
                    {
                        "id": 1,
                        "name": "manage meetings"
                    },
                    {
                        "id": 2,
                        "name": "delete meetings"
                    },
                    {
                        "id": 3,
                        "name": "manage users"
                    },
                    {
                        "id": 4,
                        "name": "manage roles"
                    }
                ]
            }
        ]
    }
}
  ```

### 4. Logout

- **Method:** `POST`
- **Endpoint:** `/api/logout`
- **Description:** Revokes the user's current API token.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):**
  ```json
  {
      "message": "Successfully logged out"
  }
  ```

### 5. Forgot Password

- **Method:** `POST`
- **Endpoint:** `/api/forgot-password`
- **Description:** Sends a password reset link to the user's email address.

- **Payload Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `email` | string | required, email | The user's email address. |

- **Success Response (200):**
  ```json
  {
    "message": "We have emailed your password reset link."
  }
  ```

### 6. Reset Password

- **Method:** `POST`
- **Endpoint:** `/api/reset-password`
- **Description:** Resets the user's password using the token from the reset email.

- **Payload Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `token` | string | required | The password reset token from the email. |
| `email` | string | required, email | The user's email address. |
| `password`| string | required, min:8, confirmed | The new password. |
| `password_confirmation` | string | required | Must match the `password` field. |

- **Success Response (200):**
  ```json
  {
    "message": "Your password has been reset."
  }
  ```

### 7. Verify Email Address

- **Method:** `GET`
- **Endpoint:** `/api/email/verify/{id}/{hash}`
- **Description:** This is the endpoint that the user clicks in their verification email. It is not typically called directly by a client.
- **Success Response:** Redirects to the frontend URL with a success message.

### 8. Resend Verification Email

- **Method:** `POST`
- **Endpoint:** `/api/email/verification-notification`
- **Description:** Resends the email verification link to the authenticated user.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (202):** Accepted.

---

## User Profile Management

Endpoints for authenticated users to manage their own profile.

### 1. Change Name

- **Method:** `POST`
- **Endpoint:** `/api/user/change-name`
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `name` | string | required, max:255 | The user's new full name. |
- **Success Response (200):** `{"message": "Name updated successfully."}`

### 2. Change Email

- **Method:** `POST`
- **Endpoint:** `/api/user/change-email`
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `email` | string | required, email, unique | The user's new email address. |
- **Success Response (200):** `{"message": "Email updated successfully."}`

### 3. Change Password

- **Method:** `POST`
- **Endpoint:** `/api/user/change-password`
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `current_password` | string | required | The user's current password. |
| `password` | string | required, min:8, confirmed | The new password. |
| `password_confirmation` | string | required | Confirmation of the new password. |
- **Success Response (200):** `{"message": "Password updated successfully."}`

---

## Admin: Role & Permission Management

Endpoints for administrators to manage roles and permissions.

### 1. List Roles

- **Method:** `GET`
- **Endpoint:** `/api/roles`
- **Description:** Retrieves a list of all roles and their assigned permissions. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** An array of role objects.

### 2. Create Role

- **Method:** `POST`
- **Endpoint:** `/api/roles`
- **Description:** Creates a new role. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `name` | string | required, unique | The name for the new role. |
- **Success Response (201):** Returns the new role object.

### 3. Assign Permission to Role

- **Method:** `POST`
- **Endpoint:** `/api/roles/{role}/permissions`
- **Description:** Assigns an existing permission to a role. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `permission` | string | required, exists:permissions,name | The name of the permission to assign. |
- **Success Response (200):** `{"message": "Permission assigned successfully."}`

### 4. Revoke Permission from Role

- **Method:** `DELETE`
- **Endpoint:** `/api/roles/{role}/permissions`
- **Description:** Revokes a permission from a role. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `permission` | string | required, exists:permissions,name | The name of the permission to revoke. |
- **Success Response (200):** `{"message": "Permission revoked successfully."}`

### 5. List Permissions

- **Method:** `GET`
- **Endpoint:** `/api/permissions`
- **Description:** Retrieves a list of all available permissions. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** An array of permission objects.

### 6. Delete Role

- **Method:** `DELETE`
- **Endpoint:** `/api/roles/{id}`
- **Description:** Deletes a specific role. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** `{"message": "Role deleted successfully."}`

---

## Meeting Location Management

**Note:** All endpoints in this section require the `manage meetings` permission.

### 1. List Locations

- **Method:** `GET`
- **Endpoint:** `/api/meeting-locations`
- **Description:** Retrieves a list of all meeting locations.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** An array of location objects.

### 2. Create a Location

- **Method:** `POST`
- **Endpoint:** `/api/meeting-locations`
- **Description:** Creates a new physical meeting location.
- **Headers:** `Authorization: Bearer <token>`

- **Payload Parameters:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `name` | string | required, max:255 | The name of the location (e.g., "Main Office"). |
| `address` | string | required, max:255 | The physical address of the location. |
| `room_name`| string | nullable, max:255 | The specific room name (e.g., "Conference Room A"). |
| `capacity` | integer | nullable, min:1 | The seating capacity of the room. |

- **Success Response (201):** Returns the newly created location object.

### 3. Get a Specific Location

- **Method:** `GET`
- **Endpoint:** `/api/meeting-locations/{id}`
- **Description:** Retrieves details for a single location.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** Returns the location object.

### 4. Update a Location

- **Method:** `PUT` or `PATCH`
- **Endpoint:** `/api/meeting-locations/{id}`
- **Description:** Updates an existing location's details.
- **Headers:** `Authorization: Bearer <token>`

- **Payload Parameters:** (All are optional)
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `name` | string | sometimes, required, max:255 | The name of the location. |
| `address` | string | sometimes, required, max:255 | The physical address. |
| `room_name`| string | nullable, max:255 | The specific room name. |
| `capacity` | integer | nullable, min:1 | The seating capacity. |

- **Success Response (200):** Returns the updated location object.

### 5. Delete a Location

- **Method:** `DELETE`
- **Endpoint:** `/api/meeting-locations/{id}`
- **Description:** Deletes a meeting location.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (204):** No content.

---

## Core Meeting Management

### 1. List All Meetings

- **Method:** `GET`
- **Endpoint:** `/api/meetings`
- **Description:** Retrieves a paginated list of all meetings. **Requires `manage meetings` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `page` | integer | The page number for pagination. |
- **Success Response (200):** A paginated list of meeting objects.

### 2. Create a Meeting

- **Method:** `POST`
- **Endpoint:** `/api/meetings`
- **Description:** Creates a new meeting. **Requires `manage meetings` permission.**
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
| `settings` | object | nullable | An object of Zoom-specific settings. See Zoom API docs. |

- **Success Response (201):** Returns the newly created meeting object with its relations.

### 3. Get a Specific Meeting

- **Method:** `GET`
- **Endpoint:** `/api/meetings/{id}`
- **Description:** Retrieves a single meeting by its primary ID. **Requires `manage meetings` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** Returns the full meeting object with relations.

### 4. Update a Meeting

- **Method:** `PUT` or `PATCH`
- **Endpoint:** `/api/meetings/{id}`
- **Description:** Updates a meeting's details. **Requires `manage meetings` permission.**
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

### 5. Delete a Meeting

- **Method:** `DELETE`
- **Endpoint:** `/api/meetings/{id}`
- **Description:** Deletes a meeting. **Requires `delete meetings` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):**
  ```json
  {
      "message": "Meeting deleted successfully."
  }
  ```

---

## Zoom-Specific Management

**Note:** All endpoints in this section require the `manage meetings` permission.

### 1. Create a Zoom Meeting (Legacy)

- **Method:** `POST`
- **Endpoint:** `/api/zoom/meetings`
- **Description:** A legacy endpoint for creating a purely online Zoom meeting. It functions as an alias for `POST /api/meetings` with the `type` set to `online`.
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `topic` | string | required, max:255 | The title or topic of the meeting. |
| `start_time`| date | required | The meeting's start time. |
| `duration` | integer | required, min:1 | The meeting's duration in minutes. |
| `settings` | object | nullable | An object of Zoom-specific settings. |
- **Success Response (201):** Returns the newly created meeting object with its relations.

### 2. Authenticate with Zoom

- **Method:** `POST`
- **Endpoint:** `/api/zoom/auth`
- **Description:** Manually forces a re-authentication with the Zoom API to refresh the access token. This is handled automatically by the service, so manual calls are rarely needed.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):**
  ```json
  {
      "message": "Zoom authentication successful."
  }
  ```

### 3. Get Zoom Meeting (List or Single)

- **Method:** `GET`
- **Endpoint:** `/api/zoom/meetings`
- **Description:** Retrieves meeting data directly from Zoom.
- **Headers:** `Authorization: Bearer <token>`
- **To list all Zoom meetings:**
  - **Endpoint:** `/api/zoom/meetings`
- **To get a single Zoom meeting:**
  - **Endpoint:** `/api/zoom/meetings?meetingId=85746065`
- **Success Response (200):** Returns the raw JSON response from the Zoom API.

### 4. Update a Zoom Meeting

- **Method:** `PATCH`
- **Endpoint:** `/api/zoom/meetings?meetingId=85746065`
- **Description:** Updates a meeting directly on Zoom and syncs the changes to the local database.
- **Headers:** `Authorization: Bearer <token>`
- **Payload:** The body can contain any updatable field from the [Zoom API documentation for updating a meeting](https://developers.zoom.us/docs/api/rest/reference/zoom-api/methods/#operation/meetingUpdate). Common fields include `topic`, `duration`, `agenda`, and `settings`.
- **Success Response (200):**
  ```json
  {
      "message": "Meeting updated successfully."
  }
  ```

### 5. Delete a Zoom Meeting

- **Method:** `DELETE`
- **Endpoint:** `/api/zoom/meetings?meetingId=85746065`
- **Description:** Deletes a meeting from Zoom and the local database.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):**
  ```json
  {
      "message": "Meeting deleted successfully."
  }
  ```

### 6. Get Meeting Summary

- **Method:** `GET`
- **Endpoint:** `/api/zoom/meetings/{meetingUuid}/summary`
- **Description:** Retrieves the summary for a specific meeting. The `{meetingUuid}` must be the UUID of the meeting (e.g., `k9vd10Q2TE+R4emk4LGNig==`).
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** Returns the raw summary JSON from the Zoom API.

### 7. Get Past Meeting Details

- **Method:** `GET`
- **Endpoint:** `/api/zoom/past_meetings?meetingId=85746065`
- **Description:** Retrieves details for a past meeting instance.
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** Returns the raw JSON from the Zoom API.