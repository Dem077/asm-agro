<?php

namespace App\Http\Controllers\Assets;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetForm;
use App\Models\Setting;
use App\Models\User;
use Mpdf\Mpdf;
class AssetHandoverController
{
    public function downloadPdf($asset_form_id)
    {
        $assetForm = AssetForm::findOrFail($asset_form_id);

        $assetItems = collect($assetForm->assets['assets']);

        // Get all asset IDs
        $asset_ids = $assetItems->pluck('id');

        $assets = Asset::with('model.manufacturer')
            ->whereIn('id', $asset_ids)
            ->get();

        // Collect ALL accessory IDs across all assets
        $allAccessoryIds = collect($assetItems)
            ->when(isset($assetItems['assigned_accessories']), fn ($c) => collect([$assetItems]))
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
            'issued_user.department',
            'issued_user.location',
            'user.location'
        ]);

        $settings = Setting::first();

        $data = [
            'assetForm' => $assetForm,
            'assets'=>$assets,
            'accessories' => $accessories,
            'settings'=>$settings->logo,
        ];

        $html = view('pdf.asset-handover', $data)->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
//            'margin_header' => '0',
//            'margin_top' => '15',
//            'margin_bottom' => '30',
//            'margin_footer' => '10',
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output($assetForm->form_number .'.pdf', \Mpdf\Output\Destination::INLINE)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$assetForm->form_number .'.pdf"');
    }
}