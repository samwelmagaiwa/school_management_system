<?php

namespace App\Services;

use App\Models\Fee;

use Illuminate\Support\Facades\DB;

class FeeService
{
    /**
     * Create a new fee
     */
    public function createFee(array $data): Fee
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = $data['status'] ?? 'Pending';
            return Fee::create($data);
        });
    }

    /**
     * Update an existing fee
     */
    public function updateFee(Fee $fee, array $data): Fee
    {
        return DB::transaction(function () use ($fee, $data) {
            $fee->update($data);
            return $fee;
        });
    }

    /**
     * Delete a fee
     */
    public function deleteFee(Fee $fee): bool
    {
        return DB::transaction(function () use ($fee) {
            return $fee->delete();
        });
    }

    /**
     * Mark fee as paid
     */
    public function markAsPaid(Fee $fee, array $paymentData): Fee
    {
        return DB::transaction(function () use ($fee, $paymentData) {
            $fee->update([
                'status' => 'Paid',
                'paid_date' => $paymentData['paid_date'] ?? now(),
                'payment_method' => $paymentData['payment_method'],
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'notes' => $paymentData['notes'] ?? $fee->notes,
            ]);
            
            return $fee;
        });
    }

    /**
     * Get fee statistics
     */
    public function getFeeStatistics(?int $schoolId = null): array
    {
        $query = Fee::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalFees = $query->count();
        $totalAmount = $query->sum('amount');
        $paidAmount = $query->where('status', 'Paid')->sum('amount');
        $pendingAmount = $query->where('status', 'Pending')->sum('amount');
        $overdueAmount = $query->overdue()->sum('amount');

        // Statistics by status
        $statusStats = [];
        foreach (Fee::STATUSES as $status) {
            $statusQuery = clone $query;
            $statusStats[$status] = [
                'count' => $statusQuery->where('status', $status)->count(),
                'amount' => $statusQuery->where('status', $status)->sum('amount')
            ];
        }

        // Statistics by type
        $typeStats = [];
        foreach (Fee::TYPES as $type) {
            $typeQuery = clone $query;
            $typeStats[$type] = [
                'count' => $typeQuery->where('type', $type)->count(),
                'amount' => $typeQuery->where('type', $type)->sum('amount')
            ];
        }

        return [
            'total_fees' => $totalFees,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'overdue_amount' => $overdueAmount,
            'collection_rate' => $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0,
            'by_status' => $statusStats,
            'by_type' => $typeStats,
        ];
    }

    /**
     * Generate bulk fees for students
     */
    public function generateBulkFees(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $count = 0;
            
            foreach ($data['student_ids'] as $studentId) {
                Fee::create([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'type' => $data['type'],
                    'frequency' => $data['frequency'],
                    'school_id' => $data['school_id'],
                    'student_id' => $studentId,
                    'due_date' => $data['due_date'],
                    'status' => 'Pending',
                ]);
                $count++;
            }
            
            return $count;
        });
    }

    /**
     * Apply late fees to overdue payments
     */
    public function applyLateFees(): int
    {
        return DB::transaction(function () {
            $overdueFees = Fee::where('status', 'Pending')
                             ->where('due_date', '<', now())
                             ->where('late_fee', 0)
                             ->get();

            $count = 0;
            foreach ($overdueFees as $fee) {
                $daysOverdue = now()->diffInDays($fee->due_date);
                $lateFee = $fee->amount * 0.01 * $daysOverdue; // 1% per day
                
                $fee->update([
                    'late_fee' => min($lateFee, $fee->amount * 0.1), // Max 10% of original amount
                    'status' => 'Overdue'
                ]);
                $count++;
            }
            
            return $count;
        });
    }

    /**
     * Get fees by student
     */
    public function getFeesByStudent(int $studentId): \Illuminate\Database\Eloquent\Collection
    {
        return Fee::where('student_id', $studentId)
                  ->with(['school', 'class', 'subject'])
                  ->orderBy('due_date', 'desc')
                  ->get();
    }
    
    /**
     * Export fees data
     */
    public function exportFees(array $filters, string $format = 'excel')
    {
        // Build query with filters
        $query = Fee::with(['school', 'student.user', 'class', 'subject']);
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }
        
        $fees = $query->get();
        
        // Generate export file
        $filename = 'fees_export_' . date('Y-m-d_H-i-s') . ($format === 'excel' ? '.xlsx' : '.csv');
        
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        return response()->streamDownload(function () use ($fees) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, [
                'Fee Name', 'Student Name', 'Admission No', 'Class', 'Amount', 
                'Late Fee', 'Net Amount', 'Type', 'Status', 'Due Date', 'Paid Date'
            ]);
            
            // Write data
            foreach ($fees as $fee) {
                fputcsv($output, [
                    $fee->name,
                    $fee->student->user->name ?? '',
                    $fee->student->admission_no ?? '',
                    $fee->class->name ?? '',
                    $fee->amount,
                    $fee->late_fee,
                    $fee->net_amount,
                    $fee->type,
                    $fee->status,
                    $fee->due_date,
                    $fee->paid_date
                ]);
            }
            
            fclose($output);
        }, $filename, $headers);
    }
    
    /**
     * Get student fees with filtering and pagination
     */
    public function getStudentFees(int $studentId, array $params = []): array
    {
        $query = Fee::where('student_id', $studentId)
                   ->with(['school', 'class', 'subject']);
        
        // Apply filters
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }
        
        if (isset($params['date_from'])) {
            $query->where('due_date', '>=', $params['date_from']);
        }
        
        if (isset($params['date_to'])) {
            $query->where('due_date', '<=', $params['date_to']);
        }
        
        $fees = $query->orderBy('due_date', 'desc')->get();
        
        // Calculate summary
        $summary = [
            'total_fees' => $fees->count(),
            'total_amount' => $fees->sum('amount'),
            'paid_amount' => $fees->where('status', 'Paid')->sum('amount'),
            'pending_amount' => $fees->where('status', 'Pending')->sum('amount'),
            'overdue_amount' => $fees->where('status', 'Overdue')->sum('amount'),
            'late_fee_amount' => $fees->sum('late_fee')
        ];
        
        return [
            'fees' => $fees,
            'summary' => $summary
        ];
    }
    
    /**
     * Generate fee invoice PDF
     */
    public function generateInvoice(Fee $fee)
    {
        // Load relationships
        $fee->load(['school', 'student.user', 'class', 'subject']);
        
        // Generate invoice data
        $invoiceData = [
            'invoice_number' => 'INV-' . str_pad($fee->id, 6, '0', STR_PAD_LEFT),
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => $fee->due_date,
            'fee' => $fee,
            'school' => $fee->school,
            'student' => $fee->student
        ];
        
        // In a real implementation, you would use a PDF library like DomPDF
        // For now, returning a simple response
        $filename = 'invoice_' . $invoiceData['invoice_number'] . '.pdf';
        
        return response()->streamDownload(function () use ($invoiceData) {
            echo "INVOICE\n";
            echo "Invoice Number: {$invoiceData['invoice_number']}\n";
            echo "Date: {$invoiceData['invoice_date']}\n";
            echo "Due Date: {$invoiceData['due_date']}\n\n";
            echo "Bill To:\n";
            echo "Student: {$invoiceData['student']->user->name}\n";
            echo "Admission No: {$invoiceData['student']->admission_no}\n\n";
            echo "Fee Details:\n";
            echo "Description: {$invoiceData['fee']->name}\n";
            echo "Amount: $" . number_format($invoiceData['fee']->amount, 2) . "\n";
            echo "Late Fee: $" . number_format($invoiceData['fee']->late_fee, 2) . "\n";
            echo "Total Amount: $" . number_format($invoiceData['fee']->net_amount, 2) . "\n";
        }, $filename, [
            'Content-Type' => 'application/pdf'
        ]);
    }
    
    /**
     * Generate payment receipt PDF
     */
    public function generateReceipt(Fee $fee)
    {
        // Load relationships
        $fee->load(['school', 'student.user', 'class', 'subject']);
        
        // Generate receipt data
        $receiptData = [
            'receipt_number' => 'RCP-' . str_pad($fee->id, 6, '0', STR_PAD_LEFT),
            'receipt_date' => now()->format('Y-m-d'),
            'payment_date' => $fee->paid_date,
            'fee' => $fee,
            'school' => $fee->school,
            'student' => $fee->student
        ];
        
        // In a real implementation, you would use a PDF library
        $filename = 'receipt_' . $receiptData['receipt_number'] . '.pdf';
        
        return response()->streamDownload(function () use ($receiptData) {
            echo "PAYMENT RECEIPT\n";
            echo "Receipt Number: {$receiptData['receipt_number']}\n";
            echo "Receipt Date: {$receiptData['receipt_date']}\n";
            echo "Payment Date: {$receiptData['payment_date']}\n\n";
            echo "Received From:\n";
            echo "Student: {$receiptData['student']->user->name}\n";
            echo "Admission No: {$receiptData['student']->admission_no}\n\n";
            echo "Payment Details:\n";
            echo "Fee: {$receiptData['fee']->name}\n";
            echo "Amount Paid: $" . number_format($receiptData['fee']->net_amount, 2) . "\n";
            echo "Payment Method: {$receiptData['fee']->payment_method}\n";
            if ($receiptData['fee']->transaction_id) {
                echo "Transaction ID: {$receiptData['fee']->transaction_id}\n";
            }
        }, $filename, [
            'Content-Type' => 'application/pdf'
        ]);
    }
}
