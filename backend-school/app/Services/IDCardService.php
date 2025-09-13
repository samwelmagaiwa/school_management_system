<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\School;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class IDCardService
{
    /**
     * Get paginated ID cards with filters
     */
    public function getIDCards(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = IDCard::with(['school', 'student.user', 'teacher.user'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where('card_number', 'like', "%{$search}%")
                    ->orWhereHas('student.user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('teacher.user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                    });
            })
            ->when($filters['school_id'] ?? null, function ($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->when($filters['type'] ?? null, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($filters['is_active'] ?? null, function ($query, $isActive) {
                return $query->where('is_active', $isActive);
            })
            ->when($filters['expired'] ?? null, function ($query, $expired) {
                if ($expired) {
                    return $query->where('expiry_date', '<', now());
                } else {
                    return $query->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', now());
                    });
                }
            })
            ->orderBy('created_at', 'desc');

        $idCards = $query->paginate($perPage);

        ActivityLogger::log('ID Cards List Retrieved', 'IDCard', [
            'filters' => $filters,
            'total_results' => $idCards->total(),
            'per_page' => $perPage
        ]);

        return $idCards;
    }

    /**
     * Generate ID card for a student
     */
    public function generateStudentIDCard(int $studentId, array $options = []): IDCard
    {
        DB::beginTransaction();
        
        try {
            $student = Student::with(['user', 'school', 'class'])->findOrFail($studentId);
            
            // Check if student already has an active ID card
            $existingCard = IDCard::where('student_id', $studentId)
                ->where('is_active', true)
                ->first();
                
            if ($existingCard && !($options['force_regenerate'] ?? false)) {
                throw new \Exception('Student already has an active ID card. Use force regenerate option to create a new one.');
            }

            // Deactivate existing card if regenerating
            if ($existingCard && ($options['force_regenerate'] ?? false)) {
                $existingCard->update(['is_active' => false]);
            }

            $cardData = [
                'school_id' => $student->school_id,
                'student_id' => $studentId,
                'teacher_id' => null,
                'card_number' => $this->generateCardNumber($student->school_id, 'student'),
                'type' => IDCard::TYPE_STUDENT,
                'issue_date' => now(),
                'expiry_date' => $options['expiry_date'] ?? now()->addYear(),
                'template_used' => $options['template'] ?? 'default_student',
                'is_active' => true
            ];

            $idCard = IDCard::create($cardData);

            // Generate photo and QR code
            $this->generateCardAssets($idCard, $student->user);

            // Clear related caches
            $this->clearIDCardCaches();

            DB::commit();

            ActivityLogger::log('Student ID Card Generated', 'IDCard', [
                'id_card_id' => $idCard->id,
                'card_number' => $idCard->card_number,
                'student_id' => $studentId,
                'student_name' => $student->user->full_name,
                'school_id' => $student->school_id
            ]);

            return $idCard->load(['school', 'student.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Student ID Card Generation Failed', 'IDCard', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'options' => $options
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Generate ID card for a teacher
     */
    public function generateTeacherIDCard(int $teacherId, array $options = []): IDCard
    {
        DB::beginTransaction();
        
        try {
            $teacher = Teacher::with(['user', 'school', 'department'])->findOrFail($teacherId);
            
            // Check if teacher already has an active ID card
            $existingCard = IDCard::where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->first();
                
            if ($existingCard && !($options['force_regenerate'] ?? false)) {
                throw new \Exception('Teacher already has an active ID card. Use force regenerate option to create a new one.');
            }

            // Deactivate existing card if regenerating
            if ($existingCard && ($options['force_regenerate'] ?? false)) {
                $existingCard->update(['is_active' => false]);
            }

            $cardData = [
                'school_id' => $teacher->school_id,
                'student_id' => null,
                'teacher_id' => $teacherId,
                'card_number' => $this->generateCardNumber($teacher->school_id, 'teacher'),
                'type' => IDCard::TYPE_TEACHER,
                'issue_date' => now(),
                'expiry_date' => $options['expiry_date'] ?? now()->addYears(2),
                'template_used' => $options['template'] ?? 'default_teacher',
                'is_active' => true
            ];

            $idCard = IDCard::create($cardData);

            // Generate photo and QR code
            $this->generateCardAssets($idCard, $teacher->user);

            // Clear related caches
            $this->clearIDCardCaches();

            DB::commit();

            ActivityLogger::log('Teacher ID Card Generated', 'IDCard', [
                'id_card_id' => $idCard->id,
                'card_number' => $idCard->card_number,
                'teacher_id' => $teacherId,
                'teacher_name' => $teacher->user->full_name,
                'school_id' => $teacher->school_id
            ]);

            return $idCard->load(['school', 'teacher.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Teacher ID Card Generation Failed', 'IDCard', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
                'options' => $options
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Bulk generate ID cards
     */
    public function bulkGenerate(array $data): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => 0
        ];

        DB::beginTransaction();
        
        try {
            if ($data['type'] === 'student') {
                $studentIds = $data['student_ids'] ?? [];
                $results['total'] = count($studentIds);
                
                foreach ($studentIds as $studentId) {
                    try {
                        $idCard = $this->generateStudentIDCard($studentId, $data['options'] ?? []);
                        $results['success'][] = [
                            'id' => $studentId,
                            'card_number' => $idCard->card_number,
                            'name' => $idCard->student->user->full_name
                        ];
                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'id' => $studentId,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            } elseif ($data['type'] === 'teacher') {
                $teacherIds = $data['teacher_ids'] ?? [];
                $results['total'] = count($teacherIds);
                
                foreach ($teacherIds as $teacherId) {
                    try {
                        $idCard = $this->generateTeacherIDCard($teacherId, $data['options'] ?? []);
                        $results['success'][] = [
                            'id' => $teacherId,
                            'card_number' => $idCard->card_number,
                            'name' => $idCard->teacher->user->full_name
                        ];
                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'id' => $teacherId,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            DB::commit();

            ActivityLogger::log('Bulk ID Cards Generated', 'IDCard', [
                'type' => $data['type'],
                'total' => $results['total'],
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed'])
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Bulk ID Card Generation Failed', 'IDCard', [
                'type' => $data['type'] ?? 'unknown',
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Deactivate an ID card
     */
    public function deactivateIDCard(IDCard $idCard, string $reason = null): IDCard
    {
        DB::beginTransaction();
        
        try {
            $idCard->update([
                'is_active' => false,
                'deactivation_reason' => $reason,
                'deactivated_at' => now()
            ]);

            // Clear related caches
            $this->clearIDCardCaches();

            DB::commit();

            ActivityLogger::log('ID Card Deactivated', 'IDCard', [
                'id_card_id' => $idCard->id,
                'card_number' => $idCard->card_number,
                'type' => $idCard->type,
                'reason' => $reason
            ]);

            return $idCard;

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('ID Card Deactivation Failed', 'IDCard', [
                'id_card_id' => $idCard->id,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Get ID card statistics
     */
    public function getIDCardStatistics(int $schoolId): array
    {
        $cacheKey = "idcard_statistics_{$schoolId}";
        
        return Cache::remember($cacheKey, 300, function () use ($schoolId) {
            $stats = [
                'total_cards' => IDCard::where('school_id', $schoolId)->count(),
                'active_cards' => IDCard::where('school_id', $schoolId)->where('is_active', true)->count(),
                'student_cards' => IDCard::where('school_id', $schoolId)->where('type', 'student')->count(),
                'teacher_cards' => IDCard::where('school_id', $schoolId)->where('type', 'teacher')->count(),
                'expired_cards' => IDCard::where('school_id', $schoolId)
                    ->where('expiry_date', '<', now())
                    ->count(),
                'expiring_soon' => IDCard::where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->where('expiry_date', '>', now())
                    ->where('expiry_date', '<=', now()->addMonth())
                    ->count(),
                'cards_by_month' => $this->getCardsByMonth($schoolId),
                'template_usage' => $this->getTemplateUsage($schoolId)
            ];

            ActivityLogger::log('ID Card Statistics Retrieved', 'IDCard', [
                'school_id' => $schoolId,
                'statistics' => $stats
            ]);

            return $stats;
        });
    }

    /**
     * Generate card number
     */
    private function generateCardNumber(int $schoolId, string $type): string
    {
        $year = date('Y');
        $prefix = $type === 'student' ? 'STU' : 'TCH';
        
        $lastCard = IDCard::where('school_id', $schoolId)
            ->where('type', $type)
            ->where('card_number', 'like', "{$prefix}{$year}%")
            ->orderBy('card_number', 'desc')
            ->first();

        if ($lastCard) {
            $lastNumber = (int) substr($lastCard->card_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "{$prefix}{$year}" . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate card assets (photo and QR code)
     */
    private function generateCardAssets(IDCard $idCard, $user): void
    {
        // Generate QR code
        $qrData = json_encode([
            'card_number' => $idCard->card_number,
            'type' => $idCard->type,
            'school_id' => $idCard->school_id,
            'issued_at' => $idCard->issue_date->toISOString()
        ]);

        $qrCodePath = "id-cards/qr-codes/{$idCard->card_number}.png";
        $qrCode = QrCode::format('png')->size(200)->generate($qrData);
        Storage::disk('public')->put($qrCodePath, $qrCode);

        // Process photo if user has profile picture
        $photoPath = null;
        if ($user->profile_picture) {
            $photoPath = "id-cards/photos/{$idCard->card_number}.jpg";
            
            // Resize and optimize photo for ID card
            $image = Image::make(Storage::disk('public')->get($user->profile_picture))
                ->fit(300, 400)
                ->encode('jpg', 90);
                
            Storage::disk('public')->put($photoPath, $image);
        }

        // Update ID card with asset paths
        $idCard->update([
            'qr_code_path' => $qrCodePath,
            'photo_path' => $photoPath
        ]);
    }

    /**
     * Get cards by month for statistics
     */
    private function getCardsByMonth(int $schoolId): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = IDCard::where('school_id', $schoolId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $months[] = [
                'month' => $date->format('M Y'),
                'count' => $count
            ];
        }
        
        return $months;
    }

    /**
     * Get template usage statistics
     */
    private function getTemplateUsage(int $schoolId): array
    {
        return IDCard::where('school_id', $schoolId)
            ->selectRaw('template_used, COUNT(*) as count')
            ->groupBy('template_used')
            ->pluck('count', 'template_used')
            ->toArray();
    }

    /**
     * Get available templates
     */
    public function getAvailableTemplates(): array
    {
        return [
            'default_student' => [
                'name' => 'Default Student Template',
                'description' => 'Standard student ID card template',
                'preview' => 'templates/previews/default_student.png'
            ],
            'default_teacher' => [
                'name' => 'Default Teacher Template',
                'description' => 'Standard teacher ID card template',
                'preview' => 'templates/previews/default_teacher.png'
            ],
            'modern_student' => [
                'name' => 'Modern Student Template',
                'description' => 'Modern design student ID card template',
                'preview' => 'templates/previews/modern_student.png'
            ],
            'modern_teacher' => [
                'name' => 'Modern Teacher Template',
                'description' => 'Modern design teacher ID card template',
                'preview' => 'templates/previews/modern_teacher.png'
            ]
        ];
    }

    /**
     * Generate printable ID card
     */
    public function generatePrintableCard(IDCard $idCard): string
    {
        // This would generate a printable PDF or image
        // For now, return a placeholder path
        $printablePath = "id-cards/printable/{$idCard->card_number}.pdf";
        
        // Here you would use a PDF library like TCPDF or DomPDF
        // to generate the actual printable card
        
        ActivityLogger::log('Printable ID Card Generated', 'IDCard', [
            'id_card_id' => $idCard->id,
            'card_number' => $idCard->card_number,
            'printable_path' => $printablePath
        ]);
        
        return $printablePath;
    }

    /**
     * Get ID card generation history
     */
    public function getGenerationHistory(array $filters = []): array
    {
        $query = IDCard::with(['school', 'student.user', 'teacher.user'])
                      ->orderBy('created_at', 'desc');
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('card_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('student.user', function($subQ) use ($filters) {
                      $subQ->where('first_name', 'like', '%' . $filters['search'] . '%')
                           ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
                  })
                  ->orWhereHas('teacher.user', function($subQ) use ($filters) {
                      $subQ->where('first_name', 'like', '%' . $filters['search'] . '%')
                           ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }
        
        $history = $query->paginate(15);
        
        return [
            'data' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total()
            ]
        ];
    }
    
    /**
     * Export ID cards data
     */
    public function exportIDCards(array $filters, string $format = 'excel')
    {
        // Build query with filters
        $query = IDCard::with(['school', 'student.user', 'teacher.user']);
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['expired'])) {
            if ($filters['expired']) {
                $query->where('expiry_date', '<', now());
            } else {
                $query->where('expiry_date', '>=', now());
            }
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('card_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('student.user', function($subQ) use ($filters) {
                      $subQ->where('first_name', 'like', '%' . $filters['search'] . '%')
                           ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
                  })
                  ->orWhereHas('teacher.user', function($subQ) use ($filters) {
                      $subQ->where('first_name', 'like', '%' . $filters['search'] . '%')
                           ->orWhere('last_name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }
        
        $idCards = $query->get();
        
        // Generate export file
        $filename = 'id_cards_export_' . date('Y-m-d_H-i-s') . ($format === 'excel' ? '.xlsx' : '.csv');
        
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        return response()->streamDownload(function () use ($idCards) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, [
                'Card Number', 'Type', 'Holder Name', 'School', 'Issue Date', 
                'Expiry Date', 'Status', 'Template', 'Is Active'
            ]);
            
            // Write data
            foreach ($idCards as $idCard) {
                $holderName = $idCard->type === 'student' 
                    ? ($idCard->student->user->name ?? 'Unknown')
                    : ($idCard->teacher->user->name ?? 'Unknown');
                    
                fputcsv($output, [
                    $idCard->card_number,
                    ucfirst($idCard->type),
                    $holderName,
                    $idCard->school->name ?? '',
                    $idCard->issue_date,
                    $idCard->expiry_date,
                    $idCard->isExpired() ? 'Expired' : 'Valid',
                    $idCard->template_used,
                    $idCard->is_active ? 'Yes' : 'No'
                ]);
            }
            
            fclose($output);
        }, $filename, $headers);
    }
    
    /**
     * Bulk print ID cards
     */
    public function bulkPrintIDCards(array $idCardIds, ?string $template = null): array
    {
        $idCards = IDCard::with(['school', 'student.user', 'teacher.user'])
                         ->whereIn('id', $idCardIds)
                         ->get();
        
        $printableFiles = [];
        
        foreach ($idCards as $idCard) {
            try {
                $printablePath = $this->generatePrintableCard($idCard);
                $printableFiles[] = [
                    'id_card_id' => $idCard->id,
                    'card_number' => $idCard->card_number,
                    'printable_path' => $printablePath,
                    'download_url' => asset('storage/' . $printablePath)
                ];
            } catch (\Exception $e) {
                $printableFiles[] = [
                    'id_card_id' => $idCard->id,
                    'card_number' => $idCard->card_number,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        ActivityLogger::log('Bulk ID Cards Print', 'IDCard', [
            'id_card_ids' => $idCardIds,
            'template' => $template,
            'generated_count' => count(array_filter($printableFiles, function($file) {
                return !isset($file['error']);
            }))
        ]);
        
        return [
            'total_requested' => count($idCardIds),
            'generated_successfully' => count(array_filter($printableFiles, function($file) {
                return !isset($file['error']);
            })),
            'files' => $printableFiles
        ];
    }

    /**
     * Clear ID card related caches
     */
    private function clearIDCardCaches(): void
    {
        // Clear statistics caches
        $cacheKeys = Cache::getRedis()->keys('*idcard_statistics_*');
        if (!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}
