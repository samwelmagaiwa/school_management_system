# V1 API Migration Guide

## Overview
The backend has been successfully migrated to use **V1 API architecture only**. All legacy SuperAdmin routes have been removed and replaced with standardized V1 endpoints.

## ✅ Changes Made

### 1. Route Architecture
- **REMOVED**: Legacy `/api/superadmin/*` routes
- **ACTIVE**: Only `/api/v1/superadmin/*` routes
- **REDIRECT**: Legacy routes now return 301 redirect with proper V1 endpoint information

### 2. Controller Structure
- **REMOVED**: `app/Http/Controllers/SuperAdminController.php` (legacy)
- **ACTIVE**: `app/Http/Controllers/Api/V1/SuperAdminController.php` (V1 only)

### 3. Middleware & Authorization
- All V1 routes have proper authentication (`auth:sanctum`)
- SuperAdmin routes include role-based middleware (`superadmin`)
- Rate limiting applied (`advanced.rate.limit:30,1`)

## 🔄 API Endpoint Changes

### Schools Management (CRITICAL FOR FRONTEND)

| Legacy Route (DEPRECATED) | V1 Route (USE THIS) | Status |
|---------------------------|---------------------|---------|
| `/api/superadmin/schools` | `/api/v1/superadmin/schools` | ✅ Working |
| `/api/superadmin/schools/statistics` | `/api/v1/superadmin/schools/statistics` | ✅ Working |
| `/api/superadmin/schools/{id}` | `/api/v1/superadmin/schools/{id}` | ✅ Working |

### Dashboard & Reports

| Legacy Route (DEPRECATED) | V1 Route (USE THIS) | Status |
|---------------------------|---------------------|---------|
| `/api/superadmin/dashboard` | `/api/v1/superadmin/dashboard` | ✅ Working |
| `/api/superadmin/reports` | `/api/v1/superadmin/reports` | ✅ Working |

### Users Management

| Legacy Route (DEPRECATED) | V1 Route (USE THIS) | Status |
|---------------------------|---------------------|---------|
| `/api/superadmin/users` | `/api/v1/superadmin/users` | ✅ Working |
| `/api/superadmin/users/statistics` | `/api/v1/superadmin/users/statistics` | ✅ Working |
| `/api/superadmin/users/schools` | `/api/v1/superadmin/users/schools` | ✅ Working |
| `/api/superadmin/users/roles` | `/api/v1/superadmin/users/roles` | ✅ Working |

## 🔧 Frontend Update Required

### 1. Update API Base URLs
```javascript
// OLD (will not work)
const LEGACY_ENDPOINTS = {
  schools: '/api/superadmin/schools',
  dashboard: '/api/superadmin/dashboard',
  users: '/api/superadmin/users'
};

// NEW (use these)
const V1_ENDPOINTS = {
  schools: '/api/v1/superadmin/schools',
  dashboard: '/api/v1/superadmin/dashboard', 
  users: '/api/v1/superadmin/users'
};
```

### 2. API Response Structure (Unchanged)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Demo High School",
      "code": "DHS001", 
      "email": "info@demohigh.edu",
      "is_active": true,
      "status": true,
      "total_users": 7,
      "status_label": "Active",
      "school_type_formatted": "Primary & Secondary School",
      // ... more fields
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 5,
    "per_page": 25
  }
}
```

### 3. Authentication (Unchanged)
```javascript
// Authentication headers remain the same
const headers = {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json'
};
```

## 🧪 Testing Endpoints

### Test V1 Schools API
```bash
curl -X GET "http://localhost:8000/api/v1/superadmin/schools" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Expected Response
- **Status**: 200 OK
- **Data**: Array of 5 schools with complete information
- **Meta**: Pagination information

### Test Legacy Redirect
```bash
curl -X GET "http://localhost:8000/api/superadmin/schools" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Expected Response
- **Status**: 301 Moved Permanently
- **Body**: `{"message": "This endpoint has been moved. Please use the V1 API.", "redirect_to": "/api/v1/superadmin/schools", "deprecated": true}`

## ⚡ Quick Fix for Frontend

### Option 1: Global API URL Update
```javascript
// In your API configuration file
const API_BASE_URL = 'http://localhost:8000/api/v1';

// Update all SuperAdmin API calls
const superAdminAPI = {
  schools: `${API_BASE_URL}/superadmin/schools`,
  dashboard: `${API_BASE_URL}/superadmin/dashboard`,
  users: `${API_BASE_URL}/superadmin/users`,
  // ... other endpoints
};
```

### Option 2: Service-by-Service Update
```javascript
// Update your schools service
class SchoolsService {
  constructor() {
    this.baseURL = '/api/v1/superadmin/schools'; // Changed from /api/superadmin/schools
  }
  
  async getSchools(params = {}) {
    return await axios.get(this.baseURL, { params });
  }
}
```

## 🛠️ Debug Tools Available

### Debug Schools Requests
```
GET /api/debug/schools-requests
```

### Test Frontend Integration  
```
GET /api/debug/test-schools-frontend
```

## ❗ Important Notes

1. **Backend is Ready**: All V1 endpoints are working and tested
2. **Data is Available**: 5 schools exist in the database 
3. **Authentication Works**: Sanctum tokens are properly validated
4. **Frontend Issue**: The "No schools found" message is due to frontend calling wrong endpoints

## 🎯 Action Items for Frontend Team

1. **Update API URLs** from `/api/superadmin/*` to `/api/v1/superadmin/*`
2. **Test the endpoints** using the provided curl commands
3. **Verify authentication tokens** are being sent correctly
4. **Check browser developer tools** for network requests during page load
5. **Use the debug endpoints** to troubleshoot any remaining issues

## 📞 Support

If you encounter any issues after updating to V1 endpoints:

1. Check the browser developer console for errors
2. Verify the network requests in Developer Tools
3. Use the debug endpoints to test authentication
4. Ensure your frontend is sending the correct `Authorization` header

The backend is fully functional and ready - this is purely a frontend URL configuration issue.
