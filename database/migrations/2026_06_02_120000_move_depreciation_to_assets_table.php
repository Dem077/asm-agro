<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'depreciation_method')) {
                $table->string('depreciation_method', 32)->nullable()->after('purchase_cost');
            }

            if (! Schema::hasColumn('assets', 'depreciation_months')) {
                $table->unsignedInteger('depreciation_months')->nullable()->after('depreciation_method');
            }

            if (! Schema::hasColumn('assets', 'depreciation_percentage')) {
                $table->decimal('depreciation_percentage', 5, 2)->nullable()->after('depreciation_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'depreciation_percentage')) {
                $table->dropColumn('depreciation_percentage');
            }

            if (Schema::hasColumn('assets', 'depreciation_months')) {
                $table->dropColumn('depreciation_months');
            }

            if (Schema::hasColumn('assets', 'depreciation_method')) {
                $table->dropColumn('depreciation_method');
            }
        });
    }
};
