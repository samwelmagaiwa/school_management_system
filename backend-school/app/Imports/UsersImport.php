<?php

namespace App\Imports;

use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected int $schoolId;
    protected int $importedCount = 0;
    protected int $failedCount = 0;
    protected array $errors = [];

    public function __construct(int $schoolId)
    {
        $this->schoolId = $schoolId;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            try {
                $this->processRow($row->toArray());
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = "Row error: " . $e->getMessage();
            }
        }
    }

    /**
     * Process a single row
     */
    protected function processRow(array $row): void
    {
        // Clean and validate the row data
        $userData = [
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'gender' => $row['gender'] ?? null,
            'role' => $row['role'] ?? 'Student',
            'school_id' => $this->schoolId,
            'status' => true,
        ];

        // Validate the data
        $validator = Validator::make($userData, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'role' => 'required|in:' . implode(',', User::ROLES),
            'school_id' => 'required|exists:schools,id',
        ]);

        if ($validator->fails()) {
            $this->failedCount++;
            $this->errors[] = "Validation failed for {$userData['email']}: " . implode(', ', $validator->errors()->all());
            return;
        }

        // Set default password to last_name if not provided
        $userData['password'] = Hash::make($row['password'] ?? $userData['last_name']);

        // Create the user
        User::create($userData);
        $this->importedCount++;
    }

    /**
     * Get the number of imported users
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Get the number of failed imports
     */
    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}