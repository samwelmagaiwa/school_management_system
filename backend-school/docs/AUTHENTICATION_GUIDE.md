# Authentication & Authorization System Documentation

## Overview

The School Management System implements a comprehensive authentication and authorization system using Laravel Sanctum with role-based access control (RBAC). The system provides secure API token-based authentication with fine-grained permissions and security features.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Authentication](#authentication)
3. [Authorization & Permissions](#authorization--permissions)
4. [User Roles](#user-roles)
5. [Security Features](#security-features)
6. [API Endpoints](#api-endpoints)
7. [Middleware](#middleware)
8. [Services](#services)
9. [Usage Examples](#usage-examples)
10. [Security Best Practices](#security-best-practices)

## Architecture Overview

### Components

- **Authentication Guard**: Laravel Sanctum for API token management
- **User Model**: Extended with role-based methods and relationships
- **Middleware Stack**: Custom middleware for authentication, authorization, and security
- **Permission System**: Fine-grained permission control via PermissionService
- **Activity Logging**: Comprehensive audit trail for security monitoring
- **Password Policies**: Enforced password strength and security policies

### Flow Diagram

```
Client Request → Sanctum Auth → Role Middleware → Permission Check → Controller → Service → Response
                      ↓               ↓              ↓
                Security Headers   Activity Log   Rate Limiting
```

## Authentication

### Configuration

The system uses Laravel Sanctum configured in `config/sanctum.php`:

- **Stateful Domains**: Configured for frontend SPA communication
- **Token Expiration**: Configurable token lifetime
- **CSRF Protection**: Enabled for stateful requests

### Authentication Flow

1. **Login Process**:
   ```
   POST /api/v1/auth/login
   {
     "email": "user@example.com",
     "password": "password"
   }
   ```

2. **Token Generation**: Upon successful authentication, a Sanctum token is created
3. **User Context**: User information, permissions, and menu items are returned
4. **Token Usage**: Include token in subsequent requests: `Authorization: Bearer {token}`

### User Registration

```
POST /api/v1/auth/register
{
  "first_name": "John",
  "last_name": "Doe", 
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "Teacher",
  "school_id": 1
}
```

### Token Management

- **Token Refresh**: `POST /api/v1/auth/refresh`
- **Logout**: `POST /api/v1/auth/logout` (revokes current token)
- **User Info**: `GET /api/v1/auth/user` (get authenticated user data)

## Authorization & Permissions

### Permission System

The system implements a comprehensive permission system through the `PermissionService`:

#### Permission Structure
```
{module}.{action}_{resource}
```

Examples:
- `students.view_students`
- `schools.create_schools`
- `users.manage_user_roles`

#### Permission Categories

1. **SuperAdmin Permissions**: `['*']` (full access)
2. **Module-Based Permissions**: Organized by functional areas
3. **Wildcard Permissions**: `students.*` (all student permissions)
4. **Contextual Permissions**: School and data-level restrictions

### Permission Modules

- **SuperAdmin**: System-wide management
- **Schools**: School management and configuration
- **Users**: User account management
- **Students**: Student data and academic records
- **Teachers**: Teacher management and performance
- **Classes**: Class organization and scheduling
- **Subjects**: Curriculum and subject management
- **Attendance**: Attendance tracking and reporting
- **Exams**: Examination and assessment management
- **Fees**: Financial management and billing
- **Library**: Library resource management
- **Transport**: Transportation services
- **Reports**: Analytics and reporting access

## User Roles

### Available Roles

1. **SuperAdmin**
   - Full system access
   - Multi-school management
   - System configuration
   - User role management

2. **Admin**
   - School-level administration
   - Staff and student management
   - Academic operations
   - Reports and analytics

3. **Teacher**
   - Class and student management
   - Attendance and grading
   - Academic content creation
   - Performance tracking

4. **Student**
   - Personal academic records
   - Attendance viewing
   - Assignment access
   - Fee information

5. **Parent**
   - Children's academic information
   - Communication with teachers
   - Fee payment access
   - Progress monitoring

6. **HR**
   - Staff management
   - Payroll operations
   - Performance reviews
   - Administrative reports

7. **Accountant**
   - Financial management
   - Fee collection
   - Payment processing
   - Financial reporting

### Role-Based Access Control

```php
// Check user role
if ($user->isSuperAdmin()) {
    // Full access
}

// Check permissions
$permissionService = app(PermissionService::class);
if ($permissionService->hasPermission($user, 'students.view_students')) {
    // Allow action
}

// Check resource access
if ($permissionService->canAccessResource($user, $student, 'view')) {
    // Allow access to specific student
}
```

## Security Features

### Password Security

- **Strength Requirements**: Minimum 8 characters, mixed case, numbers, symbols
- **Policy Enforcement**: Prevents common passwords and personal information
- **Password Expiration**: Configurable password aging (90 days default)
- **Password History**: Prevents reuse of last 5 passwords
- **Account Lockout**: 5 failed attempts result in 30-minute lockout

### Security Headers

Automatically applied to all API responses:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy: default-src 'none'`

### Rate Limiting

- **Authenticated Users**: 60 requests per minute per user per route
- **Unauthenticated Users**: More restrictive limits per IP
- **Login Attempts**: Special throttling for authentication endpoints
- **Lockout Protection**: Progressive delays on repeated failures

### Activity Logging

Comprehensive audit trail including:
- Authentication events
- Authorization failures
- Data access attempts
- Security violations
- Performance metrics
- User actions

### Input Validation

- **Form Requests**: Dedicated validation classes for all endpoints
- **Sanitization**: Automatic input cleaning and validation
- **CSRF Protection**: For stateful requests
- **SQL Injection Protection**: Eloquent ORM and prepared statements

## API Endpoints

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/auth/login` | User login | No |
| POST | `/api/v1/auth/register` | User registration | No |
| POST | `/api/v1/auth/logout` | User logout | Yes |
| GET | `/api/v1/auth/user` | Get user info | Yes |
| POST | `/api/v1/auth/refresh` | Refresh token | Yes |

### Protected Endpoints

All other endpoints require authentication via Sanctum token:

```
Authorization: Bearer {your-token-here}
```

### SuperAdmin Endpoints

Special endpoints requiring SuperAdmin role:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/superadmin/dashboard` | System dashboard |
| GET | `/api/v1/superadmin/schools` | All schools management |
| GET | `/api/v1/superadmin/users` | All users management |
| GET | `/api/v1/superadmin/reports` | System reports |

## Middleware

### Authentication Middleware

1. **auth:sanctum**: Laravel Sanctum authentication
2. **ApiAuthenticate**: Custom API authentication with JSON responses
3. **SuperAdminMiddleware**: SuperAdmin role verification

### Authorization Middleware

1. **RoleMiddleware**: Multi-role authorization check
2. **role:SuperAdmin,Admin**: Route-level role restrictions

### Security Middleware

1. **SecurityHeadersMiddleware**: Security headers injection
2. **AdvancedRateLimitMiddleware**: Advanced rate limiting
3. **LogActivity**: Request and security logging

### Usage Example

```php
Route::middleware(['auth:sanctum', 'role:SuperAdmin,Admin', 'security.headers'])
    ->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    });
```

## Services

### Core Services

1. **MenuService**: Role-based menu generation
2. **PermissionService**: Fine-grained permission management
3. **PasswordPolicyService**: Password strength and policy enforcement
4. **ActivityLogger**: Security and audit logging

### Permission Service Usage

```php
use App\Services\PermissionService;

class StudentController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Check permission
        $this->permissionService->authorize($user, 'students.view_students');
        
        // Get accessible school IDs
        $schoolIds = $this->permissionService->getAccessibleSchoolIds($user);
        
        // Apply school-based filtering
        $students = Student::whereIn('school_id', $schoolIds)->get();
        
        return response()->json($students);
    }
}
```

### Password Policy Usage

```php
use App\Services\PasswordPolicyService;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $passwordPolicy = app(PasswordPolicyService::class);
        
        // Validate password strength
        $userData = $request->only(['first_name', 'last_name', 'email']);
        $passwordRules = $passwordPolicy->getValidationRules($userData);
        
        $request->validate([
            'password' => ['required', 'confirmed', $passwordRules]
        ]);
        
        // ... user creation logic
    }
}
```

## Usage Examples

### Frontend Authentication

```javascript
// Login
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});

const data = await response.json();
const token = data.data.token;

// Store token and use in subsequent requests
localStorage.setItem('auth_token', token);

// Authenticated requests
const protectedResponse = await fetch('/api/v1/students', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

### Backend Authorization

```php
// In Controller
public function store(Request $request)
{
    // Automatic authorization via middleware
    // role:Admin,SuperAdmin middleware already applied
    
    // Additional permission check
    $this->permissionService->authorize(
        $request->user(), 
        'students.create_students'
    );
    
    // Business logic
    $student = Student::create($request->validated());
    
    // Log activity
    ActivityLogger::logStudent('Created new student', [
        'student_id' => $student->id,
        'student_name' => $student->full_name
    ]);
    
    return response()->json($student, 201);
}
```

## Security Best Practices

### 1. Token Management
- Store tokens securely (httpOnly cookies for web, secure storage for mobile)
- Implement automatic token refresh
- Clear tokens on logout
- Monitor token usage patterns

### 2. Password Security
- Enforce strong password policies
- Implement password expiration
- Use secure password reset flows
- Monitor failed login attempts

### 3. API Security
- Always validate input data
- Implement rate limiting
- Use HTTPS in production
- Monitor for suspicious activity

### 4. Role Management
- Follow principle of least privilege
- Regular audit of user permissions
- Implement role separation
- Monitor permission changes

### 5. Data Protection
- Encrypt sensitive data at rest
- Use secure communication channels
- Implement data access logging
- Regular security assessments

### 6. Monitoring & Auditing
- Enable comprehensive logging
- Monitor authentication patterns
- Set up security alerts
- Regular log analysis

## Configuration

### Environment Variables

```env
# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:8080,127.0.0.1:8080
SANCTUM_TOKEN_PREFIX=sms_

# Session Configuration  
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# Password Policy
PASSWORD_MIN_LENGTH=8
PASSWORD_EXPIRY_DAYS=90
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_DURATION=30

# Rate Limiting
API_RATE_LIMIT=60
AUTH_RATE_LIMIT=10
```

### Production Considerations

1. **HTTPS Only**: Force HTTPS in production
2. **Secure Cookies**: Enable secure session cookies
3. **Rate Limiting**: Implement strict rate limiting
4. **Monitoring**: Set up comprehensive monitoring
5. **Backup**: Regular security configuration backups

This authentication system provides enterprise-level security while maintaining usability and flexibility for the school management system.
