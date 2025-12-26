[<-- Back to Index](../api_documentation.md)

# User Profile Management

Endpoints for authenticated users to manage their own profile.

### 1. Change Name

-   **Method:** `POST`
-   **Endpoint:** `/api/user/change-name`
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `name` | string | required, max:255 | The user's new full name. |
-   **Success Response (200):** `{"message": "Name updated successfully."}`

### 2. Change Email

-   **Method:** `POST`
-   **Endpoint:** `/api/user/change-email`
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `email` | string | required, email, unique | The user's new email address. |
-   **Success Response (200):** `{"message": "Email updated successfully."}`

### 3. Change Avatar

-   **Method:** `POST`
-   **Endpoint:** `/api/user/change-avatar`
-   **Headers:** `Authorization: Bearer <token>`, `Content-Type: multipart/form-data`
-   **Payload:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `avatar` | file | required, image, max:2048 | The new avatar image file (jpg, jpeg, png). |
-   **Success Response (200):**
    ```json
    {
        "message": "Avatar updated successfully.",
        "avatar_url": "http://localhost/storage/avatars/filename.jpg"
    }
    ```

### 4. Change Password

-   **Method:** `POST`
-   **Endpoint:** `/api/user/change-password`
-   **Headers:** `Authorization: Bearer <token>`
-   **Payload:**
    | Parameter | Type | Validation | Description |
    |---|---|---|---|
    | `current_password` | string | required | The user's current password. |
    | `password` | string | required, min:8, confirmed | The new password. |
    | `password_confirmation` | string | required | Confirmation of the new password. |
-   **Success Response (200):** `{"message": "Password updated successfully."}`

---

## Common Error Responses

-   **401 Unauthorized:** The request is missing a valid authentication token.
-   **422 Unprocessable Entity:** The request payload contains validation errors (e.g., the `current_password` is incorrect).
-   **500 Internal Server Error:** A generic error indicating a problem on the server.
