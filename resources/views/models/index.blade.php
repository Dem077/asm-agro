@extends('layouts/default')

{{-- Page title --}}
@section('title')

  @if (request()->input('status')=='deleted')
    {{ trans('admin/models/general.view_deleted') }}
    {{ trans('admin/models/table.title') }}
    @else
    {{ trans('admin/models/general.view_models') }}
  @endif
@parent
@stop

{{-- Page content --}}
@section('content')

    <x-container>
        <x-box.container>

    @include('partials.models-bulk-actions')
            <table
                    data-columns="{{ \App\Presenters\AssetModelPresenter::dataTableLayout() }}"
                    data-cookie-id-table="asssetModelsTable"
                    data-id-table="asssetModelsTable"
                    data-show-footer="true"
                    data-side-pagination="server"
                    data-footer-style="footerStyle"
                    data-toolbar="#modelsBulkEditToolbar"
                    data-bulk-button-id="#bulkModelsEditButton"
                    data-bulk-form-id="#modelsBulkForm"
                    data-sort-order="asc"
                    id="asssetModelsTable"
                    data-buttons="modelButtons"
                    class="table table-striped snipe-table"
                    data-url="{{ route('api.models.index', ['status' => e(request('status'))]) }}"
                    data-export-options='{
          "fileName": "export-models-{{ date('Y-m-d') }}",
          "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
          }'>
          </table>
        </x-box.container>
    </x-container>

@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'models-export', 'search' => true])

@stop
