<?php

namespace App\Http\Traits;

use App\Enums\ActionType;
use App\Models\Asset;
use App\Models\AssetForm;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait ManagesAssetForms
{
    /**
     * Create a handover form for one or more assets (checkout / bulk checkout).
     *
     * @param  iterable<Asset>  $assets
     * @return array{form_number: string, form_id: int}|null
     */
    protected function createHandoverForm(?int $userId, iterable $assets): ?array
    {
        if (empty($userId)) {
            return null;
        }

        $assets = collect($assets)->filter();
        if ($assets->isEmpty()) {
            return null;
        }

        $year = date('Y');

        $lastForm = AssetForm::where('form_number', 'like', "AGRO/HN/{$year}/%")
            ->orderBy('form_number', 'desc')
            ->first();

        if ($lastForm && preg_match('/AGRO\/HN\/\d{4}\/(\d+)/', $lastForm->form_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        $formNumber = sprintf('AGRO/HN/%s/%02d', $year, $nextNumber);

        $assetsData = $assets->map(function (Asset $asset) {
            $asset->load([
                'assignedAccessories.accessory:id,name',
            ]);

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'assigned_accessories' => $asset->assignedAccessories->map(function ($assigned) {
                    if (! $assigned->accessory) {
                        return null;
                    }

                    return [
                        'id' => $assigned->accessory->id,
                        'name' => $assigned->accessory->name,
                    ];
                })->filter()->values(),
            ];
        })->values();

        $createdForm = AssetForm::create([
            'form_number' => $formNumber,
            'user_id' => $userId,
            'status' => ActionType::CheckedOut,
            'issued_user_id' => Auth::id(),
            'assets' => ['assets' => $assetsData],
        ]);

        $createdForm->syncAssetIdsFromStoredData();

        foreach ($assets as $asset) {
            $asset->update([
                'form_id' => $createdForm->id,
            ]);
        }

        return [
            'form_number' => $formNumber,
            'form_id' => $createdForm->id,
        ];
    }

    /**
     * Complete return paperwork for checked-in asset(s) tied to handover form(s).
     *
     * @param  iterable<Asset>  $assets
     * @return array{form_number: string, form_id: int}|null
     */
    protected function processAssetReturn(iterable $assets): ?array
    {
        $assets = collect($assets)->filter();
        if ($assets->isEmpty()) {
            return null;
        }

        $year = date('Y');

        $lastForm = AssetForm::where('return_form_number', 'like', "AGRO/RN/{$year}/%")
            ->orderBy('return_form_number', 'desc')
            ->first();

        if ($lastForm && preg_match('/AGRO\/RN\/\d{4}\/(\d+)/', $lastForm->return_form_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        $formNumber = sprintf('AGRO/RN/%s/%02d', $year, $nextNumber);

        $assetsData = $assets->map(function (Asset $asset) {
            $asset->load([
                'assignedAccessories.accessory:id,name',
            ]);

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'date' => now(),
                'returned_by' => Auth::id(),
                'assigned_accessories' => $asset->assignedAccessories->map(function ($assigned) {
                    if (! $assigned->accessory) {
                        return null;
                    }

                    return [
                        'id' => $assigned->accessory->id,
                        'name' => $assigned->accessory->name,
                    ];
                })->filter()->values(),
            ];
        })->values();

        $handoverForm = $assets->first()->assetform;

        if (! $handoverForm) {
            return null;
        }

        $existingReturnedAssets = collect($handoverForm->returned_assets['assets'] ?? []);
        $mergedAssets = $existingReturnedAssets->push($assetsData->all())->values()->all();

        $handoverForm->update([
            'return_form_number' => $formNumber,
            'status' => ActionType::Returned,
            'return_issued_user_id' => Auth::id(),
            'returned_assets' => ['assets' => $mergedAssets],
        ]);

        $handoverForm->syncAssetIdsFromStoredData();

        foreach ($assets as $asset) {
            $asset->update([
                'form_id' => null,
            ]);
        }

        return [
            'form_number' => $formNumber,
            'form_id' => $handoverForm->id,
        ];
    }

    /**
     * Process return forms for bulk check-in, grouped by handover form.
     *
     * @param  iterable<Asset>  $assets
     * @return array{form_number: string, form_id: int}|null
     */
    protected function processBulkAssetReturns(iterable $assets): ?array
    {
        $assets = collect($assets)->filter();
        $lastResult = null;

        foreach ($assets->groupBy('form_id') as $formId => $group) {
            if (! $formId) {
                continue;
            }

            $group->each->load('assetform');
            $lastResult = $this->processAssetReturn($group);
        }

        return $lastResult;
    }

    /**
     * Resolve the employee user ID for handover forms on checkout.
     */
    protected function resolveHandoverFormUserId(?int $formUserId, mixed $checkoutTarget): ?int
    {
        if ($formUserId) {
            return $formUserId;
        }

        if ($checkoutTarget instanceof User) {
            return $checkoutTarget->id;
        }

        return null;
    }
}
