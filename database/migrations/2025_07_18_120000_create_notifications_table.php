<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // Student ID
            $table->integer('booking_id'); // Consultation booking ID
            $table->string('type'); // 'accepted', 'completed', 'rescheduled', 'cancelled'
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['user_id', 'is_read']);
            $table->index('booking_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
