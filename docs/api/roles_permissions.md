[<-- Back to Index](../api_documentation.md)

# Admin: Role & Permission Management

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

### 3. Get a Specific Role

- **Method:** `GET`
- **Endpoint:** `/api/roles/{id}`
- **Description:** Retrieves a single role and its assigned permissions. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** Returns the role object.

### 4. Update a Role

- **Method:** `PUT` or `PATCH`
- **Endpoint:** `/api/roles/{id}`
- **Description:** Updates the name of an existing role. **Requires `manage roles` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Payload:**
| Parameter | Type | Validation | Description |
|---|---|---|---|
| `name` | string | required, unique | The new name for the role. |
- **Success Response (200):** Returns the updated role object.

### 5. Assign Permission to Role

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

## Common Error Responses

- **401 Unauthorized:** The request is missing a valid authentication token.
- **403 Forbidden:** The authenticated user does not have the `manage roles` permission.
- **404 Not Found:** The requested role or permission does not exist.
- **422 Unprocessable Entity:** The request payload contains validation errors (e.g., the role name already exists).
- **500 Internal Server Error:** A generic error indicating a problem on the server.
