<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\HrApiSyncService;
use Illuminate\Http\RedirectResponse;

class HrSyncController extends Controller
{
    public function __construct(private HrApiSyncService $hrApiSyncService)
    {
    }

    public function store(): RedirectResponse
    {
        $this->authorize('update', \App\Models\User::class);

        $result = $this->hrApiSyncService->sync();

        if ($result['error']) {
            return redirect()
                ->route('users.index')
                ->with('error', $result['error_message']);
        }

        return redirect()
            ->route('users.index')
            ->with('success', trans('admin/users/message.hr_api_sync_success'))
            ->with('hr_sync_summary', $result['summary']);
    }
}
