<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to');
            $table->text('body');
            $table->unsignedInteger('segments')->default(1);
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->string('provider')->nullable();
            $table->string('provider_ref')->nullable();
            $table->string('purpose')->default('reminder'); // reminder, otp, campaign, manual
            $table->string('status')->default('queued'); // queued, sent, delivered, failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
