@php
    $assetForm = $handoverPreview['assetForm'] ?? null;
    $assets = $handoverPreview['assets'] ?? collect();
    $accessories = $handoverPreview['accessories'] ?? collect();
@endphp

<div class="col-md-12" style="padding-top: 5px; padding-bottom: 15px;">
    <div style="background-color: rgba(211,211,211,0.25); padding: 15px; border: lightgrey 1px solid;">
        <div class="clearfix" style="margin-bottom: 10px;">
            <h3 style="margin-top: 0; display: inline-block;">Asset Handover Form</h3>
            @if($assetForm)
                <a href="{{ route('handover-form.download', $assetForm->id) }}"
                   class="btn btn-sm btn-default pull-right"
                   target="_blank"
                   rel="noopener">
                    <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                    View / Download Form
                </a>
            @endif
        </div>

        @if($assetForm)
            <p>
                <strong>Document No:</strong> {{ $assetForm->form_number }}
                &nbsp;|&nbsp;
                <strong>Handover Date:</strong>
                {{ $assetForm->created_at ? \Carbon\Carbon::parse($assetForm->created_at)->format('d-M-Y') : '' }}
            </p>
        @endif

        <h4>Employee Information</h4>
        <table class="table table-condensed" style="margin-bottom: 15px;">
            <tr>
                <th style="width: 25%;">Full Name</th>
                <td>{{ optional($assetForm?->user)->displayName ?? optional($assetForm?->user)->name ?? '' }}</td>
            </tr>
            <tr>
                <th>Staff ID</th>
                <td>{{ optional($assetForm?->user)->employee_num ?? '' }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ optional(optional($assetForm?->user)->department)->name ?? '' }}</td>
            </tr>
            <tr>
                <th>Designation</th>
                <td>{{ optional($assetForm?->user)->jobtitle ?? '' }}</td>
            </tr>
        </table>

        <h4>Asset Details</h4>
        <table class="table table-bordered table-condensed" style="margin-bottom: 15px;">
            <thead>
            <tr>
                <th>No</th>
                <th>Asset Tag</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Serial Number</th>
            </tr>
            </thead>
            <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $asset->asset_tag ?? '' }}</td>
                    <td>{{ optional(optional($asset->model)->manufacturer)->name ?? '' }}</td>
                    <td>{{ optional($asset->model)->name ?? '' }}</td>
                    <td>{{ $asset->serial ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">-</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        @if($accessories->isNotEmpty())
            <h4>Accessories</h4>
            <table class="table table-bordered table-condensed" style="margin-bottom: 15px;">
                <thead>
                <tr>
                    <th>Accessory</th>
                    <th>Brand</th>
                    <th>Qty</th>
                </tr>
                </thead>
                <tbody>
                @foreach($accessories as $accessory)
                    <tr>
                        <td>{{ $accessory->name }}</td>
                        <td>{{ optional($accessory->manufacturer)->name ?? '' }}</td>
                        <td>{{ $accessory->qty }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <h4>Terms &amp; Conditions</h4>
        <p>I acknowledge receipt of the above listed assets in good working condition and agree that:</p>
        <ul>
            <li>Assets are for official use only.</li>
            <li>I am responsible for safekeeping and proper usage.</li>
            <li>Assets must not be transferred without approval.</li>
            <li>Assets must be returned upon request, resignation, or termination.</li>
            <li>Loss or damage due to negligence may result in recovery of cost.</li>
        </ul>
    </div>
</div>
