<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds all foreign key constraints after all tables have been created.
     * This ensures proper dependency order and prevents foreign key constraint errors.
     */
    public function up(): void
    {
        // Add foreign key constraints to users table
        if (Schema::hasTable('users') && Schema::hasTable('schools')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
            });
        }

        // Add foreign key constraints to students table
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('classes')) {
                    $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
                }
                if (Schema::hasTable('vehicles')) {
                    $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to teachers table
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to teacher_subjects table
        if (Schema::hasTable('teacher_subjects')) {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                if (Schema::hasTable('teachers')) {
                    $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
                }
                if (Schema::hasTable('subjects')) {
                    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                }
                if (Schema::hasTable('classes')) {
                    $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to classes table
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('teachers')) {
                    $table->foreign('class_teacher_id')->references('id')->on('teachers')->onDelete('set null');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to timetables table
        if (Schema::hasTable('timetables')) {
            Schema::table('timetables', function (Blueprint $table) {
                if (Schema::hasTable('classes')) {
                    $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                }
                if (Schema::hasTable('subjects')) {
                    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                }
                if (Schema::hasTable('teachers')) {
                    $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to subjects table
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to fee_types table
        if (Schema::hasTable('fee_types')) {
            Schema::table('fee_types', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to class_subjects table
        if (Schema::hasTable('class_subjects')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                if (Schema::hasTable('classes')) {
                    $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                }
                if (Schema::hasTable('subjects')) {
                    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                }
                if (Schema::hasTable('teachers')) {
                    $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to fees table
        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) {
                if (Schema::hasTable('students')) {
                    $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                }
                if (Schema::hasTable('fee_types')) {
                    $table->foreign('fee_type_id')->references('id')->on('fee_types')->onDelete('cascade');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('discount_approved_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to fee_payments table
        if (Schema::hasTable('fee_payments')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                if (Schema::hasTable('fees')) {
                    $table->foreign('fee_id')->references('id')->on('fees')->onDelete('cascade');
                }
                if (Schema::hasTable('students')) {
                    $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('collected_by')->references('id')->on('users')->onDelete('restrict');
                    $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
                    $table->foreign('refunded_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to exams table
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
                }
            });
        }

        // Add foreign key constraints to attendances table
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('classes')) {
                    $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                }
                if (Schema::hasTable('students')) {
                    $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                }
                if (Schema::hasTable('subjects')) {
                    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                }
                if (Schema::hasTable('teachers')) {
                    $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('excused_by')->references('id')->on('users')->onDelete('set null');
                    $table->foreign('marked_by')->references('id')->on('users')->onDelete('restrict');
                    $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
                    $table->foreign('last_modified_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to attendance_summaries table
        if (Schema::hasTable('attendance_summaries')) {
            Schema::table('attendance_summaries', function (Blueprint $table) {
                if (Schema::hasTable('students')) {
                    $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                }
                if (Schema::hasTable('classes')) {
                    $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                }
                if (Schema::hasTable('subjects')) {
                    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                }
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('calculated_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to id_cards table
        if (Schema::hasTable('id_cards')) {
            Schema::table('id_cards', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('printed_by')->references('id')->on('users')->onDelete('set null');
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
                    $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasTable('id_cards')) {
                    $table->foreign('replaced_by_card_id')->references('id')->on('id_cards')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to id_card_templates table
        if (Schema::hasTable('id_card_templates')) {
            Schema::table('id_card_templates', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasTable('id_card_templates')) {
                    $table->foreign('parent_template_id')->references('id')->on('id_card_templates')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to departments table
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('employees')) {
                    $table->foreign('head_id')->references('id')->on('employees')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to positions table
        if (Schema::hasTable('positions')) {
            Schema::table('positions', function (Blueprint $table) {
                if (Schema::hasTable('departments')) {
                    $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                }
                if (Schema::hasTable('positions')) {
                    $table->foreign('reports_to_position_id')->references('id')->on('positions')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to employees table
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
                if (Schema::hasTable('departments')) {
                    $table->foreign('department_id')->references('id')->on('departments')->onDelete('restrict');
                }
                if (Schema::hasTable('positions')) {
                    $table->foreign('position_id')->references('id')->on('positions')->onDelete('restrict');
                }
                if (Schema::hasTable('employees')) {
                    $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
                }
            });
        }

        // Add foreign key constraints to leave_types table
        if (Schema::hasTable('leave_types')) {
            Schema::table('leave_types', function (Blueprint $table) {
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to leave_requests table
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (Schema::hasTable('employees')) {
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                    $table->foreign('approver_id')->references('id')->on('employees')->onDelete('set null');
                }
                if (Schema::hasTable('leave_types')) {
                    $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('restrict');
                }
            });
        }

        // Add foreign key constraints to leave_documents table
        if (Schema::hasTable('leave_documents')) {
            Schema::table('leave_documents', function (Blueprint $table) {
                if (Schema::hasTable('leave_requests')) {
                    $table->foreign('leave_request_id')->references('id')->on('leave_requests')->onDelete('cascade');
                }
                if (Schema::hasTable('employees')) {
                    $table->foreign('uploaded_by')->references('id')->on('employees')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to payrolls table
        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (Schema::hasTable('employees')) {
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                    $table->foreign('approved_by')->references('id')->on('employees')->onDelete('set null');
                }
                if (Schema::hasTable('schools')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints to payroll_items table
        if (Schema::hasTable('payroll_items')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                if (Schema::hasTable('payrolls')) {
                    $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints in reverse order
        $tables = [
            'payroll_items' => ['payroll_id'],
            'payrolls' => ['employee_id', 'school_id', 'approved_by'],
            'leave_documents' => ['leave_request_id', 'uploaded_by'],
            'leave_requests' => ['employee_id', 'leave_type_id', 'approver_id'],
            'leave_types' => ['school_id'],
            'employees' => ['user_id', 'school_id', 'department_id', 'position_id', 'manager_id'],
            'positions' => ['department_id', 'reports_to_position_id'],
            'departments' => ['school_id', 'head_id'],
            'id_card_templates' => ['school_id', 'created_by', 'updated_by', 'parent_template_id'],
            'id_cards' => ['school_id', 'printed_by', 'created_by', 'approved_by', 'replaced_by_card_id'],
            'attendances' => ['school_id', 'class_id', 'student_id', 'subject_id', 'teacher_id', 'academic_year_id', 'excused_by', 'marked_by', 'verified_by', 'last_modified_by'],
            'attendance_summaries' => ['student_id', 'class_id', 'subject_id', 'academic_year_id', 'calculated_by'],
            'exams' => ['school_id', 'academic_year_id', 'created_by'],
            'fees' => ['student_id', 'fee_type_id', 'academic_year_id', 'discount_approved_by'],
            'fee_payments' => ['fee_id', 'student_id', 'collected_by', 'verified_by', 'refunded_by'],
            'subjects' => ['school_id'],
            'fee_types' => ['school_id'],
            'class_subjects' => ['class_id', 'subject_id', 'teacher_id', 'academic_year_id'],
            'timetables' => ['class_id', 'subject_id', 'teacher_id'],
            'classes' => ['school_id', 'class_teacher_id', 'academic_year_id'],
            'teacher_subjects' => ['teacher_id', 'subject_id', 'class_id', 'academic_year_id'],
            'teachers' => ['user_id', 'school_id'],
            'students' => ['user_id', 'school_id', 'class_id', 'vehicle_id'],
            'users' => ['school_id'],
        ];

        foreach ($tables as $table => $foreignKeys) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tableSchema) use ($foreignKeys) {
                    foreach ($foreignKeys as $foreignKey) {
                        try {
                            $tableSchema->dropForeign([$foreignKey]);
                        } catch (Exception $e) {
                            // Ignore if foreign key doesn't exist
                        }
                    }
                });
            }
        }
    }
};