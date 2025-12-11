<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Auto run recovery seeder if core tables are empty (local/dev safety)
        if (app()->environment(['local','development']) ) {
            $needsRecovery = false;
            if (Schema::hasTable('t_student') && DB::table('t_student')->count() === 0) $needsRecovery = true;
            if (Schema::hasTable('professors') && DB::table('professors')->count() === 0) $needsRecovery = true;
            elseif (Schema::hasTable('t_professor') && DB::table('t_professor')->count() === 0) $needsRecovery = true;
            if ($needsRecovery) {
                $this->call(RecoverySeeder::class);
            }
        }
    }
}
