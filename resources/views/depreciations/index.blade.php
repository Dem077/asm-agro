@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('general.depreciations')}}
@parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>

        <x-page-column>
            <x-box.container>
              <table
                  data-columns="{{ \App\Presenters\DepreciationPresenter::dataTableLayout() }}"
                  data-cookie-id-table="depreciationsTable"
                  data-id-table="depreciationsTable"
                  data-side-pagination="server"
                  data-sort-order="asc"
                  id="depreciationsTable"
                  data-advanced-search="false"
                  data-buttons="depreciationButtons"
                  class="table table-striped snipe-table"
                  data-url="{{ route('api.depreciations.index') }}"
                  data-export-options='{
                    "fileName": "export-depreciations-{{ date('Y-m-d') }}",
                    "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                    }'>
              </table>

            </x-box.container>
        </x-page-column>

    </x-container>

@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'depreciations-export', 'search' => true])
@stop
