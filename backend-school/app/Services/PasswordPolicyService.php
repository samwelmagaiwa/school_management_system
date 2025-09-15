<?php

namespace App\Services;

use Illuminate\Validation\Rules\Password;

class PasswordPolicyService
{
    /**
     * Password policy configuration
     */
    private const POLICY = [
        'min_length' => 8,
        'max_length' => 128,
        'require_lowercase' => true,
        'require_uppercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'min_unique_chars' => 4,
        'max_repeated_chars' => 2,
        'prevent_common_passwords' => true,
        'prevent_user_info_in_password' => true,
        'password_history_limit' => 5, // Remember last 5 passwords
        'password_expiry_days' => 90,
        'failed_attempts_lockout' => 5,
        'lockout_duration_minutes' => 30,
    ];

    /**
     * Common weak passwords to prevent
     */
    private const COMMON_PASSWORDS = [
        'password', 'password123', '123456', '123456789', 'qwerty', 'abc123',
        'password1', 'admin', 'administrator', 'root', 'user', 'guest',
        '12345678', 'welcome', 'login', 'pass', 'secret', 'letmein',
        'monkey', 'dragon', 'princess', 'football', 'baseball', 'basketball'
    ];

    /**
     * Get password validation rules
     */
    public function getValidationRules(array $userData = []): Password
    {
        $rules = Password::min(self::POLICY['min_length'])
            ->max(self::POLICY['max_length']);

        if (self::POLICY['require_lowercase']) {
            $rules->mixedCase();
        }

        if (self::POLICY['require_numbers']) {
            $rules->numbers();
        }

        if (self::POLICY['require_symbols']) {
            $rules->symbols();
        }

        // Add custom validation
        $rules->rules([
            function ($attribute, $value, $fail) use ($userData) {
                $this->validateCustomRules($value, $userData, $fail);
            }
        ]);

        return $rules;
    }

    /**
     * Validate custom password rules
     */
    private function validateCustomRules(string $password, array $userData, \Closure $fail): void
    {
        // Check unique characters
        $uniqueChars = count(array_unique(str_split(strtolower($password))));
        if ($uniqueChars < self::POLICY['min_unique_chars']) {
            $fail("Password must contain at least {$uniqueChars} unique characters.");
        }

        // Check repeated characters
        if ($this->hasExcessiveRepeatedChars($password)) {
            $fail('Password cannot contain more than ' . self::POLICY['max_repeated_chars'] . ' consecutive identical characters.');
        }

        // Check common passwords
        if (self::POLICY['prevent_common_passwords'] && $this->isCommonPassword($password)) {
            $fail('This password is too common. Please choose a stronger password.');
        }

        // Check user information in password
        if (self::POLICY['prevent_user_info_in_password'] && $this->containsUserInfo($password, $userData)) {
            $fail('Password should not contain personal information.');
        }
    }

    /**
     * Check if password has excessive repeated characters
     */
    private function hasExcessiveRepeatedChars(string $password): bool
    {
        $maxRepeated = self::POLICY['max_repeated_chars'];
        
        for ($i = 0; $i <= strlen($password) - $maxRepeated - 1; $i++) {
            $char = $password[$i];
            $count = 1;
            
            for ($j = $i + 1; $j < strlen($password); $j++) {
                if ($password[$j] === $char) {
                    $count++;
                    if ($count > $maxRepeated) {
                        return true;
                    }
                } else {
                    break;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if password is a common weak password
     */
    private function isCommonPassword(string $password): bool
    {
        $lowercasePassword = strtolower($password);
        
        foreach (self::COMMON_PASSWORDS as $commonPassword) {
            if ($lowercasePassword === $commonPassword || 
                str_contains($lowercasePassword, $commonPassword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if password contains user information
     */
    private function containsUserInfo(string $password, array $userData): bool
    {
        $lowercasePassword = strtolower($password);
        
        $checkFields = ['first_name', 'last_name', 'email', 'username'];
        
        foreach ($checkFields as $field) {
            if (isset($userData[$field])) {
                $value = strtolower($userData[$field]);
                
                // Remove common separators from email
                if ($field === 'email') {
                    $value = explode('@', $value)[0];
                }
                
                // Check if user info is in password (minimum 3 chars to avoid false positives)
                if (strlen($value) >= 3 && str_contains($lowercasePassword, $value)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Generate password strength score (0-100)
     */
    public function getPasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];
        
        // Length score (0-30 points)
        $lengthScore = min(30, (strlen($password) / 12) * 30);
        $score += $lengthScore;
        
        if (strlen($password) < 8) {
            $feedback[] = 'Password should be at least 8 characters long';
        } elseif (strlen($password) >= 12) {
            $feedback[] = 'Good length';
        }
        
        // Character variety score (0-40 points)
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/\d/', $password);
        $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $password);
        
        $varietyScore = ($hasLower + $hasUpper + $hasNumber + $hasSymbol) * 10;
        $score += $varietyScore;
        
        if (!$hasLower) $feedback[] = 'Add lowercase letters';
        if (!$hasUpper) $feedback[] = 'Add uppercase letters';
        if (!$hasNumber) $feedback[] = 'Add numbers';
        if (!$hasSymbol) $feedback[] = 'Add symbols';
        
        // Uniqueness score (0-20 points)
        $uniqueChars = count(array_unique(str_split(strtolower($password))));
        $uniquenessScore = min(20, ($uniqueChars / 8) * 20);
        $score += $uniquenessScore;
        
        if ($uniqueChars < 4) {
            $feedback[] = 'Use more unique characters';
        }
        
        // Penalize common patterns (0-10 points deduction)
        if ($this->isCommonPassword($password)) {
            $score -= 10;
            $feedback[] = 'Avoid common passwords';
        }
        
        if ($this->hasExcessiveRepeatedChars($password)) {
            $score -= 5;
            $feedback[] = 'Avoid repeated characters';
        }
        
        // Cap score at 100
        $score = max(0, min(100, $score));
        
        // Determine strength level
        if ($score >= 80) {
            $level = 'Strong';
            $color = 'green';
        } elseif ($score >= 60) {
            $level = 'Good';
            $color = 'yellow';
        } elseif ($score >= 40) {
            $level = 'Fair';
            $color = 'orange';
        } else {
            $level = 'Weak';
            $color = 'red';
        }
        
        return [
            'score' => $score,
            'level' => $level,
            'color' => $color,
            'feedback' => $feedback
        ];
    }

    /**
     * Generate a secure random password
     */
    public function generateSecurePassword(int $length = 12): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one character from each required category
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill remaining length with random characters from all categories
        $allChars = $lowercase . $uppercase . $numbers . $symbols;
        
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password to avoid predictable patterns
        return str_shuffle($password);
    }

    /**
     * Check if password has expired
     */
    public function isPasswordExpired(?string $lastChanged): bool
    {
        if (!$lastChanged) {
            return true; // Force password change if never set
        }
        
        $expiryDate = \Carbon\Carbon::parse($lastChanged)
            ->addDays(self::POLICY['password_expiry_days']);
        
        return now()->greaterThan($expiryDate);
    }

    /**
     * Get password policy information for display
     */
    public function getPolicyInfo(): array
    {
        return [
            'requirements' => [
                'Minimum ' . self::POLICY['min_length'] . ' characters',
                'Maximum ' . self::POLICY['max_length'] . ' characters',
                self::POLICY['require_lowercase'] ? 'At least one lowercase letter' : null,
                self::POLICY['require_uppercase'] ? 'At least one uppercase letter' : null,
                self::POLICY['require_numbers'] ? 'At least one number' : null,
                self::POLICY['require_symbols'] ? 'At least one symbol (!@#$%^&*)' : null,
                'At least ' . self::POLICY['min_unique_chars'] . ' unique characters',
                'No more than ' . self::POLICY['max_repeated_chars'] . ' consecutive identical characters',
                'Cannot be a common password',
                'Cannot contain personal information',
            ],
            'expiry_policy' => [
                'passwords_expire_after_days' => self::POLICY['password_expiry_days'],
                'remember_last_passwords' => self::POLICY['password_history_limit'],
            ],
            'lockout_policy' => [
                'max_failed_attempts' => self::POLICY['failed_attempts_lockout'],
                'lockout_duration_minutes' => self::POLICY['lockout_duration_minutes'],
            ]
        ];
    }

    /**
     * Get policy constants for external use
     */
    public static function getPolicy(): array
    {
        return self::POLICY;
    }
}
