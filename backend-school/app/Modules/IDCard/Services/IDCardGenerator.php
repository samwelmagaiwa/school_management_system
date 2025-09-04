<?php

namespace App\Modules\IDCard\Services;

use App\Modules\IDCard\Models\IDCard;
use App\Modules\Student\Models\Student;
use App\Modules\Teacher\Models\Teacher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IDCardGenerator
{
    /**
     * Generate a new ID card
     */
    public function generate(array $data): IDCard
    {
        $cardNumber = $this->generateCardNumber($data['school_id'], $data['type']);
        
        $idCardData = [
            'school_id' => $data['school_id'],
            'card_number' => $cardNumber,
            'type' => $data['type'],
            'issue_date' => now(),
            'expiry_date' => now()->addYears($data['expiry_years'] ?? 1),
            'template_used' => $data['template'] ?? 'default',
            'is_active' => true,
        ];

        // Set student or teacher ID
        if ($data['type'] === IDCard::TYPE_STUDENT) {
            $idCardData['student_id'] = $data['student_id'];
            $holder = Student::find($data['student_id']);
        } else {
            $idCardData['teacher_id'] = $data['teacher_id'];
            $holder = Teacher::find($data['teacher_id']);
        }

        $idCard = IDCard::create($idCardData);

        // Generate QR code if requested
        if ($data['include_qr_code'] ?? true) {
            $this->generateQRCode($idCard);
        }

        // Process photo if provided
        if (isset($data['photo'])) {
            $this->processPhoto($idCard, $data['photo']);
        }

        return $idCard;
    }

    /**
     * Regenerate an existing ID card
     */
    public function regenerate(IDCard $idCard): IDCard
    {
        // Deactivate old card
        $idCard->update(['is_active' => false]);

        // Create new card with same data
        $data = [
            'school_id' => $idCard->school_id,
            'type' => $idCard->type,
            'template' => $idCard->template_used,
            'expiry_years' => 1,
            'include_qr_code' => true,
        ];

        if ($idCard->type === IDCard::TYPE_STUDENT) {
            $data['student_id'] = $idCard->student_id;
        } else {
            $data['teacher_id'] = $idCard->teacher_id;
        }

        return $this->generate($data);
    }

    /**
     * Bulk generate ID cards
     */
    public function bulkGenerate(array $data): array
    {
        $idCards = [];

        if ($data['type'] === IDCard::TYPE_STUDENT) {
            foreach ($data['student_ids'] as $studentId) {
                $cardData = array_merge($data, ['student_id' => $studentId]);
                unset($cardData['student_ids']);
                
                try {
                    $idCards[] = $this->generate($cardData);
                } catch (\Exception $e) {
                    // Log error and continue with next student
                    \Log::error("Failed to generate ID card for student {$studentId}: " . $e->getMessage());
                }
            }
        } else {
            foreach ($data['teacher_ids'] as $teacherId) {
                $cardData = array_merge($data, ['teacher_id' => $teacherId]);
                unset($cardData['teacher_ids']);
                
                try {
                    $idCards[] = $this->generate($cardData);
                } catch (\Exception $e) {
                    // Log error and continue with next teacher
                    \Log::error("Failed to generate ID card for teacher {$teacherId}: " . $e->getMessage());
                }
            }
        }

        return $idCards;
    }

    /**
     * Download ID card as PDF
     */
    public function downloadPDF(IDCard $idCard)
    {
        // This would integrate with a PDF generation library like TCPDF or DomPDF
        // For now, return a placeholder response
        
        $fileName = "id_card_{$idCard->card_number}.pdf";
        
        // Generate PDF content based on template
        $pdfContent = $this->generatePDFContent($idCard);
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }

    /**
     * Generate unique card number
     */
    private function generateCardNumber(int $schoolId, string $type): string
    {
        $prefix = $type === IDCard::TYPE_STUDENT ? 'STU' : 'TCH';
        $year = date('Y');
        $schoolCode = str_pad($schoolId, 3, '0', STR_PAD_LEFT);
        
        // Get next sequence number
        $lastCard = IDCard::where('school_id', $schoolId)
            ->where('type', $type)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastCard ? (int)substr($lastCard->card_number, -4) + 1 : 1;
        $sequenceStr = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$year}{$schoolCode}{$sequenceStr}";
    }

    /**
     * Generate QR code for ID card
     */
    private function generateQRCode(IDCard $idCard): void
    {
        // This would integrate with a QR code generation library
        // For now, just set a placeholder path
        
        $qrData = json_encode([
            'card_number' => $idCard->card_number,
            'type' => $idCard->type,
            'school_id' => $idCard->school_id,
            'issue_date' => $idCard->issue_date->format('Y-m-d'),
        ]);
        
        $fileName = "qr_codes/qr_{$idCard->card_number}.png";
        
        // Generate QR code and save to storage
        // $qrCode = QrCode::format('png')->size(200)->generate($qrData);
        // Storage::disk('public')->put($fileName, $qrCode);
        
        $idCard->update(['qr_code_path' => $fileName]);
    }

    /**
     * Process and store photo
     */
    private function processPhoto(IDCard $idCard, $photo): void
    {
        $fileName = "id_photos/{$idCard->card_number}." . $photo->getClientOriginalExtension();
        $path = $photo->storeAs('public', $fileName);
        
        $idCard->update(['photo_path' => str_replace('public/', '', $path)]);
    }

    /**
     * Generate PDF content for ID card
     */
    private function generatePDFContent(IDCard $idCard): string
    {
        // This would generate actual PDF content using a template
        // For now, return placeholder content
        
        $holder = $idCard->holder;
        $holderName = $holder ? $holder->user->name : 'Unknown';
        
        return "PDF content for ID Card: {$idCard->card_number} - {$holderName}";
    }
}