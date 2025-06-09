# API Documentation - Bienetre Pharma

## Base URL
```
http://your-domain.com
```

## Authentication
Currently, no authentication is required. Future versions will implement token-based authentication.

## Endpoints

### Clients

#### Get All Clients
```http
GET /clients
```

#### Get Client by ID
```http
GET /clients/{id}
```

#### Create Client
```http
POST /clients
Content-Type: application/x-www-form-urlencoded

name=Pharmacie+Example&address=123+Rue+Test&phone=0555123456&email=test@example.com&csrf_token=TOKEN
```

#### Update Client
```http
PUT /clients/{id}
```

#### Delete Client
```http
DELETE /clients/{id}
```

### Transactions

#### Get Transactions for Client/Month
```http
GET /clients/{id}/months/{month}
```

#### Add Invoice
```http
POST /clients/{id}/months/{month}/invoices
```

#### Add Return
```http
POST /clients/{id}/months/{month}/returns
```

#### Set Discount
```http
POST /clients/{id}/months/{month}/discount
```

### Dashboard

#### Get Statistics
```http
GET /dashboard/stats
```

## Response Format

### Success Response
```json
{
    "success": true,
    "data": {...},
    "message": "Operation successful"
}
```

### Error Response
```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE"
}
```

## Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `403` - Forbidden (CSRF)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error
