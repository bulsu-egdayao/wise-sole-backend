<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'qc_timeline')) {
                    $table->string('qc_timeline')->nullable()->after('description');
                }
                if (!Schema::hasColumn('products', 'arrival_timeline')) {
                    $table->string('arrival_timeline')->nullable()->after('qc_timeline');
                }
                if (!Schema::hasColumn('products', 'packaging_inclusions')) {
                    $table->text('packaging_inclusions')->nullable()->after('arrival_timeline');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $columns = ['qc_timeline', 'arrival_timeline', 'packaging_inclusions'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('products', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
