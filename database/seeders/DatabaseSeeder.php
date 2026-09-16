<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Dashboard login. Change the password from Dashboard → Account right after the first login.
        User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'mohammad-hamdy@hotmail.com')],
            ['name' => 'Mohammed Hamdy', 'password' => env('ADMIN_PASSWORD', 'password')],
        );

        $this->call(PortfolioSeeder::class);
    }
}
