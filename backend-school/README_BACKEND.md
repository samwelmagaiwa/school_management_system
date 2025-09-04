# School Management System - Laravel 12 Backend

A comprehensive Laravel 12 backend API for school management system with modular architecture, Sanctum authentication, and role-based access control.

## Features

- **Laravel 12.x** with latest features
- **Laravel Sanctum** for SPA authentication
- **Modular Architecture** for better code organization
- **Role-based Access Control** (SuperAdmin, Admin, Teacher, Student, Parent)
- **RESTful API** with consistent JSON responses
- **Comprehensive Validation** with FormRequest classes
- **Policy-based Authorization** for fine-grained access control
- **API Resources** for consistent data transformation
- **CORS Configuration** for frontend integration

## Modules

### 1. Schools Module
- CRUD operations for schools
- Search and pagination
- School-specific data scoping

### 2. Users Module
- User management with role-based access
- Default password generation (last_name)
- User statistics and reporting
- Password reset functionality

### 3. Students Module
- Student management with auto-generated admission numbers
- Class and section management
- Parent information tracking
- Student promotion functionality

## API Endpoints

### Authentication
```
POST   /api/auth/login          # User login
POST   /api/auth/logout         # User logout
GET    /api/auth/me             # Get current user
POST   /api/auth/refresh        # Refresh token
```

### Schools
```
GET    /api/schools             # List schools (with pagination, search)
POST   /api/schools             # Create school
GET    /api/schools/{id}        # Get school details
PUT    /api/schools/{id}        # Update school
DELETE /api/schools/{id}        # Delete school
```

### Users
```
GET    /api/users               # List users (with filters)
POST   /api/users               # Create user
GET    /api/users/{id}          # Get user details
PUT    /api/users/{id}          # Update user
DELETE /api/users/{id}          # Delete user
GET    /api/users/roles/available        # Get available roles
GET    /api/users/role/{role}            # Get users by role
GET    /api/users/statistics/overview    # Get user statistics
POST   /api/users/{id}/reset-password    # Reset user password
```

### Students
```
GET    /api/students            # List students (with filters)
POST   /api/students            # Create student
GET    /api/students/{id}       # Get student details
PUT    /api/students/{id}       # Update student
DELETE /api/students/{id}       # Delete student
GET    /api/students/{id}/performance    # Get student performance
POST   /api/students/{id}/promote        # Promote student
GET    /api/students/statistics/overview # Get student statistics
```

## Installation

1. **Clone the repository**
   ```bash
   cd backend-school
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   Update `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=school_management_system
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Configure Sanctum**
   Update `.env` file with frontend URL:
   ```env
   SANCTUM_STATEFUL_DOMAINS=localhost:8080,127.0.0.1:8080
   FRONTEND_URL=http://localhost:8080
   ```

6. **Run migrations** (if not already done)
   ```bash
   php artisan migrate
   ```

7. **Start the server**
   ```bash
   php artisan serve
   ```

## Configuration

### CORS Configuration
The API is configured to accept requests from:
- `http://localhost:8080` (Vue.js frontend)
- `http://localhost:3000` (Alternative frontend port)

### Sanctum Configuration
- Stateful authentication for SPA
- Token-based authentication for API
- CSRF protection for stateful requests

### Role-based Access Control

#### Roles:
- **SuperAdmin**: Full system access
- **Admin**: School-specific management
- **Teacher**: Limited access to assigned classes
- **Student**: Own data access
- **Parent**: Child's data access

#### Permissions:
- **Schools**: SuperAdmin (full), Admin (own school)
- **Users**: SuperAdmin (all), Admin (school users)
- **Students**: SuperAdmin (all), Admin (school students), Teacher (assigned students)

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  },
  "meta": {
    // Pagination metadata (for lists)
  },
  "filters": {
    // Applied filters (for lists)
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

## Validation Rules

### School Creation/Update
- `name`: Required, max 255 characters
- `code`: Required, unique, max 20 characters
- `email`: Required, unique, valid email
- `phone`: Required, max 20 characters
- `address`: Required, max 500 characters
- `established_date`: Required, date, not future

### User Creation/Update
- `first_name`: Required, max 255 characters
- `last_name`: Required, max 255 characters
- `email`: Required, unique, valid email
- `role`: Required, valid role
- `school_id`: Required for non-SuperAdmin roles
- `date_of_birth`: Required, date, before today

### Student Creation/Update
- `user_id`: Required, exists in users table
- `school_id`: Required, exists in schools table
- `admission_no`: Auto-generated if not provided
- `parent_name`: Required, max 255 characters
- `parent_phone`: Required, max 20 characters
- `emergency_contact`: Required, max 20 characters

## Security Features

1. **Authentication**: Laravel Sanctum with token-based auth
2. **Authorization**: Policy-based access control
3. **Validation**: Comprehensive input validation
4. **CORS**: Configured for specific origins
5. **Rate Limiting**: Built-in Laravel rate limiting
6. **CSRF Protection**: For stateful requests

## Development

### Adding New Modules
1. Create module directory in `app/Modules/`
2. Add Controller, Model, Requests, Policy, Resources
3. Create `routes.php` file
4. Register routes in `routes/api.php`
5. Register policies in `AuthServiceProvider`

### Testing
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

## Deployment

1. **Production Environment**
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Environment Variables**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```

3. **Database**
   ```bash
   php artisan migrate --force
   ```

## Troubleshooting

### Common Issues

1. **CORS Errors**
   - Check `config/cors.php` configuration
   - Verify frontend URL in allowed origins

2. **Authentication Issues**
   - Ensure Sanctum middleware is properly configured
   - Check stateful domains configuration

3. **Permission Denied**
   - Verify user roles and policies
   - Check authorization logic in controllers

### Logs
Check Laravel logs for detailed error information:
```bash
tail -f storage/logs/laravel.log
```

## API Documentation

For detailed API documentation with request/response examples, use tools like:
- Postman Collection
- OpenAPI/Swagger documentation
- Laravel API Documentation Generator

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review Laravel documentation
3. Check module-specific documentation
4. Contact the development team

## License

This project is licensed under the MIT License.