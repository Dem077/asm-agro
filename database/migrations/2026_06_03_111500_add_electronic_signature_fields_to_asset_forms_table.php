<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_forms', function (Blueprint $table) {
            $table->timestamp('electronically_signed_at')->nullable()->after('issued_user_id');
            $table->string('acknowledgment_signature')->nullable()->after('electronically_signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_forms', function (Blueprint $table) {
            $table->dropColumn(['electronically_signed_at', 'acknowledgment_signature']);
        });
    }
};
