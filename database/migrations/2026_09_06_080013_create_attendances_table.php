<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->string('method')->default('manual'); // qr, manual, nic, biometric, rfid
            $table->string('status_at_checkin')->nullable(); // active, due, expired, frozen
            $table->boolean('access_granted')->default(true);
            $table->string('device_id')->nullable();
            $table->uuid('client_uuid')->nullable()->unique(); // for offline dedupe
            $table->timestamps();

            $table->index(['branch_id', 'checked_in_at']);
            $table->index(['member_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
