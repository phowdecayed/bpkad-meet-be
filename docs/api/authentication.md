[<-- Back to Index](../api_documentation.md)

# Authentication & User Management

This section covers endpoints for user registration, login, and general user account management.

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

## Common Error Responses

- **401 Unauthorized:** The request is missing a valid authentication token.
- **403 Forbidden:** The authenticated user does not have the required permissions to perform the action.
- **422 Unprocessable Entity:** The request payload contains validation errors (e.g., a required field is missing, an email is not unique). The response body will contain a detailed list of the errors.
- **500 Internal Server Error:** A generic error indicating a problem on the server.
