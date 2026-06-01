<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_forms', 'assets')) {
                $table->json('assets')->nullable()->after('form_number');
            }

            if (! Schema::hasColumn('asset_forms', 'returned_assets')) {
                $table->json('returned_assets')->nullable()->after('assets');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_forms', function (Blueprint $table) {
            if (Schema::hasColumn('asset_forms', 'returned_assets')) {
                $table->dropColumn('returned_assets');
            }

            if (Schema::hasColumn('asset_forms', 'assets')) {
                $table->dropColumn('assets');
            }
        });
    }
};
