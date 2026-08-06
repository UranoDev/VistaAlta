<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fraccionamiento_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('start_date');
            $table->string('surcharge_type')->nullable(); // 'percentage' | 'fixed'
            $table->decimal('surcharge_value', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['fraccionamiento_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fees');
    }
};
