<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // member-app login
            $table->string('member_no')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('nic')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('emergency_name')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->text('health_notes')->nullable();
            $table->text('goals')->nullable();
            $table->string('referral_source')->nullable();
            $table->foreignId('assigned_trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('joined_on')->nullable();
            $table->string('status')->default('enquiry'); // enquiry, trial, active, due, frozen, expired, cancelled
            $table->date('current_expiry_on')->nullable(); // denormalised from active membership for fast filtering
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('current_expiry_on');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
