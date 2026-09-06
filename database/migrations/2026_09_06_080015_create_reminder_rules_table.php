<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event'); // before_expiry, on_expiry, after_expiry, payment_due, welcome, birthday, winback
            $table->integer('offset_days')->default(0); // -7 = 7 days before; 3 = 3 days after
            $table->string('channel')->default('sms'); // sms, whatsapp, email
            $table->string('template_key')->nullable();
            $table->string('language', 5)->default('en');
            $table->boolean('respect_quiet_hours')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_rules');
    }
};
