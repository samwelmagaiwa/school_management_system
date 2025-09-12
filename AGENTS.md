# Repository Guidelines

## Project Structure & Module Organization

This is a full-stack school management system with separate backend and frontend applications:
- **backend-school/**: Laravel 12 API with modular architecture (16 modules)
- **frontend-school/**: Vue.js 3 SPA with role-based access control
- **Root level**: Project documentation and configuration

## Build, Test, and Development Commands

```bash
# Backend (Laravel API)
cd backend-school
composer install
php artisan serve                    # Start API server (port 8000)
php artisan migrate                  # Run database migrations
php artisan test                     # Run PHPUnit tests

# Frontend (Vue.js SPA)
cd frontend-school
npm install
npm run serve                        # Start dev server (port 8080)
npm run build                        # Build for production
npm run lint                         # Run ESLint

# Full-stack development
composer run dev                     # Concurrent backend + frontend + queue + logs
```

## Coding Style & Naming Conventions

- **Indentation**: 4 spaces for PHP, 2 spaces for JavaScript/Vue
- **File naming**: PascalCase for classes, camelCase for methods, kebab-case for Vue components
- **Function/variable naming**: camelCase in JavaScript, snake_case in PHP
- **Linting**: ESLint for frontend, Laravel Pint for backend

## Testing Guidelines

- **Framework**: PHPUnit for backend, Vue Test Utils for frontend
- **Test files**: `tests/Feature/` and `tests/Unit/` for backend
- **Running tests**: `php artisan test` (backend), `npm run test` (frontend)
- **Coverage**: Tests include authentication, API endpoints, and student management

## Commit & Pull Request Guidelines

- **Commit format**: Descriptive messages (examples: "second commit", "first commit")
- **PR process**: Feature branches, code review required
- **Branch naming**: feature/module-name or fix/issue-description

---

# Repository Tour

## 🎯 What This Repository Does

School Management System is a comprehensive full-stack application for managing educational institutions with role-based access control, multi-tenant architecture, and modular design supporting students, teachers, administrators, and parents.

**Key responsibilities:**
- Multi-tenant school management with SuperAdmin oversight
- Role-based access control (SuperAdmin, Admin, Teacher, Student, Parent, HR)
- Academic management (classes, subjects, exams, attendance, fees)
- Administrative operations (HR, library, transport, ID cards)

---

## 🏗️ Architecture Overview

### System Context
```
[Frontend Vue.js SPA] → [Laravel API Backend] → [MySQL Database]
        ↓                        ↓
[Role-based UI]         [Modular Services]
        ↓                        ↓
[Authentication]        [Multi-tenant Data]
```

### Key Components
- **Frontend (Vue.js 3)** - SPA with role-based routing, Vuex state management, and responsive design
- **Backend (Laravel 12)** - Modular API with Sanctum authentication and service-oriented architecture
- **Database Layer** - MySQL with migrations, seeders, and multi-tenant support
- **Authentication System** - JWT tokens with role-based permissions and tenant isolation

### Data Flow
1. User authenticates via Vue.js frontend to Laravel Sanctum API
2. Role-based middleware validates permissions and tenant access
3. Modular services process business logic with database operations
4. API responses formatted through Laravel resources and returned to frontend
5. Vue.js components update UI based on user role and permissions

---

## 📁 Project Structure [Partial Directory Tree]

```
school_management_system/
├── backend-school/                 # Laravel 12 API Backend
│   ├── app/
│   │   ├── Http/Controllers/       # Base controllers and middleware
│   │   ├── Modules/                # 16 feature modules
│   │   │   ├── Auth/              # Authentication module
│   │   │   ├── SuperAdmin/        # Multi-tenant management
│   │   │   ├── User/              # User management
│   │   │   ├── Student/           # Student operations
│   │   │   ├── Teacher/           # Teacher management
│   │   │   ├── Class/             # Class management
│   │   │   ├── Subject/           # Subject management
│   │   │   ├── Exam/              # Examination system
│   │   │   ├── Attendance/        # Attendance tracking
│   │   │   ├── Fee/               # Fee management
│   │   │   ├── Library/           # Library system
│   │   │   ├── Transport/         # Transport management
│   │   │   ├── HR/                # Human resources
│   │   │   ├── IDCard/            # ID card generation
│   │   │   ├── School/            # School management
│   │   │   └── Dashboard/         # Dashboard services
│   │   ├── Services/              # Shared services
│   │   └── Traits/                # Reusable traits
│   ├── config/                    # Laravel configuration
│   ├── database/                  # Migrations, seeders, factories
│   ├── routes/                    # API routes
│   └── tests/                     # PHPUnit tests
├── frontend-school/               # Vue.js 3 Frontend
│   ├── src/
│   │   ├── components/            # Shared Vue components
│   │   │   ├── Layout.vue         # Main layout wrapper
│   │   │   ├── Header.vue         # Navigation header
│   │   │   ├── Sidebar.vue        # Role-based sidebar
│   │   │   └── Footer.vue         # Application footer
│   │   ├── modules/               # Feature modules (mirrors backend)
│   │   │   ├── auth/              # Authentication components
│   │   │   ├── dashboard/         # Dashboard views
│   │   │   ├── superadmin/        # SuperAdmin interface
│   │   │   ├── student/           # Student management
│   │   │   ├── teacher/           # Teacher management
│   │   │   └── [other modules]/   # Additional feature modules
│   │   ├── router/                # Vue Router configuration
│   │   ├── store/                 # Vuex state management
│   │   ├── services/              # API service layer
│   │   └── utils/                 # Utility functions
│   ├── public/                    # Static assets
│   └── dist/                      # Built application
└── docs/                          # Project documentation
```

### Key Files to Know

| File | Purpose | When You'd Touch It |
|------|---------|---------------------|
| `backend-school/routes/api.php` | API route definitions | Adding new endpoints |
| `backend-school/app/Modules/User/Models/User.php` | User model with roles | Modifying user permissions |
| `frontend-school/src/router/index.js` | Frontend routing | Adding new pages/routes |
| `frontend-school/src/main.js` | Vue.js application entry | Global configuration |
| `backend-school/composer.json` | PHP dependencies | Adding Laravel packages |
| `frontend-school/package.json` | Node.js dependencies | Adding Vue.js packages |
| `backend-school/.env.example` | Environment configuration | Setting up development |
| `frontend-school/src/services/api.js` | API client configuration | Changing API endpoints |
| `backend-school/app/Modules/*/routes.php` | Module-specific routes | Module functionality |
| `frontend-school/src/store/index.js` | Vuex store configuration | State management |

---

## 🔧 Technology Stack

### Core Technologies
- **Language:** PHP 8.2+ (Backend), JavaScript ES6+ (Frontend)
- **Backend Framework:** Laravel 12 - Modern PHP framework with excellent ecosystem
- **Frontend Framework:** Vue.js 3 - Progressive JavaScript framework with Composition API
- **Database:** MySQL - Relational database with full ACID compliance
- **Authentication:** Laravel Sanctum - SPA authentication with token management

### Key Libraries
- **Laravel Sanctum** - API authentication and SPA token management
- **Vue Router 4** - Client-side routing with navigation guards
- **Vuex 4** - Centralized state management for Vue.js
- **Axios** - HTTP client with request/response interceptors
- **Chart.js** - Data visualization for dashboards and reports
- **Font Awesome** - Icon library for consistent UI elements

### Development Tools
- **PHPUnit** - Backend testing framework with feature and unit tests
- **Laravel Pint** - Code formatting and style enforcement
- **ESLint** - JavaScript linting and code quality
- **Vue CLI** - Frontend build tooling and development server
- **Composer** - PHP dependency management
- **npm** - Node.js package management

---

## 🌐 External Dependencies

### Required Services
- **MySQL Database** - Primary data storage with multi-tenant support
- **Web Server** - Apache/Nginx for serving Laravel application
- **Node.js** - Frontend build process and development server

### Optional Integrations
- **Email Service** - SMTP configuration for notifications and password resets
- **File Storage** - Local or cloud storage for uploads and documents
- **Backup Services** - Database and file backup solutions

### Environment Variables

```bash
# Backend (.env)
APP_NAME="School Management System"
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management_system
DB_USERNAME=root
DB_PASSWORD=

# Frontend (.env)
VUE_APP_API_URL=http://localhost:8000/api
VUE_APP_APP_NAME=School Management System
```

---

## 🔄 Common Workflows

### User Authentication & Role Management
1. User logs in through Vue.js frontend with email/password
2. Laravel Sanctum validates credentials and issues API token
3. Frontend stores token and redirects based on user role
4. Role-based middleware protects API endpoints and UI routes

**Code path:** `LoginPage.vue` → `authService.js` → `AuthController.php` → `User.php`

### Student Management Operations
1. Admin creates/updates student through frontend forms
2. Vue.js validates data and sends to Laravel API
3. StudentService processes business logic and database operations
4. Response updates frontend state and displays confirmation

**Code path:** `StudentList.vue` → `studentService.js` → `StudentController.php` → `StudentService.php`

### Multi-tenant Data Access
1. SuperAdmin manages multiple schools (tenants)
2. School-specific users access only their tenant data
3. Database queries filtered by school_id/tenant_id
4. Role permissions enforced at both API and UI levels

**Code path:** `SuperAdminDashboard.vue` → `superAdminService.js` → `SuperAdminController.php` → `TenantPermission.php`

---

## 📈 Performance & Scale

### Performance Considerations
- **Database Indexing:** Optimized indexes on frequently queried fields (user roles, school_id, status)
- **API Caching:** Laravel cache for dashboard statistics and role permissions
- **Frontend Optimization:** Lazy loading of Vue components and route-based code splitting
- **Query Optimization:** Eloquent relationships with eager loading to prevent N+1 queries

### Monitoring
- **Metrics:** API response times, database query performance, user session tracking
- **Alerts:** Failed authentication attempts, database connection issues, high error rates
- **Logging:** Comprehensive activity logging with user actions and system events

---

## 🚨 Things to Be Careful About

### 🔒 Security Considerations
- **Authentication:** Sanctum tokens with proper expiration and refresh mechanisms
- **Authorization:** Role-based permissions with tenant isolation for multi-school setup
- **Data Validation:** Server-side validation for all API inputs with Laravel Form Requests
- **SQL Injection:** Eloquent ORM usage prevents direct SQL injection vulnerabilities
- **XSS Protection:** Vue.js automatic escaping and CSP headers

### Multi-tenant Data Isolation
- **Tenant Separation:** Strict enforcement of school_id filtering in all database queries
- **Role Boundaries:** SuperAdmin vs School Admin permissions clearly defined
- **Data Leakage Prevention:** Middleware ensures users only access their tenant data

### Module Dependencies
- **Service Layer:** Business logic isolated in service classes for testability
- **Database Migrations:** Proper foreign key constraints and cascading deletes
- **API Versioning:** Consistent API structure across all modules

### Development Workflow
- **Environment Setup:** Proper .env configuration for database and API connections
- **Database Seeding:** Use seeders for consistent development data
- **Testing:** Run both backend and frontend tests before deployment
- **Cache Management:** Clear Laravel caches when modifying configurations

*Updated at: 2025-01-15 12:00:00 UTC*