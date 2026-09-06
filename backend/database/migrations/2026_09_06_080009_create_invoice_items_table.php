<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('qty', 8, 2)->default(1);
            $table->unsignedBigInteger('unit_price_cents')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('line_total_cents')->default(0); // (qty*unit - discount) + line tax
            $table->nullableMorphs('itemable'); // membership, product, fee...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
