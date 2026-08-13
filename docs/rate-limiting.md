# Rate Limiting

## How does rate limiting work?

Our rate limiting system works based on Redis Database. Each requests is stored in Redis with a timestamp. When a new request is made, the system checks the number of requests made by the user in a specific time window (e.g., 1 minute). If the number of requests exceeds the allowed limit, the request is denied, and an error message is returned.

## How to configure rate limiting?

You can configure rate limiting in Settings page in Security Tab. You can set the maximum number of requests allowed per user in a specific time window. You can also configure the time window duration (e.g., 1 minute, 5 minutes, etc.) and if you even want to enable or disable rate limiting.

## Example rate limiting error response

When a user exceeds the allowed number of requests, they will receive an error response similar to the following:

```json
{
  "success": false,
  "response": [],
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "description": "Rate limit exceeded",
    "data": {
      "limit": 50,
      "remaining": 0,
      "reset_in": 60
    }
  },
  "metadata": {
    "timestamp": 1234567890.123456,
    "timezone": "Europe/Warsaw"
  },
  "signature": "MEUCIQDx8...[base64-encoded signature]"
}
```