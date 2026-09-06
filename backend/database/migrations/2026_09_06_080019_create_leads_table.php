<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('source')->nullable();   // walk_in, call, web, social, referral
            $table->string('interest')->nullable();
            $table->string('stage')->default('new'); // new, contacted, trial_booked, negotiation, won, lost
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('next_follow_up_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('converted_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('lost_at')->nullable();
            $table->timestamps();

            $table->index('stage');
            $table->index('next_follow_up_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
