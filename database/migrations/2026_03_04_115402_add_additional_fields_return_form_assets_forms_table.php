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
            $table->string('return_form_number')->nullable();
            $table->unsignedInteger('return_issued_user_id')->nullable();

            $table->foreign('return_issued_user_id')
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
            $table->dropColumn('return_form_number');
            $table->dropForeign(['return_issued_user_id']);
            $table->dropColumn('return_issued_user_id');
        });
    }
};
