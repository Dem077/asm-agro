@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.departments') }}
    @parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        <x-box.container>

            <table
                    data-columns="{{ \App\Presenters\DepartmentPresenter::dataTableLayout() }}"
                    data-cookie-id-table="departmentsTable"
                    data-id-table="departmentsTable"
                    data-side-pagination="server"
                    data-sort-order="asc"
                    id="departmentsTable"
                    data-advanced-search="false"
                    data-buttons="departmentButtons"
                    class="table table-striped snipe-table"
                    data-url="{{ route('api.departments.index') }}"
                    data-export-options='{
                  "fileName": "export-departments-{{ date('Y-m-d') }}",
                  "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                  }'>
            </table>

        </x-box.container>
    </x-container>

@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')

@stop
