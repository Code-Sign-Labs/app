# Code Sign App API

## Authentication

API requests does not require any authentication. The only shield is the [rate limiting system](rate-limiting.md).

## Response format structure

### Success

This is the structure of a successful response:

```json
{
  "success": true,
  "response": {
    "activations": [
      {
        "id": "c7f59c3d-cfa2-4bf5-88fa-92a21cb03c27",
        "ip": "192.168.65.1",
        "device_name": "Postman Agent",
        "device_identifier": "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/534.1.1 (KHTML, like Gecko) Chrome/20.0.819.0 Safari/534.1.1",
        "metadata": {
          "user_agent": "PostmanRuntime/7.56.0",
          "time_of_activation": 1786472530.100242
        },
        "active": false,
        "deactivated_at": 1786473170,
        "created_at": 1786472530
      }
    ]
  },
  "error": null,
  "metadata": {
    "timestamp": 1786486816.654943,
    "timezone": "Europe/Warsaw"
  }
}
```

### Error

This is the structure of an error response:

```json
{
  "success": false,
  "response": [],
  "error": {
    "code": "LICENSE_NOT_FOUND",
    "description": "License key not found",
    "data": []
  },
  "metadata": {
    "timestamp": 1786486916.453467,
    "timezone": "Europe/Warsaw"
  }
}
```

## List of endpoints

### Validate license key - [GET] /api/licenses/<license_key>

Successful response:

```json
{
  "success": true,
  "response": {
    "id": "0547b0b3-f2d0-4292-876c-ae665e94fd22",
    "product": {
      "id": "3eef439b-2245-4dea-84f2-a9179a631d98",
      "name": "product_name"
    },
    "batch": null,
    "max_activations": 1,
    "current_activations": 0,
    "expires_at": null,
    "created_at": 1785840895
  },
  "error": null,
  "metadata": {
    "timestamp": 1786487178.538961,
    "timezone": "Europe/Warsaw"
  }
}
```

Error response:

```json
{
  "success": false,
  "response": [],
  "error": {
    "code": "LICENSE_NOT_FOUND",
    "description": "License key not found",
    "data": []
  },
  "metadata": {
    "timestamp": 1786487224.029105,
    "timezone": "Europe/Warsaw"
  }
}
```

Errors:
- LICENSE_NOT_FOUND - License key not found
- LICENSE_INACTIVE - License key is inactive
- LICENSE_EXPIRED - License key is expired

### List of devices - [GET] /api/licenses/<license_key>/devices

Successful response:
```json
{
  "success": true,
  "response": {
    "activations": [
      {
        "id": "c7f59c3d-cfa2-4bf5-88fa-92a21cb03c27",
        "ip": "192.168.65.1",
        "device_name": "Postman Agent",
        "device_identifier": "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/534.1.1 (KHTML, like Gecko) Chrome/20.0.819.0 Safari/534.1.1",
        "metadata": {
          "user_agent": "PostmanRuntime/7.56.0",
          "time_of_activation": 1786472530.100242
        },
        "active": false,
        "deactivated_at": 1786473170,
        "created_at": 1786472530
      }
    ]
  },
  "error": null,
  "metadata": {
    "timestamp": 1786487112.068577,
    "timezone": "Europe/Warsaw"
  }
}
```

Errors:
- LICENSE_KEY_MISSING - License key is missing
- LICENSE_NOT_FOUND - License key not found
- LICENSE_INACTIVE - License key is inactive
- LICENSE_EXPIRED - License key is expired

### Activate device - [POST] /api/licenses/<license_key>/activate

Body requirements:
- device_name (string, required)
- device_identifier (string, required)

Successful response:

```json
{
  "success": true,
  "response": {
    "activation": {
        "id": "c7f59c3d-cfa2-4bf5-88fa-92a21cb03c27",
        "ip": "192.168.65.1",
        "device_name": "Postman Agent",
        "device_identifier": "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/534.1.1 (KHTML, like Gecko) Chrome/20.0.819.0 Safari/534.1.1",
        "metadata": {
          "user_agent": "PostmanRuntime/7.56.0",
          "time_of_activation": 1786472530.100242
        },
        "active": false,
        "created_at": 1786472530
    },
    "license_id": "0547b0b3-f2d0-4292-876c-ae665e94fd22"
  },
  "error": null,
  "metadata": {
    "timestamp": 1786487112.068577,
    "timezone": "Europe/Warsaw"
  }
}
```

Errors:
- LICENSE_NOT_FOUND - License key not found
- LICENSE_INACTIVE - License key is inactive
- LICENSE_EXPIRED - License key is expired
- DEVICE_INFO_MISSING - Device name or identifier is missing
- LICENSE_MAX_ACTIVATIONS_REACHED - License key has reached the maximum number of activations
- INTERNAL_SERVER_ERROR - Internal server error

### Deactivate device - [POST] /api/licenses/<license_key>/deactivate

Body requirements:
- activation_id (string, required)

Successful response:

```json
{
  "success": true,
  "response": {
    "activation": {
        "id": "c7f59c3d-cfa2-4bf5-88fa-92a21cb03c27",
        "active": true,
        "deactivated_at": 1786473170
    }
  },
  "error": null,
  "metadata": {
    "timestamp": 1786487112.068577,
    "timezone": "Europe/Warsaw"
  }
}
```

Errors:
- LICENSE_NOT_FOUND - License key not found
- LICENSE_INACTIVE - License key is inactive
- LICENSE_EXPIRED - License key is expired
- ACTIVATION_ID_MISSING - Activation ID is missing
- ACTIVATION_NOT_FOUND - Activation not found
- INTERNAL_SERVER_ERROR - Internal server error