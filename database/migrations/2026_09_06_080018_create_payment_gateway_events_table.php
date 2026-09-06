<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');            // payhere, onepay
            $table->string('event_id');           // gateway's unique reference / order id + status hash
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->string('signature_status')->default('unverified'); // verified, invalid
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_events');
    }
};
