<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\User\Models\User;

class CreateSuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating SuperAdmin user...');
        
        $user = User::updateOrCreate(
            ['email' => 'superadmin@sms.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@sms.com',
                'password' => Hash::make('password123'),
                'role' => 'SuperAdmin',
                'status' => true,
                'email_verified_at' => now()
            ]
        );
        
        $this->command->info('SuperAdmin user created:');
        $this->command->info("Email: {$user->email}");
        $this->command->info("Password: password123");
        $this->command->info("Role: {$user->role}");
    }
}
