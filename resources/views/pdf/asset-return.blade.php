<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Asset Return Form</title>

    <style>
        @page {
            margin: 22mm 18mm 22mm 18mm;
        }

        body {
            font-family: dejavusans;
            font-size: 9.5pt;
            color: #222;
        }

        h1 {
            text-align: center;
            font-size: 13pt;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }

        .sub-info {
            font-size: 9pt;
            line-height: 1.4;
        }

        .section-title {
            font-weight: bold;
            font-size: 10.5pt;
            margin-top: 14px;
            margin-bottom: 6px;
            border-bottom: 0.5px solid #888;
            padding-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
            font-size: 9pt;
            padding: 5px;
            border: 0.5px solid #aaa;
        }

        td {
            padding: 5px;
            font-size: 9pt;
            border: 0.5px solid #ccc;
        }

        .info-table td {
            border: none;
            padding: 3px 0;
        }

        .no-border td {
            border: none;
        }

        .terms {
            font-size: 9pt;
            line-height: 1.5;
        }

        .signature-line {
            border-bottom: 0.5px solid #000;
            height: 18px;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 8.5pt;
            color: #555;
            border-top: 0.5px solid #ccc;
            padding-top: 4px;
        }
    </style>
</head>

<body>

{{-- ================= HEADER ================= --}}
<table class="no-border">
    <tr>
        <td style="text-align: center;" colspan="2">
            <h1>ASSET RETURN FORM</h1>
        </td>
    </tr>
    <tr>
        <td width="60%">
            <div class="sub-info">
                <strong>Return Document No:</strong> {{ $assetForm->return_form_number ?? '' }}<br>
                <strong>Original Handover No:</strong> {{ $assetForm->form_number ?? '' }}<br>
                <strong>Handover Date:</strong> {{ $assetForm->created_at ? \Carbon\Carbon::parse($assetForm->created_at)->format('d-M-Y') : '' }}<br>
                <strong>Return Date:</strong> {{ $returnDate ? \Carbon\Carbon::parse($returnDate)->format('d-M-Y') : now()->format('d-M-Y') }}<br>
                <strong>Purpose:</strong> Official Asset Return Record
            </div>
        </td>
        <td width="40%" style="text-align: right">
            <img src="{{ Storage::disk('public')->url($settings) }}"
                 alt="Company logo" height="120">
        </td>
    </tr>
</table>

{{-- ================= EMPLOYEE INFORMATION ================= --}}
<div class="section-title">Employee Information</div>

<table class="info-table">
    <tr>
        <td width="25%"><strong>Full Name</strong></td>
        <td width="75%">{{ optional($assetForm->user)->displayName ?? '' }}</td>
    </tr>
    <tr>
        <td><strong>Staff ID</strong></td>
        <td>{{ optional($assetForm->user)->employee_num ?? '' }}</td>
    </tr>
    <tr>
        <td><strong>Department</strong></td>
        <td>{{ optional($assetForm->user->department)->name ?? '' }}</td>
    </tr>
    <tr>
        <td><strong>Designation</strong></td>
        <td>{{ optional($assetForm->user)->jobtitle ?? '' }}</td>
    </tr>
    <tr>
        <td><strong>Location</strong></td>
        <td>{{ optional($assetForm->user->location)->name ?? '' }}</td>
    </tr>
</table>

{{-- ================= ASSET DETAILS ================= --}}
<div class="section-title">Returned Asset Details</div>

@php $assets = $assets ?? collect(); @endphp

<table>
    <thead>
    <tr>
        <th width="5%">No</th>
        <th width="10%">Asset Tag</th>
        <th width="18%">Asset Type</th>
        <th width="15%">Brand</th>
        <th width="15%">Model</th>
        <th width="10%">Serial Number</th>
        <th width="12%">Condition</th>
        <th width="15%">Remarks</th>
    </tr>
    </thead>
    <tbody>
    @forelse($assets as $asset)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $asset->asset_tag ?? '' }}</td>
            <td>{{ $asset->notes ?? '' }}</td>
            <td>{{ optional($asset->model->manufacturer)->name ?? '' }}</td>
            <td>{{ $asset->model->name ?? '' }}</td>
            <td>{{ $asset->serial ?? '' }}</td>
            <td>{{ $asset->condition ?? '' }}</td>
            <td>{{ $asset->remarks ?? '' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="text-align: center">-</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- ================= ACCESSORIES ================= --}}
<div class="section-title">Returned Accessories</div>

<table>
    <thead>
    <tr>
        <th width="18%">Accessory Type</th>
        <th width="15%">Brand</th>
        <th width="20%">Model No</th>
        <th width="10%">Qty</th>
        <th width="12%">Condition</th>
        <th width="15%">Remarks</th>
    </tr>
    </thead>
    <tbody>
        @if($accessories->isEmpty())
            <tr>
                <td colspan="6" style="text-align: center">-</td>
            </tr>
        @else
            @foreach($accessories as $accessory)
                <tr>
                    <td>{{ $accessory->name }}</td>
                    <td>{{ $accessory->manufacturer->name ?? '' }}</td>
                    <td>{{ $accessory->model_number ?? '' }}</td>
                    <td>{{ $accessory->qty }}</td>
                    <td>{{ $accessory->condition ?? '' }}</td>
                    <td>{{ $accessory->note ?? '' }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

{{-- ================= TERMS ================= --}}
<div class="section-title">Return Acknowledgment</div>

<div class="terms">
    I confirm that the above listed assets and accessories have been returned to the company and that:
    <ul>
        <li>All items listed were originally issued under handover document {{ $assetForm->form_number ?? 'N/A' }}.</li>
        <li>I have returned the assets in the condition stated above.</li>
        <li>I no longer hold responsibility for the safekeeping of these returned items.</li>
        <li>Any discrepancies or damages have been noted in the remarks column where applicable.</li>
    </ul>
</div>

{{-- ================= SIGNATURE SECTION ================= --}}
<br><br>

<table class="no-border">
    <tr>
        <td width="50%" style="padding-right:20px;">
            <strong>Returned By</strong><br><br>
            <div class="signature-line"></div>
            Name: {{ optional($assetForm->user)->name ?? '' }}<br>
            Designation: {{ optional($assetForm->user)->jobtitle ?? '' }}<br>
            Date: {{ $returnDate ? \Carbon\Carbon::parse($returnDate)->format('d M Y') : now()->format('d M Y') }}<br>
            Signature:
        </td>

        <td width="50%" style="padding-left:20px;">
            <strong>Received By</strong><br><br>
            <div class="signature-line"></div>
            Name: {{ optional($assetForm->return_issued_user)->name ?? '' }}<br>
            Designation: {{ optional($assetForm->return_issued_user)->jobtitle ?? '' }}<br>
            Date: {{ $returnDate ? \Carbon\Carbon::parse($returnDate)->format('d M Y') : now()->format('d M Y') }}<br>
            Signature:
        </td>
    </tr>
</table>

<!--mpdf
<htmlpagefooter name="custom-footer">
<div class="footer">
AGRO NATIONAL CORPORATION PVT LTD<br>
H. Orchid, 1st Floor, Ameenee Magu, Malé 20095, Maldives<br><br>
 Page {PAGENO} of {nbpg}
</div>
</htmlpagefooter>
<sethtmlpagefooter name="custom-footer" value="on" show-this-page="1" />
mpdf-->

</body>
</html>
