<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetForm;
use App\Models\CheckoutAcceptance;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

class HandoverPdfService
{
    /**
     * @return array{assetForm: AssetForm, assets: Collection, accessories: Collection, settings: mixed, returnDate: mixed, physicalSignatureRequired: bool, electronicallySignedAt: string|null, acknowledgmentSignature: string|null}
     */
    public function buildHandoverPdfData(
        AssetForm $assetForm,
        Collection $assetItems,
        ?string $electronicallySignedAt = null,
        ?string $acknowledgmentSignature = null
    ): array {
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

        $settings = Setting::getSettings();

        $signedAt = $assetForm->electronically_signed_at
            ?? $this->findAcceptedAtForForm($assetForm, $asset_ids);

        if ($electronicallySignedAt === null && $signedAt) {
            $electronicallySignedAt = Helper::getFormattedDateObject(
                \Carbon\Carbon::parse($signedAt)->format('Y-m-d H:i:s'),
                'datetime',
                false
            );
        }

        $electronicallySignedAtDate = $signedAt
            ? Helper::getFormattedDateObject(
                \Carbon\Carbon::parse($signedAt)->format('Y-m-d H:i:s'),
                'date',
                false
            )
            : null;

        if ($acknowledgmentSignature === null && $assetForm->acknowledgment_signature) {
            $signaturePath = 'private_uploads/signatures/' . $assetForm->acknowledgment_signature;
            if (Storage::exists($signaturePath)) {
                $acknowledgmentSignature = base64_encode(Storage::get($signaturePath));
            }
        }

        return [
            'assetForm' => $assetForm,
            'assets' => $assets,
            'accessories' => $accessories,
            'settings' => $settings?->logo,
            'returnDate' => null,
            'physicalSignatureRequired' => (bool) ($settings?->physical_signature_required ?? true),
            'electronicallySignedAt' => $electronicallySignedAt,
            'electronicallySignedAtDate' => $electronicallySignedAtDate,
            'acknowledgmentSignature' => $acknowledgmentSignature,
        ];
    }

    public function renderPdf(string $view, array $data, string $filename, string $destination = \Mpdf\Output\Destination::INLINE)
    {
        $html = view($view, $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_footer' => 12,
            'margin_bottom' => 28,
        ]);

        $mpdf->WriteHTML($html);

        if ($destination === \Mpdf\Output\Destination::STRING_RETURN) {
            return $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
        }

        return $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function resolveFormForAsset(Asset $asset): ?AssetForm
    {
        $asset->loadMissing('assetform');

        if ($asset->assetform) {
            return $asset->assetform;
        }

        return AssetForm::forAsset($asset->id)->latest('id')->first();
    }

    /**
     * Generate the handover PDF binary for an asset acceptance.
     */
    public function generateAcceptanceHandoverPdf(
        Asset $asset,
        ?string $electronicallySignedAt = null,
        ?string $acknowledgmentSignature = null
    ): ?string {
        $assetForm = $this->resolveFormForAsset($asset);

        if (! $assetForm) {
            return null;
        }

        $assetItems = collect($assetForm->assets['assets'] ?? []);
        $data = $this->buildHandoverPdfData(
            $assetForm,
            $assetItems,
            $electronicallySignedAt,
            $acknowledgmentSignature
        );

        $filename = ($assetForm->form_number ?? 'handover') . '.pdf';

        return $this->renderPdf(
            'pdf.asset-handover',
            $data,
            $filename,
            \Mpdf\Output\Destination::STRING_RETURN
        );
    }

    /**
     * Stream a signed handover PDF for an accepted asset (EULA downloads).
     */
    public function downloadHandoverForAsset(Asset $asset, ?string $downloadName = null, bool $inline = false): ?Response
    {
        $assetForm = $this->resolveFormForAsset($asset);

        if (! $assetForm) {
            return null;
        }

        $pdfContent = $this->generateAcceptanceHandoverPdf($asset);

        if ($pdfContent === null) {
            return null;
        }

        $filename = $downloadName ?: (($assetForm->form_number ?? 'handover') . '.pdf');
        $disposition = $inline ? 'inline' : 'attachment';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }

    /**
     * Persist electronic acceptance details on the handover form.
     */
    public function markFormElectronicallySigned(AssetForm $assetForm, ?string $signatureFilename = null): void
    {
        $assetForm->electronically_signed_at = now();

        if ($signatureFilename) {
            $assetForm->acknowledgment_signature = $signatureFilename;
        }

        $assetForm->save();
    }

    protected function findAcceptedAtForForm(AssetForm $assetForm, Collection $assetIds): mixed
    {
        if ($assetIds->isEmpty()) {
            return null;
        }

        $acceptance = CheckoutAcceptance::query()
            ->where('checkoutable_type', Asset::class)
            ->whereIn('checkoutable_id', $assetIds->all())
            ->whereNotNull('accepted_at')
            ->latest('accepted_at')
            ->first();

        return $acceptance?->accepted_at;
    }
}
