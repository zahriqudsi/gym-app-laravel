<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('opening_float_cents')->default(0);
            $table->bigInteger('expected_cash_cents')->nullable();
            $table->bigInteger('counted_cash_cents')->nullable();
            $table->bigInteger('variance_cents')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
