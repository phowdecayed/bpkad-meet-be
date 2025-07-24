[<-- Back to Index](../api_documentation.md)

# Statistics

### 1. Get Dashboard Statistics

- **Method:** `GET`
- **Endpoint:** `/api/statistics/dashboard`
- **Description:** Retrieves a cached set of key statistics for the application dashboard. **Requires `view meetings` permission.**
- **Headers:** `Authorization: Bearer <token>`
- **Success Response (200):** A JSON object containing various statistics.
  ```json
  {
    "data": {
      "overview": {
        "total_meetings": 142,
        "average_duration_minutes": 58,
        "meetings_this_month": 23
      },
      "meeting_trends": {
        "by_type": [
          { "type": "online", "count": 85 },
          { "type": "offline", "count": 45 },
          { "type": "hybrid", "count": 12 }
        ]
      },
      "leaderboards": {
        "top_organizers": [
          { "name": "Rachmat Sharyadi", "meetings_count": 35 },
          { "name": "Example Admin", "meetings_count": 28 }
        ],
        "top_locations": [
          { "name": "Main Office - Conference Room A", "meetings_count": 67 },
          { "name": "Branch Office - Room B", "meetings_count": 21 }
        ]
      }
    }
  }
  ```

---

## Common Error Responses

- **401 Unauthorized:** The request is missing a valid authentication token.
- **403 Forbidden:** The authenticated user does not have the `view meetings` permission.
- **500 Internal Server Error:** A generic error indicating a problem on the server.
