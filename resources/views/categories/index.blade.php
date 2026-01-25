@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.categories') }}
@parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        <x-box.container>

          <x-tables.bulk-actions
                  id_divname='categoriesBulkEditToolbar'
                  action_route="{{route('categories.bulk.delete')}}"
                  id_formname="categoriesBulkForm"
                  id_button="bulkCategoryEditButton"
                  model_name="category"
          >
              @can('delete', App\Models\Category::class)
                  <option>Delete</option>
              @endcan
          </x-tables.bulk-actions>

          <table
                  data-columns="{{ \App\Presenters\CategoryPresenter::dataTableLayout() }}"
            data-cookie-id-table="categoryTable"
            data-id-table="categoryTable"
            data-side-pagination="server"
            data-sort-order="asc"
            id="categoryTable"
            {{-- begin stuff for bulk dropdown --}}
            data-toolbar="#categoriesBulkEditToolbar"
            data-bulk-button-id="#bulkCategoryEditButton"
            data-bulk-form-id="#categoriesBulkForm"
            {{-- end stuff for bulk dropdown --}}
            data-buttons="categoryButtons"
            class="table table-striped snipe-table"
            data-url="{{ route('api.categories.index') }}"
            data-export-options='{
              "fileName": "export-categories-{{ date('Y-m-d') }}",
              "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
              }'>
          </table>
        </x-box.container>
    </x-container>

@stop

@section('moar_scripts')
  @include ('partials.bootstrap-table',
      ['exportFile' => 'category-export',
      'search' => true,
      'columns' => \App\Presenters\CategoryPresenter::dataTableLayout()
  ])
@stop

