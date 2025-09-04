<?php

namespace App\Services;

use App\Modules\Auth\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Teacher\Models\Teacher;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to user(s)
     */
    public function send(array $recipients, string $type, array $data): bool
    {
        try {
            foreach ($recipients as $recipient) {
                $this->sendToRecipient($recipient, $type, $data);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Notification sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send admission confirmation notification
     */
    public function sendAdmissionConfirmation(Student $student): bool
    {
        $data = [
            'student_name' => $student->user->name,
            'student_id' => $student->student_id,
            'school_name' => $student->school->name,
            'class_name' => $student->class->full_name ?? 'N/A',
            'admission_date' => $student->admission_date->format('d M Y'),
        ];

        return $this->send(
            [$student->user->email, $student->parent_email],
            'admission_confirmation',
            $data
        );
    }

    /**
     * Send fee reminder notification
     */
    public function sendFeeReminder(Student $student, array $pendingFees): bool
    {
        $data = [
            'student_name' => $student->user->name,
            'parent_name' => $student->parent_name,
            'school_name' => $student->school->name,
            'pending_fees' => $pendingFees,
            'total_amount' => array_sum(array_column($pendingFees, 'amount')),
        ];

        return $this->send(
            [$student->parent_email],
            'fee_reminder',
            $data
        );
    }

    /**
     * Send exam result notification
     */
    public function sendExamResults(Student $student, array $results): bool
    {
        $data = [
            'student_name' => $student->user->name,
            'parent_name' => $student->parent_name,
            'school_name' => $student->school->name,
            'class_name' => $student->class->full_name ?? 'N/A',
            'results' => $results,
            'overall_grade' => $student->getOverallGrade(),
        ];

        return $this->send(
            [$student->user->email, $student->parent_email],
            'exam_results',
            $data
        );
    }

    /**
     * Send attendance alert
     */
    public function sendAttendanceAlert(Student $student, float $attendancePercentage): bool
    {
        if ($attendancePercentage >= 75) {
            return true; // No alert needed for good attendance
        }

        $data = [
            'student_name' => $student->user->name,
            'parent_name' => $student->parent_name,
            'school_name' => $student->school->name,
            'attendance_percentage' => $attendancePercentage,
            'required_percentage' => 75,
        ];

        return $this->send(
            [$student->parent_email],
            'attendance_alert',
            $data
        );
    }

    /**
     * Send teacher assignment notification
     */
    public function sendTeacherAssignment(Teacher $teacher, array $assignments): bool
    {
        $data = [
            'teacher_name' => $teacher->user->name,
            'school_name' => $teacher->school->name,
            'assignments' => $assignments,
        ];

        return $this->send(
            [$teacher->user->email],
            'teacher_assignment',
            $data
        );
    }

    /**
     * Send school announcement
     */
    public function sendSchoolAnnouncement(School $school, string $announcement, array $targetGroups = ['all']): bool
    {
        $recipients = $this->getAnnouncementRecipients($school, $targetGroups);
        
        $data = [
            'school_name' => $school->name,
            'announcement' => $announcement,
            'date' => now()->format('d M Y'),
        ];

        return $this->send($recipients, 'school_announcement', $data);
    }

    /**
     * Send employee welcome notification
     */
    public function sendEmployeeWelcome($employee): bool
    {
        $data = [
            'employee_name' => $employee->full_name,
            'employee_id' => $employee->employee_id,
            'school_name' => $employee->school->name,
            'department' => $employee->department->name ?? 'N/A',
            'position' => $employee->position->title ?? 'N/A',
            'start_date' => $employee->hire_date->format('d M Y'),
        ];

        return $this->send(
            [$employee->email],
            'employee_welcome',
            $data
        );
    }

    /**
     * Send leave request notification
     */
    public function sendLeaveRequestNotification($leaveRequest): bool
    {
        $manager = $leaveRequest->employee->manager;
        if (!$manager) return true;

        $data = [
            'employee_name' => $leaveRequest->employee->full_name,
            'leave_type' => $leaveRequest->leaveType->name,
            'start_date' => $leaveRequest->start_date->format('d M Y'),
            'end_date' => $leaveRequest->end_date->format('d M Y'),
            'days_requested' => $leaveRequest->days_requested,
            'reason' => $leaveRequest->reason,
        ];

        return $this->send(
            [$manager->email],
            'leave_request_notification',
            $data
        );
    }

    /**
     * Send leave approval notification
     */
    public function sendLeaveApprovalNotification($leaveRequest): bool
    {
        $data = [
            'employee_name' => $leaveRequest->employee->full_name,
            'leave_type' => $leaveRequest->leaveType->name,
            'start_date' => $leaveRequest->start_date->format('d M Y'),
            'end_date' => $leaveRequest->end_date->format('d M Y'),
            'approver_name' => $leaveRequest->approver->full_name ?? 'System',
            'approver_comments' => $leaveRequest->approver_comments,
        ];

        return $this->send(
            [$leaveRequest->employee->email],
            'leave_approval',
            $data
        );
    }

    /**
     * Send leave rejection notification
     */
    public function sendLeaveRejectionNotification($leaveRequest): bool
    {
        $data = [
            'employee_name' => $leaveRequest->employee->full_name,
            'leave_type' => $leaveRequest->leaveType->name,
            'start_date' => $leaveRequest->start_date->format('d M Y'),
            'end_date' => $leaveRequest->end_date->format('d M Y'),
            'approver_name' => $leaveRequest->approver->full_name ?? 'System',
            'rejection_reason' => $leaveRequest->approver_comments,
        ];

        return $this->send(
            [$leaveRequest->employee->email],
            'leave_rejection',
            $data
        );
    }

    /**
     * Send payroll approval notification
     */
    public function sendPayrollApprovalNotification($payroll): bool
    {
        $data = [
            'employee_name' => $payroll->employee->full_name,
            'payroll_number' => $payroll->payroll_number,
            'pay_period' => $payroll->pay_period_start->format('M Y'),
            'net_salary' => $payroll->net_salary,
            'approver_name' => $payroll->approver->full_name ?? 'System',
        ];

        return $this->send(
            [$payroll->employee->email],
            'payroll_approval',
            $data
        );
    }

    /**
     * Send payroll payment notification
     */
    public function sendPayrollPaymentNotification($payroll): bool
    {
        $data = [
            'employee_name' => $payroll->employee->full_name,
            'payroll_number' => $payroll->payroll_number,
            'pay_period' => $payroll->pay_period_start->format('M Y'),
            'net_salary' => $payroll->net_salary,
            'payment_date' => $payroll->pay_date->format('d M Y'),
            'payment_method' => $payroll->payment_method,
        ];

        return $this->send(
            [$payroll->employee->email],
            'payroll_payment',
            $data
        );
    }

    /**
     * Send status change notification
     */
    public function sendStatusChangeNotification($employee, $oldStatus, $newStatus): bool
    {
        $data = [
            'employee_name' => $employee->full_name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'change_date' => now()->format('d M Y'),
            'reason' => $employee->status_change_reason,
        ];

        return $this->send(
            [$employee->email],
            'status_change',
            $data
        );
    }

    /**
     * Send welcome back notification
     */
    public function sendWelcomeBackNotification($employee): bool
    {
        $data = [
            'employee_name' => $employee->full_name,
            'return_date' => now()->format('d M Y'),
        ];

        return $this->send(
            [$employee->email],
            'welcome_back',
            $data
        );
    }

    /**
     * Send termination notification
     */
    public function sendTerminationNotification($employee): bool
    {
        $data = [
            'employee_name' => $employee->full_name,
            'termination_date' => $employee->termination_date->format('d M Y'),
            'reason' => $employee->status_change_reason,
        ];

        return $this->send(
            [$employee->email],
            'termination_notification',
            $data
        );
    }

    /**
     * Send bulk notifications
     */
    public function sendBulk(array $notifications): array
    {
        $results = [];
        
        foreach ($notifications as $notification) {
            $results[] = [
                'id' => $notification['id'] ?? uniqid(),
                'success' => $this->send(
                    $notification['recipients'],
                    $notification['type'],
                    $notification['data']
                ),
            ];
        }
        
        return $results;
    }

    /**
     * Send notification to individual recipient
     */
    private function sendToRecipient(string $email, string $type, array $data): void
    {
        // Here you would integrate with your preferred notification service
        // For example: email, SMS, push notifications, etc.
        
        switch ($type) {
            case 'admission_confirmation':
                $this->sendEmail($email, 'Admission Confirmation', 'emails.admission_confirmation', $data);
                break;
                
            case 'fee_reminder':
                $this->sendEmail($email, 'Fee Payment Reminder', 'emails.fee_reminder', $data);
                break;
                
            case 'exam_results':
                $this->sendEmail($email, 'Exam Results Available', 'emails.exam_results', $data);
                break;
                
            case 'attendance_alert':
                $this->sendEmail($email, 'Attendance Alert', 'emails.attendance_alert', $data);
                break;
                
            case 'teacher_assignment':
                $this->sendEmail($email, 'New Teaching Assignment', 'emails.teacher_assignment', $data);
                break;
                
            case 'school_announcement':
                $this->sendEmail($email, 'School Announcement', 'emails.school_announcement', $data);
                break;
                
            case 'employee_welcome':
                $this->sendEmail($email, 'Welcome to the Team', 'emails.employee_welcome', $data);
                break;
                
            case 'leave_request_notification':
                $this->sendEmail($email, 'New Leave Request', 'emails.leave_request_notification', $data);
                break;
                
            case 'leave_approval':
                $this->sendEmail($email, 'Leave Request Approved', 'emails.leave_approval', $data);
                break;
                
            case 'leave_rejection':
                $this->sendEmail($email, 'Leave Request Rejected', 'emails.leave_rejection', $data);
                break;
                
            case 'payroll_approval':
                $this->sendEmail($email, 'Payroll Approved', 'emails.payroll_approval', $data);
                break;
                
            case 'payroll_payment':
                $this->sendEmail($email, 'Salary Payment Processed', 'emails.payroll_payment', $data);
                break;
                
            case 'status_change':
                $this->sendEmail($email, 'Employment Status Change', 'emails.status_change', $data);
                break;
                
            case 'welcome_back':
                $this->sendEmail($email, 'Welcome Back', 'emails.welcome_back', $data);
                break;
                
            case 'termination_notification':
                $this->sendEmail($email, 'Employment Termination', 'emails.termination_notification', $data);
                break;
                
            default:
                Log::warning("Unknown notification type: {$type}");
        }
    }

    /**
     * Send email notification
     */
    private function sendEmail(string $email, string $subject, string $template, array $data): void
    {
        // This would use Laravel's Mail facade to send emails
        // For now, just log the notification
        Log::info("Email notification sent", [
            'to' => $email,
            'subject' => $subject,
            'template' => $template,
            'data' => $data,
        ]);
        
        // Uncomment when email templates are ready:
        // Mail::send($template, $data, function ($message) use ($email, $subject) {
        //     $message->to($email)->subject($subject);
        // });
    }

    /**
     * Get recipients for school announcements
     */
    private function getAnnouncementRecipients(School $school, array $targetGroups): array
    {
        $recipients = [];
        
        foreach ($targetGroups as $group) {
            switch ($group) {
                case 'all':
                    $recipients = array_merge($recipients, $school->users()->pluck('email')->toArray());
                    break;
                    
                case 'students':
                    $recipients = array_merge($recipients, $school->students()->with('user')->get()->pluck('user.email')->toArray());
                    break;
                    
                case 'teachers':
                    $recipients = array_merge($recipients, $school->teachers()->with('user')->get()->pluck('user.email')->toArray());
                    break;
                    
                case 'parents':
                    $recipients = array_merge($recipients, $school->students()->whereNotNull('parent_email')->pluck('parent_email')->toArray());
                    break;
                    
                case 'admins':
                    $recipients = array_merge($recipients, $school->admins()->pluck('email')->toArray());
                    break;
            }
        }
        
        return array_unique(array_filter($recipients));
    }
}