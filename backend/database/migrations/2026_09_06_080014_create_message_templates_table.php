<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');       // welcome, before_expiry, on_expiry, after_expiry, dues, birthday, winback
            $table->string('name');
            $table->string('channel')->default('sms'); // sms, whatsapp, email
            $table->string('language', 5)->default('en'); // en, si, ta
            $table->string('subject')->nullable(); // email only
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'channel', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
