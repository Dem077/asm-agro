@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/licenses/general.software_licenses') }}
@parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        <x-box.container>

          <table
              data-columns="{{ \App\Presenters\LicensePresenter::dataTableLayout() }}"
              data-cookie-id-table="licensesTable"
              data-side-pagination="server"
              data-footer-style="footerStyle"
              data-show-footer="true"
              data-sort-order="asc"
              data-sort-name="name"
              id="licensesTable"
              data-buttons="licenseButtons"
              class="table table-striped snipe-table"
              data-url="{{ route('api.licenses.index', ['status' => e(request('status'))]) }}"
              data-export-options='{
            "fileName": "export-licenses-{{ date('Y-m-d') }}",
            "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
            }'>
          </table>

        </x-box.container>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table')

@stop
