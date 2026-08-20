<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'merchant@example.test'],
            [
                'name' => 'Demo Merchant',
                'password' => Hash::make('change-this-local-password'),
                'store_slug' => 'demo-merchant',
            ],
        );
    }
}
