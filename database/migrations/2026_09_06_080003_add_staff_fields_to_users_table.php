<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('staff_role')->nullable()->after('phone'); // owner, manager, receptionist, trainer, accountant
            $table->boolean('is_active')->default(true)->after('staff_role');
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_branch_id');
            $table->dropColumn(['phone', 'staff_role', 'is_active', 'last_login_at']);
        });
    }
};
