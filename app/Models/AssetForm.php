<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class AssetForm extends Model
{
    protected $fillable = ['form_number', 'asset_id', 'user_id' , 'status' , 'issued_user_id','assets','returned_assets','return_form_number','return_issued_user_id'];


    protected $casts = [
        'assets' => 'array',
        'returned_assets' => 'array',
    ];
    public function asset(): HasMany
    {
        return $this->hasMany(Asset::class, 'form_id');
    }

    public function formAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_form_asset', 'asset_form_id', 'asset_id')
            ->withTimestamps();
    }



    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function issued_user() :BelongsTo
    {
        return $this->belongsTo(User::class , 'issued_user_id');
    }
    public function return_issued_user() :BelongsTo
    {
        return $this->belongsTo(User::class , 'return_issued_user_id');
    }

    /**
     * Forms currently or historically associated with an asset.
     */
    public function scopeForAsset(Builder $query, int $assetId): Builder
    {
        $jsonIdPatterns = [
            '%"id":'.$assetId.',%',
            '%"id":'.$assetId.'}%',
            '%"id":'.$assetId.']%',
            '%"id": '.$assetId.',%',
            '%"id": '.$assetId.'}%',
            '%"id": '.$assetId.']%',
        ];

        return $query->where(function (Builder $q) use ($assetId, $jsonIdPatterns) {
            $q->whereHas('formAssets', fn (Builder $assetQuery) => $assetQuery->where('assets.id', $assetId))
                ->orWhereHas('asset', fn (Builder $assetQuery) => $assetQuery->where('assets.id', $assetId));

            foreach ($jsonIdPatterns as $pattern) {
                $q->orWhere('assets', 'like', $pattern)
                    ->orWhere('returned_assets', 'like', $pattern);
            }
        });
    }

    /**
     * Persist asset IDs from JSON payloads and active form_id links (for form history lookups).
     */
    public function syncAssetIdsFromStoredData(): void
    {
        $ids = static::extractAssetIdsFromPayload($this->assets, $this->returned_assets);

        $linkedViaFormId = $this->asset()->pluck('assets.id');
        $allIds = $ids->merge($linkedViaFormId)->unique()->filter()->values();

        if ($allIds->isEmpty()) {
            return;
        }

        $this->formAssets()->syncWithoutDetaching($allIds->all());
    }

    /**
     * @param  array<string, mixed>|null  $assetsPayload
     * @param  array<string, mixed>|null  $returnedPayload
     */
    public static function extractAssetIdsFromPayload(?array $assetsPayload, ?array $returnedPayload = null): Collection
    {
        $ids = collect();

        foreach ($assetsPayload['assets'] ?? [] as $item) {
            $ids = $ids->merge(static::extractIdsFromItem($item));
        }

        foreach ($returnedPayload['assets'] ?? [] as $batch) {
            if (! is_array($batch)) {
                continue;
            }

            foreach ($batch as $item) {
                $ids = $ids->merge(static::extractIdsFromItem($item));
            }
        }

        return $ids->unique()->filter()->values();
    }

    /**
     * @param  mixed  $item
     */
    private static function extractIdsFromItem($item): Collection
    {
        if (! is_array($item)) {
            return collect();
        }

        if (isset($item['id'])) {
            return collect([(int) $item['id']]);
        }

        return collect($item)->flatMap(fn ($nested) => static::extractIdsFromItem($nested));
    }
}