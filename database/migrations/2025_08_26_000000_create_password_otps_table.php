<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("password_otps", function (Blueprint $table) {
            $table->id();
            $table->string("email");
            $table->enum("user_type", ["student", "professor"]);
            $table->string("otp", 10); // store raw short-lived OTP
            $table->dateTime("expires_at");
            $table->dateTime("used_at")->nullable();
            $table->timestamps();
            $table->index(["email", "user_type"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("password_otps");
    }
};
