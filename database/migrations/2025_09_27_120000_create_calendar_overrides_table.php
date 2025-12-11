<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("calendar_overrides", function (Blueprint $table) {
            $table->id();
            // Date-only range (inclusive)
            $table->date("start_date");
            $table->date("end_date");
            // Scope of effect
            $table
                ->enum("scope_type", ["all", "department", "subject", "professor"])
                ->default("all");
            $table->unsignedBigInteger("scope_id")->nullable();
            // Effect details
            $table->enum("effect", ["force_mode", "block_all", "holiday"]);
            $table->enum("allowed_mode", ["online", "onsite"])->nullable();
            $table->string("reason_key")->nullable();
            $table->text("reason_text")->nullable();
            // Audit
            $table->unsignedBigInteger("created_by")->nullable();
            $table->timestamps();

            $table->index(["start_date", "end_date"]);
            $table->index(["scope_type", "scope_id"]);
            $table->index(["effect"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("calendar_overrides");
    }
};
