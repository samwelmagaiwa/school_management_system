# SuperAdmin API Guide

## 🔐 Authentication Required

All SuperAdmin routes require:
1. **Valid Bearer Token**: Include `Authorization: Bearer YOUR_TOKEN` header
2. **SuperAdmin Role**: User must have `role = 'SuperAdmin'`
3. **Active Status**: User must have `status = true`

## 🌐 Available Routes

### Legacy Routes (Backward Compatible)
Base URL: `/api/superadmin/`

### V1 Routes (Recommended)
Base URL: `/api/v1/superadmin/`

---

## 📊 Dashboard & Statistics

### Get Dashboard Statistics
```http
GET /api/superadmin/dashboard
GET /api/v1/superadmin/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_schools": 5,
      "active_schools": 4,
      "total_users": 150,
      "active_users": 142,
      "total_students": 100,
      "total_teachers": 25
    },
    "user_distribution": {
      "SuperAdmin": 1,
      "Admin": 5,
      "Teacher": 25,
      "Student": 100,
      "Parent": 19
    },
    "recent_activity": {
      "new_users_this_week": 5,
      "new_schools_this_month": 1,
      "logins_today": 45
    }
  }
}
```

### Get System Reports
```http
GET /api/superadmin/reports
GET /api/v1/superadmin/reports
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_distribution": {...},
    "school_statistics": {...},
    "monthly_growth": {...},
    "activity_summary": {...}
  }
}
```

---

## 🏫 Schools Management

### Get All Schools
```http
GET /api/superadmin/schools
GET /api/v1/superadmin/schools
```

**Query Parameters:**
- `search` (string): Search by name, code, or email
- `status` (boolean): Filter by active/inactive status
- `per_page` (integer): Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Demo High School",
        "code": "DHS",
        "email": "admin@demohigh.edu",
        "phone": "+1234567890",
        "address": "123 Education St",
        "status": true,
        "total_users": 50,
        "total_students": 35,
        "total_teachers": 10,
        "total_admins": 5
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

### Create School
```http
POST /api/superadmin/schools
POST /api/v1/superadmin/schools
```

**Request Body:**
```json
{
  "name": "New School Name",
  "code": "NSN",
  "email": "admin@newschool.edu",
  "phone": "+1234567890",
  "address": "456 Learning Ave",
  "website": "https://newschool.edu",
  "established_year": 2020,
  "principal_name": "John Doe",
  "principal_email": "principal@newschool.edu",
  "principal_phone": "+1234567891",
  "description": "A great school for learning",
  "board_affiliation": "State Board",
  "school_type": "secondary",
  "registration_number": "REG123456",
  "tax_id": "TAX789",
  "status": true
}
```

### Update School
```http
PUT /api/superadmin/schools/{id}
PUT /api/v1/superadmin/schools/{id}
```

**Request Body:** Same as create school

### Delete School
```http
DELETE /api/superadmin/schools/{id}
DELETE /api/v1/superadmin/schools/{id}
```

**Note:** Cannot delete schools with existing users.

---

## 👥 Users Management

### Get All Users
```http
GET /api/superadmin/users
GET /api/v1/superadmin/users
```

**Query Parameters:**
- `search` (string): Search by name or email
- `role` (string): Filter by role (SuperAdmin, Admin, Teacher, Student, Parent, HR, Accountant)
- `school_id` (integer): Filter by school
- `status` (boolean): Filter by active/inactive status
- `per_page` (integer): Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "role": "Admin",
        "school_id": 1,
        "status": true,
        "created_at": "2024-01-15T10:00:00Z",
        "school": {
          "id": 1,
          "name": "Demo High School",
          "code": "DHS"
        }
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

### Create User
```http
POST /api/superadmin/users
POST /api/v1/superadmin/users
```

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "password": "securepassword123",
  "phone": "+1234567890",
  "address": "123 Main St",
  "date_of_birth": "1990-01-15",
  "gender": "female",
  "role": "Teacher",
  "school_id": 1,
  "status": true
}
```

### Update User Status
```http
PUT /api/superadmin/users/{id}/status
PUT /api/v1/superadmin/users/{id}/status
```

**Request Body:**
```json
{
  "status": true
}
```

---

## 🔧 Testing the API

### 1. Login to get token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"superadmin@school.com","password":"password"}'
```

### 2. Use token for SuperAdmin requests
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     http://localhost:8000/api/superadmin/users
```

### 3. Test dashboard
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/superadmin/dashboard
```

### 4. Create a new school
```bash
curl -X POST http://localhost:8000/api/superadmin/schools \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test School",
    "code": "TS",
    "email": "admin@testschool.edu",
    "phone": "+1234567890",
    "address": "123 Test St",
    "established_year": 2024,
    "principal_name": "Test Principal",
    "principal_email": "principal@testschool.edu",
    "principal_phone": "+1234567891",
    "board_affiliation": "Test Board",
    "school_type": "secondary",
    "registration_number": "TEST123"
  }'
```

---

## 🚨 Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized. SuperAdmin access required."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to fetch users",
  "error": "Internal server error"
}
```

---

## 📝 Notes

1. **Role Validation**: All endpoints automatically check for SuperAdmin role
2. **Activity Logging**: All actions are logged for audit purposes
3. **Data Sanitization**: Sensitive data is automatically sanitized in logs
4. **Pagination**: List endpoints support pagination with `per_page` parameter
5. **Search**: Most list endpoints support search functionality
6. **Validation**: Comprehensive validation on all create/update operations

---

## 🔗 Related Documentation

- [Authentication Guide](./AUTH_GUIDE.md)
- [API Routes Overview](./API_ROUTES.md)
- [User Roles & Permissions](./ROLES_PERMISSIONS.md)