# School Management System - Database Setup Guide

## 🗄️ Database Structure

The school management system uses a modular database architecture with the following core tables:

### Core Tables
- **users** - All system users (SuperAdmin, Admin, Teacher, Student, Parent, HR)
- **schools** - School information and settings
- **subjects** - Academic subjects per school
- **classes** - Class/grade sections with teacher assignments
- **students** - Student profiles and academic information
- **teachers** - Teacher profiles and employment details
- **attendance** - Daily attendance records

### Relationship Tables
- **teacher_subjects** - Many-to-many: Teachers ↔ Subjects
- **class_subjects** - Many-to-many: Classes ↔ Subjects

## 🚀 Quick Setup

### 1. Environment Configuration
```bash
cd backend-school
cp .env.example .env
```

Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Generate Application Key
```bash
php artisan key:generate
```

### 4. Run Migrations and Seeders
```bash
# Fresh migration with sample data
php artisan migrate:fresh --seed

# Or step by step
php artisan migrate
php artisan db:seed
```

### 5. Start the Server
```bash
php artisan serve
```

## 🔧 Manual Database Setup

If you prefer manual setup or encounter issues:

### Create Database
```sql
CREATE DATABASE school_management_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Run Migrations
```bash
php artisan migrate
```

### Seed Sample Data
```bash
php artisan db:seed --class=SchoolManagementSeeder
```

## 📊 Sample Data

The seeder creates:

### Schools
- **Demo High School** (DHS) - Primary test school
- **Green Valley Academy** (GVA) - Secondary test school

### Users & Credentials
| Role | Email | Password | School |
|------|-------|----------|---------|
| SuperAdmin | superadmin@school.com | password | - |
| Admin | admin@demohigh.edu | password | Demo High School |
| Admin | admin@greenvalley.edu | password | Green Valley Academy |
| Teacher | michael.brown@demohigh.edu | password | Demo High School |
| Student | alice.johnson@student.demohigh.edu | password | Demo High School |

### Academic Structure
- **Grades**: 9-12
- **Sections**: A, B per grade
- **Subjects**: Mathematics, English, Science, History, Geography, PE
- **Classes**: 6 classes in Demo High School
- **Students**: 5 sample students in Grade 10-A

## 🔍 Database Relationships

```
Users (1) ←→ (1) Students
Users (1) ←→ (1) Teachers
Schools (1) ←→ (∞) Users
Schools (1) ←→ (∞) Classes
Schools (1) ←→ (∞) Subjects
Classes (1) ←→ (∞) Students
Classes (∞) ←→ (∞) Subjects
Teachers (∞) ←→ (∞) Subjects
Students (1) ←→ (∞) Attendance
```

## 🧪 Testing the Setup

### 1. Health Check
```bash
curl http://localhost:8000/api/health
```

### 2. Login Test
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"superadmin@school.com","password":"password"}'
```

### 3. Dashboard Test (with token)
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/dashboard
```

## 🔧 Troubleshooting

### Migration Issues
```bash
# Reset and retry
php artisan migrate:reset
php artisan migrate:fresh --seed
```

### Permission Issues
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues
1. Check MySQL service is running
2. Verify database credentials in `.env`
3. Ensure database exists
4. Check firewall settings

### Seeder Issues
```bash
# Run specific seeder
php artisan db:seed --class=SchoolManagementSeeder

# Clear cache and retry
php artisan config:clear
php artisan cache:clear
```

## 📝 Custom Migrations

To create new migrations:
```bash
# Create migration
php artisan make:migration create_new_table

# Create model with migration
php artisan make:model ModuleName/Models/ModelName -m
```

## 🔄 Database Maintenance

### Backup
```bash
mysqldump -u root -p school_management_system > backup.sql
```

### Restore
```bash
mysql -u root -p school_management_system < backup.sql
```

### Reset Development Data
```bash
php artisan migrate:fresh --seed
```

## 📚 Additional Resources

- [Laravel Migrations](https://laravel.com/docs/migrations)
- [Laravel Seeders](https://laravel.com/docs/seeding)
- [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)

## 🆘 Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database connection
3. Ensure all dependencies are installed
4. Check file permissions