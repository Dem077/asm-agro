<?php

namespace App\Http\Controllers\Assets;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetForm;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;

class AssetHandoverController
{
    public function downloadPdf($asset_form_id)
    {
        $assetForm = AssetForm::findOrFail($asset_form_id);

        $assetItems = collect($assetForm->assets['assets'] ?? []);

        $data = $this->buildHandoverPdfData($assetForm, $assetItems);

        return $this->renderPdf('pdf.asset-handover', $data, $assetForm->form_number . '.pdf');
    }

    public function downloadReturnPdf($asset_form_id)
    {
        $assetForm = AssetForm::findOrFail($asset_form_id);

        if (empty($assetForm->return_form_number)) {
            abort(404, 'Return form not found for this handover record.');
        }

        [$assetItems, $returnDate] = $this->resolveReturnedAssetItems($assetForm);

        if ($assetItems->isEmpty()) {
            $assetItems = collect($assetForm->assets['assets'] ?? []);
        }

        $data = $this->buildHandoverPdfData($assetForm, $assetItems);
        $data['returnDate'] = $returnDate;

        return $this->renderPdf('pdf.asset-return', $data, $assetForm->return_form_number . '.pdf');
    }

    /**
     * @return array{0: Collection, 1: string|null}
     */
    private function resolveReturnedAssetItems(AssetForm $assetForm): array
    {
        $batches = collect($assetForm->returned_assets['assets'] ?? []);
        $lastBatch = $batches->last();

        if (empty($lastBatch)) {
            return [collect(), null];
        }

        $assetItems = collect($lastBatch)
            ->flatten(1)
            ->map(fn ($item) => is_array($item) ? $item : (array) $item)
            ->filter(fn ($item) => !empty($item['id']))
            ->values();

        $returnDate = $assetItems->first()['date'] ?? $assetForm->updated_at;

        return [$assetItems, $returnDate];
    }

    private function buildHandoverPdfData(AssetForm $assetForm, Collection $assetItems): array
    {
        $asset_ids = $assetItems->pluck('id')->filter();

        $assets = Asset::with('model.manufacturer')
            ->whereIn('id', $asset_ids)
            ->get();

        $allAccessoryIds = $assetItems
            ->flatMap(fn ($asset) => collect($asset['assigned_accessories'] ?? []))
            ->pluck('id');

        $quantities = $allAccessoryIds->countBy();

        $accessories = Accessory::whereIn('id', $quantities->keys())
            ->with('manufacturer')
            ->get()
            ->map(function ($accessory) use ($quantities) {
                $accessory->qty = $quantities[$accessory->id] ?? 1;

                return $accessory;
            });

        $assetForm->load([
            'user.department',
            'user.location',
            'issued_user.department',
            'issued_user.location',
            'return_issued_user',
        ]);

        $settings = Setting::first();

        return [
            'assetForm' => $assetForm,
            'assets' => $assets,
            'accessories' => $accessories,
            'settings' => $settings->logo,
            'returnDate' => null,
        ];
    }

    private function renderPdf(string $view, array $data, string $filename)
    {
        $html = view($view, $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_footer' => 12,
            'margin_bottom' => 28,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}