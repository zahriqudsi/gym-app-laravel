<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->string('number')->unique();
            $table->timestamp('paid_at');
            $table->string('method'); // cash, card, bank_transfer, lankaqr, wallet, cheque, online
            $table->unsignedBigInteger('amount_cents');
            $table->string('reference')->nullable();
            $table->string('gateway')->nullable();       // payhere, onepay...
            $table->string('gateway_ref')->nullable();
            $table->string('status')->default('completed'); // pending, completed, failed, refunded
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
