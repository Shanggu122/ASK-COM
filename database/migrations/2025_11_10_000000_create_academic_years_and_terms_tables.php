<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("academic_years", function (Blueprint $table) {
            $table->id();
            $table->string("label")->unique();
            $table->date("start_at");
            $table->date("end_at");
            $table->enum("status", ["draft", "active", "closed"])->default("draft");
            $table->timestamp("activated_at")->nullable();
            $table->timestamp("closed_at")->nullable();
            $table->unsignedBigInteger("activated_by")->nullable();
            $table->unsignedBigInteger("closed_by")->nullable();
            $table->index(["activated_by"]);
            $table->index(["closed_by"]);
            $table->timestamps();
        });

        Schema::create("terms", function (Blueprint $table) {
            $table->id();
            $table->foreignId("academic_year_id")->constrained("academic_years")->cascadeOnDelete();
            $table->unsignedTinyInteger("sequence");
            $table->string("name", 128);
            $table->date("start_at");
            $table->date("end_at");
            $table->date("enrollment_deadline")->nullable();
            $table->date("grade_submission_deadline")->nullable();
            $table->enum("status", ["draft", "active", "closed"])->default("draft");
            $table->timestamp("activated_at")->nullable();
            $table->timestamp("closed_at")->nullable();
            $table->timestamps();
            $table->unique(["academic_year_id", "sequence"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("terms");
        Schema::dropIfExists("academic_years");
    }
};
