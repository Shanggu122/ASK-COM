<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Insert "General Consultation" subject if it doesn't exist yet
        $exists = DB::table("t_subject")
            ->whereRaw("LOWER(TRIM(Subject_Name)) = ?", ["general consultation"])
            ->exists();
        if (!$exists) {
            DB::table("t_subject")->insert([
                "Subject_Name" => "General Consultation",
            ]);
        }
    }

    public function down(): void
    {
        // Remove the subject by name (id can vary across environments)
        DB::table("t_subject")
            ->whereRaw("LOWER(TRIM(Subject_Name)) = ?", ["general consultation"])
            ->delete();
    }
};
