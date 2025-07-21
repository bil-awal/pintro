<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use App\Models\Transaction;
use App\Models\SystemSetting;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin users only if they don't exist
        if (!Admin::where('email', 'admin@pintro.dev')->exists()) {
            Admin::create([
                'name' => 'Super Admin',
                'email' => 'admin@pintro.dev',
                'password' => Hash::make('password'),
                'roles' => ['super_admin'],
                'permissions' => ['*'],
                'is_active' => true,
            ]);
        }

        if (!Admin::where('email', 'manager@pintro.dev')->exists()) {
            Admin::create([
                'name' => 'Transaction Manager',
                'email' => 'manager@pintro.dev',
                'password' => Hash::make('password'),
                'roles' => ['transaction_manager'],
                'permissions' => [
                    'view_transactions',
                    'approve_transactions',
                    'manage_users',
                    'view_reports',
                ],
                'is_active' => true,
            ]);
        }

        // Create sample users
        $users = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+6281234567890',
                'password' => Hash::make('password'),
                'balance' => 1000000.00,
                'status' => 'active',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '+6281234567891',
                'password' => Hash::make('password'),
                'balance' => 500000.00,
                'status' => 'active',
            ],
            [
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'email' => 'bob.johnson@example.com',
                'phone' => '+6281234567892',
                'password' => Hash::make('password'),
                'balance' => 750000.00,
                'status' => 'active',
            ],
            [
                'first_name' => 'Alice',
                'last_name' => 'Williams',
                'email' => 'alice.williams@example.com',
                'phone' => '+6281234567893',
                'password' => Hash::make('password'),
                'balance' => 250000.00,
                'status' => 'inactive',
            ],
            [
                'first_name' => 'Charlie',
                'last_name' => 'Brown',
                'email' => 'charlie.brown@example.com',
                'phone' => '+6281234567894',
                'password' => Hash::make('password'),
                'balance' => 0.00,
                'status' => 'suspended',
            ],
        ];

        foreach ($users as $userData) {
            if (!User::where('email', $userData['email'])->exists()) {
                User::create($userData);
            }
        }

        // Create sample transactions only if no transactions exist
        $createdUsers = User::all();
        if ($createdUsers->count() >= 2 && Transaction::count() == 0) {
            $transactions = [
                [
                    'user_id' => $createdUsers[0]->id,
                    'type' => 'topup',
                    'amount' => 100000.00,
                    'fee' => 2500.00,
                    'currency' => 'IDR',
                    'description' => 'Initial balance top-up',
                    'status' => 'completed',
                    'payment_method' => 'bca_va',
                    'processed_at' => now()->subDays(7),
                ],
                [
                    'user_id' => $createdUsers[1]->id,
                    'type' => 'payment',
                    'amount' => 50000.00,
                    'fee' => 1500.00,
                    'currency' => 'IDR',
                    'description' => 'E-commerce purchase',
                    'status' => 'completed',
                    'payment_method' => 'gopay',
                    'processed_at' => now()->subDays(5),
                ],
                [
                    'user_id' => $createdUsers[0]->id,
                    'from_account_id' => $createdUsers[0]->id,
                    'to_account_id' => $createdUsers[1]->id,
                    'type' => 'transfer',
                    'amount' => 25000.00,
                    'fee' => 500.00,
                    'currency' => 'IDR',
                    'description' => 'Money transfer to friend',
                    'status' => 'completed',
                    'processed_at' => now()->subDays(3),
                ],
                [
                    'user_id' => $createdUsers[2]->id,
                    'type' => 'topup',
                    'amount' => 200000.00,
                    'fee' => 5000.00,
                    'currency' => 'IDR',
                    'description' => 'Monthly top-up',
                    'status' => 'pending',
                    'payment_method' => 'bni_va',
                ],
                [
                    'user_id' => $createdUsers[1]->id,
                    'type' => 'payment',
                    'amount' => 75000.00,
                    'fee' => 2000.00,
                    'currency' => 'IDR',
                    'description' => 'Subscription payment',
                    'status' => 'failed',
                    'payment_method' => 'credit_card',
                ],
            ];

            foreach ($transactions as $transactionData) {
                Transaction::create($transactionData);
            }
        }

        // Create system settings
        $settings = [
            [
                'key' => 'transaction_fee_percentage',
                'value' => '2.5',
                'type' => 'string',
                'description' => 'Default transaction fee percentage',
            ],
            [
                'key' => 'max_transaction_amount',
                'value' => '10000000',
                'type' => 'integer',
                'description' => 'Maximum transaction amount in IDR',
            ],
            [
                'key' => 'min_transaction_amount',
                'value' => '10000',
                'type' => 'integer',
                'description' => 'Minimum transaction amount in IDR',
            ],
            [
                'key' => 'auto_approve_threshold',
                'value' => '100000',
                'type' => 'integer',
                'description' => 'Auto-approve transactions below this amount',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable/disable maintenance mode',
            ],
            [
                'key' => 'supported_payment_methods',
                'value' => '["credit_card","bca_va","bni_va","bri_va","gopay","shopeepay","indomaret","alfamart"]',
                'type' => 'json',
                'description' => 'List of supported payment methods',
            ],
        ];

        foreach ($settings as $settingData) {
            if (!SystemSetting::where('key', $settingData['key'])->exists()) {
                SystemSetting::create($settingData);
            }
        }
    }
}
