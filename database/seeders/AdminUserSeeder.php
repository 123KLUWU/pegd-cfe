<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * https://laravel.com/docs/12.x/seeding
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'admin',
            'rpe' => 'admin',
            'status' => 'Activo',
            'password' => Hash::make('dejamepasarporfavor'),
        ]);
    }
}
