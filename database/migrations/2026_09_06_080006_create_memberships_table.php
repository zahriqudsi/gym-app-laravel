<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable(); // set after invoice created
            $table->date('starts_on');
            $table->date('ends_on')->nullable(); // null for pure session packs
            $table->unsignedInteger('sessions_total')->nullable();
            $table->unsignedInteger('sessions_used')->default(0);
            $table->unsignedInteger('frozen_days')->default(0);
            $table->unsignedBigInteger('price_cents')->default(0); // snapshot at sale
            $table->string('status')->default('active'); // active, frozen, expired, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('ends_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
