<?php

namespace App\Presenters;

/**
 * Class AssetFormPresenter
 */
class AssetFormPresenter extends Presenter
{
    /**
     * JSON Column Layout for bootstrap table
     * Used in Assigned Forms tab
     */
    public static function assignedDataTableLayout()
    {
        $layout = [
            [
                'field' => 'id',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.id'),
                'visible' => false,
            ],
            [
                'field' => 'form_name',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.name'),
                'visible' => true,
            ],
            [
                'field' => 'status',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.status'),
                'visible' => true,
            ],
            [
                'field' => 'user',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.created_by'),
                'visible' => true,
                'formatter' => 'usersLinkObjFormatter',
            ],
            [
                'field' => 'download',
                'searchable' => false,
                'sortable' => false,
                'switchable' => false,
                'title' => trans('general.download'),
                'visible' => true,
                'formatter' => 'assetFormDownloadFormatter',
                'printIgnore' => true,
            ],
        ];

        return json_encode($layout);
    }

    /**
     * Direct view URL (optional)
     */
    public function viewUrl()
    {
        return url('/handover-form/' . $this->id . '/download');
    }

    /**
     * Download button HTML (if ever used server-side)
     */
    public function downloadButton()
    {
        return '<a href="' . url('/handover-form/' . $this->id . '/download') . '" 
                    class="btn btn-sm btn-primary" 
                    target="_blank">
                    <i class="fas fa-download"></i> ' . trans('general.download') . '
                </a>';
    }
}