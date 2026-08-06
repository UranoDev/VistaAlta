<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fraccionamientos', function (Blueprint $table) {
            $table->foreignId('admin_owner_id')->nullable()->after('contact')
                ->constrained('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fraccionamientos', function (Blueprint $table) {
            $table->dropForeign(['admin_owner_id']);
            $table->dropColumn('admin_owner_id');
        });
    }
};
