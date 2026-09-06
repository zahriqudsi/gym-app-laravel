<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reminder_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('sms');
            $table->string('template_key')->nullable();
            $table->string('dedupe_key')->unique(); // e.g. "before_expiry:-7:member:123:2026-09-06"
            $table->string('status')->default('queued'); // queued, sent, delivered, failed
            $table->string('provider_ref')->nullable();
            $table->unsignedBigInteger('cost_cents')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
