<?php

namespace App\Http\Controllers\Assets;

use App\Enums\ActionType;
use App\Exceptions\CheckoutNotAllowed;
use App\Helpers\Helper;
use App\Http\Controllers\CheckInOutRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetCheckoutRequest;
use App\Models\Asset;
use App\Models\AssetForm;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use \Illuminate\Contracts\View\View;
use \Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class AssetCheckoutController extends Controller
{
    use CheckInOutRequest;

    private $downloadform;

    /**
     * Returns a view that presents a form to check an asset out to a
     * user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param int $assetId
     * @since [v1.0]
     * @return \Illuminate\Contracts\View\View
     */
    public function create(Asset $asset) : View | RedirectResponse
    {

        $this->authorize('checkout', $asset);

        if (!$asset->model) {
            return redirect()->route('hardware.show', $asset)
                ->with('error', trans('admin/hardware/general.model_invalid_fix'));
        }

        // Invoke the validation to see if the audit will complete successfully
        $asset->setRules($asset->getRules() + $asset->customFieldValidationRules());

        if ($asset->isInvalid()) {
            return redirect()->route('hardware.edit', $asset)->withErrors($asset->getErrors());
        }

        
        if ($asset->availableForCheckout()) {
            return view('hardware/checkout', compact('asset'))
                ->with('statusLabel_list', Helper::deployableStatusLabelList())
                ->with('table_name', 'Assets')
                ->with('item', $asset);
        }

        return redirect()->route('hardware.index')
            ->with('error', trans('admin/hardware/message.checkout.not_available'));
    }

    /**
     * Validate and process the form data to check out an asset to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param AssetCheckoutRequest $request
     * @since [v1.0]
     */
    public function store(AssetCheckoutRequest $request, $assetId) : RedirectResponse
    {


        try {
            // Check if the asset exists
            if (! $asset = Asset::find($assetId)) {
                return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.does_not_exist'));
            } elseif (! $asset->availableForCheckout()) {
                return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.checkout.not_available'));
            }
            $this->authorize('checkout', $asset);

            if (!$asset->model) {
                return redirect()->route('hardware.show', $asset)->with('error', trans('admin/hardware/general.model_invalid_fix'));
            }

            $admin = auth()->user();

            $target = $this->determineCheckoutTarget();
            session()->put(['checkout_to_type' => $target]);

            $asset = $this->updateAssetLocation($asset, $target);

            $checkout_at = date('Y-m-d H:i:s');
            if (($request->filled('checkout_at')) && ($request->input('checkout_at') != date('Y-m-d'))) {
                $checkout_at = $request->input('checkout_at');
            }

            $expected_checkin = '';
            if ($request->filled('expected_checkin')) {
                $expected_checkin = $request->input('expected_checkin');
            }

            if ($request->filled('status_id')) {
                $asset->status_id = $request->input('status_id');
            }
            if ($request->filled('form_user_id')) {
                $form_assigned_user = $request->input('form_user_id');
            }

            if(!empty($asset->licenseseats->all())){
                if(request('checkout_to_type') == 'user') {
                    foreach ($asset->licenseseats as $seat){
                        $seat->assigned_to = $target->id;
                        $seat->save();
                    }
                }
            }

            // Add any custom fields that should be included in the checkout
            $asset->customFieldsForCheckinCheckout('display_checkout');

            $settings = \App\Models\Setting::getSettings();

            // We have to check whether $target->company_id is null here since locations don't have a company yet
            if (($settings->full_multiple_companies_support) && ((!is_null($target->company_id)) &&  (!is_null($asset->company_id)))) {
                if ($target->company_id != $asset->company_id){
                    return redirect()->route('hardware.checkout.create', $asset)->with('error', trans('general.error_user_company'));
                }
            }

            $this->downloadform = $request->input('download_form');

            session()->put(['redirect_option' => $request->input('redirect_option'), 'checkout_to_type' => $request->input('checkout_to_type')]);

            if ($asset->checkOut($target, $admin, $checkout_at, $expected_checkin, $request->input('note'), $request->input('name'))) {

                $formNumber = $this->generateFormNumber($form_assigned_user , [$asset]);

                if ($this->downloadform) {


                    return redirect()->route('handover-form.download', $formNumber['form_id'])->with([
                        'success' => trans('admin/hardware/message.checkout.success')
                    ]);
                }

                return Helper::getRedirectOption($request, $asset->id, 'Assets')
                    ->with('success', trans('admin/hardware/message.checkout.success'));
            }
            // Redirect to the asset management page with error
            return redirect()->route("hardware.checkout.create", $asset)->with('error', trans('admin/hardware/message.checkout.error').$asset->getErrors());
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', trans('admin/hardware/message.checkout.error'))->withErrors($asset->getErrors());
        } catch (CheckoutNotAllowed $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function generateFormNumber($user, $assets)
    {
        $year = date('Y');

        // Get last form number
        $lastForm = AssetForm::where('form_number', 'like', "AGRO/HN/{$year}/%")
            ->orderBy('form_number', 'desc')
            ->first();

        if ($lastForm && preg_match('/AGRO\/HN\/\d{4}\/(\d+)/', $lastForm->form_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
//        dd($assets);
//        $assets = collect($assets)->map(function ($asset) {
//            return is_numeric($asset) ? Asset::findOrFail($asset) : $asset;
//        });
        $formNumber = sprintf('AGRO/HN/%s/%02d', $year, $nextNumber);

        // Ensure collection
        $assets = collect($assets);

        $assetsData = $assets->map(function ($asset) {

            $asset->load([
                'assignedAccessories.accessory:id,name'
            ]);

            return [
                'id'   => $asset->id,
                'name' => $asset->name,
                'assigned_accessories' => $asset->assignedAccessories->map(function ($assigned) {
                    if (!$assigned->accessory) {
                        return null;
                    }

                    return [
                        'id'   => $assigned->accessory->id,
                        'name' => $assigned->accessory->name,
                    ];
                })->filter()->values(),
            ];
        })->values();

        // Create form
        $createdform = AssetForm::create([
            'form_number' => $formNumber,
            'user_id' => $user,
            'status' => ActionType::CheckedOut,
            'issued_user_id' => Auth::id(),
            'assets' => ['assets' => $assetsData],
        ]);

        // Update each asset
        foreach ($assets as $asset) {
            $asset->update([
                'form_id' => $createdform->id,
            ]);
        }

        return [
            'form_number' => $formNumber,
            'form_id' => $createdform->id
        ];
    }
}
