<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RecoverySeeder extends Seeder
{
    /**
     * Recreate baseline professors and students if tables are empty.
     */
    public function run(): void
    {
        // Students (legacy table name t_student)
        if (Schema::hasTable('t_student') && DB::table('t_student')->count() === 0) {
            DB::table('t_student')->insert([
                [
                    'Stud_ID' => '202500001',
                    'Name' => 'Sample Student One',
                    'Course' => 'BSCS',
                    'YearLevel' => '3',
                    'Email' => 'stud1@example.com',
                    'Password' => Hash::make('password1'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'Stud_ID' => '202500002',
                    'Name' => 'Sample Student Two',
                    'Course' => 'BSIT',
                    'YearLevel' => '2',
                    'Email' => 'stud2@example.com',
                    'Password' => Hash::make('password2'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // Professors (new table name professors OR legacy t_professor)
        $profTable = Schema::hasTable('professors') ? 'professors' : (Schema::hasTable('t_professor') ? 't_professor' : null);
        if ($profTable && DB::table($profTable)->count() === 0) {
            DB::table($profTable)->insert([
                [
                    'Prof_ID' => 'P000001',
                    'Name' => 'Professor Alpha',
                    'Dept_ID' => 'CS',
                    'Email' => 'alpha.prof@example.com',
                    'Password' => Hash::make('alpha123'),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'Prof_ID' => 'P000002',
                    'Name' => 'Professor Beta',
                    'Dept_ID' => 'IT',
                    'Email' => 'beta.prof@example.com',
                    'Password' => Hash::make('beta123'),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
