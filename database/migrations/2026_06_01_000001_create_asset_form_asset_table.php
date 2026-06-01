<?php

use App\Models\AssetForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_form_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_form_id')->constrained('asset_forms')->cascadeOnDelete();
            $table->unsignedInteger('asset_id');
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->unique(['asset_form_id', 'asset_id']);
        });

        AssetForm::query()->each(function (AssetForm $form) {
            $form->syncAssetIdsFromStoredData();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_form_asset');
    }
};
