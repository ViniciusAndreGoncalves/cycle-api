<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'user',
            'email' => 'test@example.com',
            'password' => bcrypt('12345678'),
        ]);
    }
}
