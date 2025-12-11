<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable("t_student")) {
            return;
        }

        $columns = Schema::getColumnListing("t_student");
        $has = fn(string $col): bool => in_array($col, $columns, true);
        $students = [
            [
                "Stud_ID" => "910000001",
                "Name" => "Demo Student One",
                "Dept_ID" => "DEMO",
                "Email" => "demo1@example.com",
            ],
            [
                "Stud_ID" => "910000002",
                "Name" => "Demo Student Two",
                "Dept_ID" => "DEMO",
                "Email" => "demo2@example.com",
            ],
            [
                "Stud_ID" => "910000003",
                "Name" => "Demo Student Three",
                "Dept_ID" => "DEMO",
                "Email" => "demo3@example.com",
            ],
            [
                "Stud_ID" => "910000004",
                "Name" => "Demo Student Four",
                "Dept_ID" => "DEMO",
                "Email" => "demo4@example.com",
            ],
            [
                "Stud_ID" => "910000005",
                "Name" => "Demo Student Five",
                "Dept_ID" => "DEMO",
                "Email" => "demo5@example.com",
            ],
            [
                "Stud_ID" => "910000006",
                "Name" => "Demo Student Six",
                "Dept_ID" => "DEMO",
                "Email" => "demo6@example.com",
            ],
            [
                "Stud_ID" => "910000007",
                "Name" => "Demo Student Seven",
                "Dept_ID" => "DEMO",
                "Email" => "demo7@example.com",
            ],
        ];

        foreach ($students as $student) {
            $payload = ["Stud_ID" => $student["Stud_ID"]];
            if ($has("Name")) {
                $payload["Name"] = $student["Name"];
            }
            if ($has("Dept_ID")) {
                $payload["Dept_ID"] = 1;
            }
            if ($has("Course")) {
                $payload["Course"] = "Demo Program";
            }
            if ($has("YearLevel")) {
                $payload["YearLevel"] = "3";
            }
            if ($has("Email")) {
                $payload["Email"] = $student["Email"];
            }
            if ($has("Password")) {
                $payload["Password"] = Hash::make("demo1234");
            }
            if ($has("is_active")) {
                $payload["is_active"] = 1;
            }
            if ($has("remember_token")) {
                $payload["remember_token"] = Str::random(40);
            }
            if ($has("Created_At")) {
                $payload["Created_At"] = now();
            }
            if ($has("Updated_At")) {
                $payload["Updated_At"] = now();
            }
            DB::table("t_student")->updateOrInsert(["Stud_ID" => $student["Stud_ID"]], $payload);
        }
    }
}
