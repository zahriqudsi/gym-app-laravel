<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('gym'); // gym, gym_classes, pt, couple, student, corporate
            $table->string('billing_type')->default('duration'); // duration | sessions
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('session_count')->nullable();
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->unsignedBigInteger('signup_fee_cents')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0); // percent
            $table->unsignedInteger('grace_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
