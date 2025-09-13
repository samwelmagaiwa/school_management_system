<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_public',
        'validation_rules',
        'options',
        'sort_order'
    ];

    protected $casts = [
        'value' => 'json',
        'options' => 'array',
        'validation_rules' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Setting categories
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_BRANDING = 'branding';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_EMAIL = 'email';
    const CATEGORY_SMS = 'sms';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_ACADEMIC = 'academic';
    const CATEGORY_FEATURES = 'features';
    const CATEGORY_LIMITS = 'limits';
    const CATEGORY_INTEGRATIONS = 'integrations';

    // Setting types
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_ARRAY = 'array';
    const TYPE_JSON = 'json';
    const TYPE_FILE = 'file';
    const TYPE_COLOR = 'color';
    const TYPE_EMAIL = 'email';
    const TYPE_URL = 'url';
    const TYPE_SELECT = 'select';
    const TYPE_MULTISELECT = 'multiselect';

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function set($key, $value, $type = self::TYPE_STRING, $category = self::CATEGORY_GENERAL)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'category' => $category
            ]
        );
    }

    /**
     * Get all settings by category
     */
    public static function getByCategory($category)
    {
        return self::where('category', $category)
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get all public settings (for frontend)
     */
    public static function getPublicSettings()
    {
        return self::where('is_public', true)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get default system settings
     */
    public static function getDefaultSettings()
    {
        return [
            // General Settings
            'app_name' => [
                'value' => 'School Management System',
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Application name displayed throughout the system',
                'is_public' => true
            ],
            'app_description' => [
                'value' => 'Comprehensive school management solution',
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Application description for SEO and branding',
                'is_public' => true
            ],
            'default_timezone' => [
                'value' => 'UTC',
                'type' => self::TYPE_SELECT,
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Default timezone for new tenants',
                'options' => timezone_identifiers_list(),
                'is_public' => false
            ],
            'default_language' => [
                'value' => 'en',
                'type' => self::TYPE_SELECT,
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Default language for new tenants',
                'options' => ['en' => 'English', 'es' => 'Spanish', 'fr' => 'French'],
                'is_public' => true
            ],
            'default_currency' => [
                'value' => 'USD',
                'type' => self::TYPE_SELECT,
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Default currency for billing',
                'options' => ['USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound'],
                'is_public' => false
            ],

            // Branding Settings
            'primary_color' => [
                'value' => '#0000ff',
                'type' => self::TYPE_COLOR,
                'category' => self::CATEGORY_BRANDING,
                'description' => 'Primary brand color (blue theme)',
                'is_public' => true
            ],
            'secondary_color' => [
                'value' => '#3b82f6',
                'type' => self::TYPE_COLOR,
                'category' => self::CATEGORY_BRANDING,
                'description' => 'Secondary brand color',
                'is_public' => true
            ],
            'logo_url' => [
                'value' => null,
                'type' => self::TYPE_FILE,
                'category' => self::CATEGORY_BRANDING,
                'description' => 'System logo URL',
                'is_public' => true
            ],
            'favicon_url' => [
                'value' => null,
                'type' => self::TYPE_FILE,
                'category' => self::CATEGORY_BRANDING,
                'description' => 'System favicon URL',
                'is_public' => true
            ],

            // Security Settings
            'require_2fa' => [
                'value' => false,
                'type' => self::TYPE_BOOLEAN,
                'category' => self::CATEGORY_SECURITY,
                'description' => 'Require 2FA for all super admin accounts',
                'is_public' => false
            ],
            'password_min_length' => [
                'value' => 8,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_SECURITY,
                'description' => 'Minimum password length requirement',
                'is_public' => false
            ],
            'session_timeout' => [
                'value' => 120,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_SECURITY,
                'description' => 'Session timeout in minutes',
                'is_public' => false
            ],
            'max_login_attempts' => [
                'value' => 5,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_SECURITY,
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false
            ],

            // Feature Settings
            'default_features' => [
                'value' => [
                    'students', 'teachers', 'classes', 'subjects', 'attendance',
                    'exams', 'fees', 'library', 'transport', 'hr'
                ],
                'type' => self::TYPE_ARRAY,
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Default features enabled for new tenants',
                'is_public' => false
            ],
            'premium_features' => [
                'value' => [
                    'advanced_reports', 'api_access', 'custom_branding',
                    'bulk_operations', 'integrations', 'mobile_app'
                ],
                'type' => self::TYPE_ARRAY,
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Premium features available for paid plans',
                'is_public' => false
            ],

            // Limits
            'default_storage_limit_gb' => [
                'value' => 5,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Default storage limit in GB for new tenants',
                'is_public' => false
            ],
            'default_user_limit' => [
                'value' => 100,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Default user limit for new tenants',
                'is_public' => false
            ],
            'trial_period_days' => [
                'value' => 14,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Default trial period in days',
                'is_public' => false
            ],

            // Academic Settings
            'default_academic_year_start' => [
                'value' => '09-01',
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_ACADEMIC,
                'description' => 'Default academic year start date (MM-DD)',
                'is_public' => false
            ],
            'default_academic_year_end' => [
                'value' => '06-30',
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_ACADEMIC,
                'description' => 'Default academic year end date (MM-DD)',
                'is_public' => false
            ],

            // Email Settings
            'smtp_host' => [
                'value' => null,
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_EMAIL,
                'description' => 'SMTP server host',
                'is_public' => false
            ],
            'smtp_port' => [
                'value' => 587,
                'type' => self::TYPE_INTEGER,
                'category' => self::CATEGORY_EMAIL,
                'description' => 'SMTP server port',
                'is_public' => false
            ],
            'smtp_username' => [
                'value' => null,
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_EMAIL,
                'description' => 'SMTP username',
                'is_public' => false
            ],
            'from_email' => [
                'value' => 'noreply@schoolsystem.com',
                'type' => self::TYPE_EMAIL,
                'category' => self::CATEGORY_EMAIL,
                'description' => 'Default from email address',
                'is_public' => false
            ],

            // SMS Settings
            'sms_provider' => [
                'value' => null,
                'type' => self::TYPE_SELECT,
                'category' => self::CATEGORY_SMS,
                'description' => 'SMS service provider',
                'options' => ['twilio' => 'Twilio', 'nexmo' => 'Nexmo', 'aws_sns' => 'AWS SNS'],
                'is_public' => false
            ],
            'sms_api_key' => [
                'value' => null,
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_SMS,
                'description' => 'SMS API key',
                'is_public' => false
            ],

            // Payment Settings
            'payment_gateway' => [
                'value' => 'stripe',
                'type' => self::TYPE_SELECT,
                'category' => self::CATEGORY_PAYMENT,
                'description' => 'Default payment gateway',
                'options' => ['stripe' => 'Stripe', 'paypal' => 'PayPal', 'razorpay' => 'Razorpay'],
                'is_public' => false
            ],
            'stripe_public_key' => [
                'value' => null,
                'type' => self::TYPE_STRING,
                'category' => self::CATEGORY_PAYMENT,
                'description' => 'Stripe public key',
                'is_public' => false
            ]
        ];
    }

    /**
     * Initialize default settings
     */
    public static function initializeDefaults()
    {
        $defaults = self::getDefaultSettings();
        
        foreach ($defaults as $key => $config) {
            self::firstOrCreate(
                ['key' => $key],
                $config
            );
        }
    }

    /**
     * Get formatted value based on type
     */
    public function getFormattedValue()
    {
        switch ($this->type) {
            case self::TYPE_BOOLEAN:
                return (bool) $this->value;
            case self::TYPE_INTEGER:
                return (int) $this->value;
            case self::TYPE_ARRAY:
            case self::TYPE_JSON:
                return is_array($this->value) ? $this->value : json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Scope for category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}