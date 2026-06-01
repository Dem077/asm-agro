<?php

namespace App\Http\Requests;

class BulkAssetCheckinRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = \App\Models\Setting::getSettings();

        $rules = [
            'selected_assets' => 'required|array|min:1',
            'selected_assets.*' => 'integer|exists:assets,id',
            'status_id' => 'nullable|exists:status_labels,id',
            'location_id' => 'nullable|exists:locations,id',
            'checkin_at' => 'nullable|date',
            'download_form' => 'nullable|boolean',
            'update_default_location' => 'nullable|in:0,1',
        ];

        if ($settings->require_checkinout_notes) {
            $rules['note'] = 'required|string';
        }

        return $rules;
    }
}
