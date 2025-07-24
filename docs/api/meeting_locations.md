[<-- Back to Index](../api_documentation.md)

# Meeting Location Management

**Note:** All endpoints in this section require the `edit meetings` permission.

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

## Common Error Responses

- **401 Unauthorized:** The request is missing a valid authentication token.
- **403 Forbidden:** The authenticated user does not have the `manage meetings` permission.
- **404 Not Found:** The requested meeting location does not exist.
- **422 Unprocessable Entity:** The request payload contains validation errors.
- **500 Internal Server Error:** A generic error indicating a problem on the server.
