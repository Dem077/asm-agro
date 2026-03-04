<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_forms', function (Blueprint $table) {
            $table->unsignedInteger('issued_user_id')->nullable();

            $table->foreign('issued_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_forms', function (Blueprint $table) {
            $table->dropForeign(['issued_user_id']);
            $table->dropColumn('issued_user_id');
        });
    }
};
