<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('amount_paid_cents')->default(0);
            $table->string('status')->default('unpaid'); // draft, unpaid, part_paid, paid, void
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
