@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.consumables') }}
@parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        <x-box.container>

        <table
                data-columns="{{ \App\Presenters\ConsumablePresenter::dataTableLayout() }}"
                data-cookie-id-table="consumablesTable"
                data-id-table="consumablesTable"
                data-side-pagination="server"
                data-footer-style="footerStyle"
                data-show-footer="true"
                data-sort-order="asc"
                data-sort-name="name"
                data-toolbar="#toolbar"
                id="consumablesTable"
                data-buttons="consumableButtons"
                class="table table-striped snipe-table"
                data-url="{{ route('api.consumables.index') }}"
                data-export-options='{
                "fileName": "export-consumables-{{ date('Y-m-d') }}",
                "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                }'>
        </table>

        </x-box.container>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'consumables-export', 'search' => true,'showFooter' => true, 'columns' => \App\Presenters\ConsumablePresenter::dataTableLayout()])
@stop
