<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "branch" is a physical location. A single-location gym has exactly one.
 * Kept as a table so a second location can be added later without a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->string('sms_sender_id')->nullable();
            $table->string('invoice_prefix')->nullable();
            $table->string('receipt_prefix')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
