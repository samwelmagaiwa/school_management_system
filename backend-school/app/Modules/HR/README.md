# HR Module Documentation

## Overview

The HR (Human Resources) module provides comprehensive employee management functionality for the school management system. It handles employee lifecycle management, leave management, payroll processing, and organizational structure.

## Features

### 👥 Employee Management
- **Employee Profiles**: Complete employee information management
- **Organizational Hierarchy**: Manager-subordinate relationships
- **Employment Types**: Full-time, part-time, contract, temporary, intern
- **Status Tracking**: Active, inactive, terminated, on leave, suspended
- **Bulk Operations**: Import multiple employees at once

### 🏢 Department & Position Management
- **Department Structure**: Organize employees by departments
- **Position Hierarchy**: Define reporting relationships
- **Budget Tracking**: Department-wise budget allocation
- **Head Assignment**: Assign department heads

### 🏖️ Leave Management
- **Leave Types**: Configurable leave types (annual, sick, personal, etc.)
- **Leave Requests**: Employee self-service leave applications
- **Approval Workflow**: Manager approval process
- **Leave Balance**: Automatic balance calculation
- **Leave Calendar**: Visual leave calendar for planning
- **Document Attachments**: Support for medical certificates, etc.

### 💰 Payroll Management
- **Payroll Generation**: Automated payroll calculation
- **Earnings & Deductions**: Flexible payroll components
- **Bulk Processing**: Generate payroll for multiple employees
- **Approval Workflow**: Multi-level payroll approval
- **Payment Processing**: Track payment status
- **Payslip Generation**: PDF payslip generation

## API Endpoints

### Employee Management
```
GET    /api/hr/employees              # List employees
POST   /api/hr/employees              # Create employee
GET    /api/hr/employees/{id}         # Get employee details
PUT    /api/hr/employees/{id}         # Update employee
DELETE /api/hr/employees/{id}         # Delete employee
GET    /api/hr/employees/{id}/profile # Get employee profile
PATCH  /api/hr/employees/{id}/status  # Update employee status
GET    /api/hr/employees/{id}/hierarchy # Get employee hierarchy
POST   /api/hr/employees/bulk-import  # Bulk import employees
```

### Department Management
```
GET    /api/hr/departments                    # List departments
POST   /api/hr/departments                    # Create department
GET    /api/hr/departments/{id}               # Get department details
PUT    /api/hr/departments/{id}               # Update department
DELETE /api/hr/departments/{id}               # Delete department
GET    /api/hr/departments/{id}/statistics    # Get department statistics
POST   /api/hr/departments/{id}/assign-head  # Assign department head
```

### Leave Management
```
GET    /api/hr/leave/requests                     # List leave requests
POST   /api/hr/leave/requests                     # Create leave request
GET    /api/hr/leave/requests/{id}                # Get leave request details
PUT    /api/hr/leave/requests/{id}                # Update leave request
DELETE /api/hr/leave/requests/{id}                # Delete leave request
POST   /api/hr/leave/requests/{id}/approve        # Approve leave request
POST   /api/hr/leave/requests/{id}/reject         # Reject leave request
GET    /api/hr/leave/balance                      # Get leave balance
GET    /api/hr/leave/types                        # Get leave types
GET    /api/hr/leave/calendar                     # Get leave calendar
```

### Payroll Management
```
GET    /api/hr/payroll                            # List payrolls
POST   /api/hr/payroll                            # Create payroll
GET    /api/hr/payroll/{id}                       # Get payroll details
PUT    /api/hr/payroll/{id}                       # Update payroll
DELETE /api/hr/payroll/{id}                       # Delete payroll
POST   /api/hr/payroll/generate-bulk              # Generate bulk payroll
POST   /api/hr/payroll/{id}/approve               # Approve payroll
POST   /api/hr/payroll/{id}/process-payment       # Process payment
GET    /api/hr/payroll/{id}/payslip               # Generate payslip
GET    /api/hr/payroll/summary/period             # Get payroll summary
GET    /api/hr/payroll/employee/{id}/history      # Get employee payroll history
```

## Data Models

### Employee
```php
- id
- user_id (foreign key)
- school_id (foreign key)
- employee_id (unique)
- department_id (foreign key)
- position_id (foreign key)
- manager_id (foreign key)
- hire_date
- termination_date
- employment_type
- employment_status
- salary
- personal_information
- qualifications
- certifications
- skills
```

### Department
```php
- id
- school_id (foreign key)
- name
- code
- description
- head_id (foreign key)
- budget
- location
- contact_info
```

### Leave Request
```php
- id
- employee_id (foreign key)
- leave_type_id (foreign key)
- start_date
- end_date
- days_requested
- reason
- status
- approver_id
- approval_details
```

### Payroll
```php
- id
- employee_id (foreign key)
- payroll_number
- pay_period
- basic_salary
- gross_salary
- total_deductions
- net_salary
- status
- payment_details
```

## Business Logic

### Employee Lifecycle
1. **Onboarding**: Create employee profile, assign department/position
2. **Active Employment**: Manage day-to-day HR operations
3. **Status Changes**: Handle promotions, transfers, leave
4. **Termination**: Process employee exit

### Leave Management Workflow
1. **Request**: Employee submits leave request
2. **Validation**: Check leave balance and policies
3. **Approval**: Manager reviews and approves/rejects
4. **Processing**: Update leave balance and calendar
5. **Notification**: Inform relevant parties

### Payroll Processing
1. **Generation**: Calculate salary based on attendance/hours
2. **Review**: HR reviews payroll details
3. **Approval**: Manager approves payroll
4. **Payment**: Process salary payment
5. **Documentation**: Generate payslips and records

## Configuration

### Leave Types Setup
```php
// Example leave type configuration
[
    'name' => 'Annual Leave',
    'code' => 'annual',
    'days_per_year' => 20,
    'requires_approval' => true,
    'carry_forward_allowed' => true,
    'max_carry_forward_days' => 5
]
```

### Payroll Components
```php
// Example payroll items
[
    'earnings' => [
        'basic_salary',
        'overtime',
        'bonus',
        'allowances'
    ],
    'deductions' => [
        'income_tax',
        'social_security',
        'health_insurance',
        'loan_repayment'
    ]
]
```

## Notifications

The HR module integrates with the notification service to send:
- Welcome emails for new employees
- Leave request notifications to managers
- Leave approval/rejection notifications
- Payroll approval notifications
- Status change notifications
- Termination notifications

## Security & Permissions

### Role-Based Access
- **HR Manager**: Full access to all HR functions
- **Department Head**: Access to department employees
- **Employee**: Access to own profile and leave requests
- **Admin**: System-wide access

### Data Protection
- Sensitive employee data encryption
- Audit trails for all changes
- Secure document storage
- GDPR compliance features

## Integration Points

### With Other Modules
- **Auth**: User account management
- **School**: Multi-school support
- **Attendance**: Integration with attendance tracking
- **Notification**: Email/SMS notifications

### External Systems
- **Payroll Services**: Integration with external payroll providers
- **Banking**: Direct deposit processing
- **Government**: Tax and compliance reporting

## Reporting & Analytics

### Available Reports
- Employee demographics
- Leave utilization reports
- Payroll summaries
- Department statistics
- Compliance reports

### Dashboard Metrics
- Total employees
- New hires vs terminations
- Leave requests pending approval
- Payroll processing status
- Department-wise breakdowns

## Best Practices

### Employee Management
- Regular profile updates
- Performance tracking
- Skills development tracking
- Career progression planning

### Leave Management
- Clear leave policies
- Advance planning
- Fair approval process
- Accurate record keeping

### Payroll Processing
- Regular payroll cycles
- Accurate time tracking
- Compliance with regulations
- Secure payment processing

## Troubleshooting

### Common Issues
1. **Leave Balance Calculation**: Check leave type configuration
2. **Payroll Errors**: Verify employee salary and deduction setup
3. **Approval Workflow**: Ensure manager hierarchy is correct
4. **Notification Issues**: Check email configuration

### Support
For technical support or feature requests, contact the development team or refer to the main system documentation.