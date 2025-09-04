<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Upload file and return path
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads'): string
    {
        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');
        
        return $path;
    }

    /**
     * Upload profile picture
     */
    public function uploadProfilePicture(UploadedFile $file, int $userId): string
    {
        // Delete old profile picture if exists
        $this->deleteOldProfilePicture($userId);
        
        $filename = "profile_{$userId}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile_pictures', $filename, 'public');
        
        return Storage::url($path);
    }

    /**
     * Delete old profile picture
     */
    private function deleteOldProfilePicture(int $userId): void
    {
        $files = Storage::disk('public')->files('profile_pictures');
        
        foreach ($files as $file) {
            if (str_contains($file, "profile_{$userId}_")) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    /**
     * Process CSV file for import
     */
    public function processCsvFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $data = [];
        
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            
            fclose($handle);
        }
        
        return $data;
    }

    /**
     * Export data to CSV
     */
    public function exportToCsv(array $data, array $headers, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = $filename . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Export data to Excel (simplified implementation)
     */
    public function exportToExcel(array $data, array $headers, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // For now, returning CSV format as Excel would require additional packages
        return $this->exportToCsv($data, $headers, $filename);
    }

    /**
     * Validate file type and size
     */
    public function validateFile(UploadedFile $file, array $allowedMimes = [], int $maxSize = 10240): array
    {
        $errors = [];
        
        if (!empty($allowedMimes) && !in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedMimes);
        }
        
        if ($file->getSize() > $maxSize * 1024) {
            $errors[] = 'File size too large. Maximum size: ' . $maxSize . 'KB';
        }
        
        return $errors;
    }

    /**
     * Generate PDF from HTML
     */
    public function generatePdf(string $html, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // This would require a PDF library like DOMPDF or similar
        // For now, returning a simple implementation
        
        $filename = $filename . '_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return response()->streamDownload(function() use ($html) {
            echo $html; // This would be processed by PDF generator
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        
        return Storage::url($path);
    }

    /**
     * Delete file
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        
        return false;
    }
}
