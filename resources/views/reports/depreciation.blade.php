@extends('layouts/default')

@section('title')
{{ trans('general.depreciation_report') }}
@parent
@stop

@section('content')

<div class="row">
    <div class="col-md-12">

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.search') }}</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('reports/depreciation') }}" class="form-horizontal">
                    <div class="form-group">
                        <label for="filter_depreciation_method" class="col-sm-2 control-label">{{ trans('admin/hardware/form.depreciation') }}</label>
                        <div class="col-sm-4">
                            <select name="depreciation_method" id="filter_depreciation_method" class="form-control">
                                <option value="">{{ trans('general.all') }}</option>
                                <option value="straight_line" @selected(request('depreciation_method') === 'straight_line')>{{ trans('admin/hardware/form.depreciation_straight_line') }}</option>
                                <option value="reducing_balance" @selected(request('depreciation_method') === 'reducing_balance')>{{ trans('admin/hardware/form.depreciation_reducing_balance') }}</option>
                            </select>
                        </div>
                        <label for="filter_company_id" class="col-sm-2 control-label">{{ trans('general.company') }}</label>
                        <div class="col-sm-4">
                            <x-input.select
                                name="company_id"
                                id="filter_company_id"
                                :options="$company_list"
                                :selected="request('company_id')"
                                class="form-control"
                                style="width:100%"
                            />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="filter_category_id" class="col-sm-2 control-label">{{ trans('general.category') }}</label>
                        <div class="col-sm-4">
                            <x-input.select
                                name="category_id"
                                id="filter_category_id"
                                :options="$category_list"
                                :selected="request('category_id')"
                                class="form-control"
                                style="width:100%"
                            />
                        </div>
                        <label for="filter_status_id" class="col-sm-2 control-label">{{ trans('general.status') }}</label>
                        <div class="col-sm-4">
                            <x-input.select
                                name="status_id"
                                id="filter_status_id"
                                :options="$statuslabel_list"
                                :selected="request('status_id')"
                                class="form-control"
                                style="width:100%"
                            />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                {{ trans('general.search') }}
                            </button>
                            <a href="{{ route('reports/depreciation') }}" class="btn btn-default">{{ trans('admin/hardware/general.clear') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($depreciableAssetsCount > 0)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('admin/depreciations/general.daily_export_title') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted" style="margin-bottom: 15px;">{{ trans('admin/depreciations/general.daily_export_help') }}</p>

                    <form method="POST" action="{{ route('reports.depreciation.daily-export') }}" id="dailyDepreciationExportForm" class="form-horizontal">
                        @csrf
                        <div class="form-group">
                            <label for="daily_export_start_date" class="col-sm-2 control-label">{{ trans('admin/depreciations/general.start_date') }}</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control datepicker" id="daily_export_start_date" name="start_date" value="{{ old('start_date', request('start_date', now()->startOfMonth()->format('Y-m-d'))) }}" placeholder="{{ trans('general.select_date') }}" required>
                            </div>
                            <label for="daily_export_end_date" class="col-sm-2 control-label">{{ trans('admin/depreciations/general.end_date') }}</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control datepicker" id="daily_export_end_date" name="end_date" value="{{ old('end_date', request('end_date', now()->format('Y-m-d'))) }}" placeholder="{{ trans('general.select_date') }}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-success" id="dailyDepreciationExportButton" disabled>
                                    <i class="fas fa-download" aria-hidden="true"></i>
                                    {{ trans('admin/depreciations/general.daily_export_button') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="box box-default">
                <div class="box-body">
                    <table
                        data-cookie-id-table="depreciationReport"
                        data-id-table="depreciationReport"
                        data-side-pagination="server"
                        data-sort-order="desc"
                        data-sort-name="created_at"
                        data-show-footer="true"
                        data-click-to-select="true"
                        data-bulk-form-id="#dailyDepreciationExportForm"
                        data-bulk-button-id="#dailyDepreciationExportButton"
                        id="depreciationReport"
                        data-advanced-search="false"
                        data-url="{{ route('api.depreciation-report.index', $apiParams) }}"
                        data-mobile-responsive="true"
                        class="table table-striped snipe-table"
                        data-columns="{{ \App\Presenters\DepreciationReportPresenter::dataTableLayout() }}"
                        data-export-options='{
                          "fileName": "depreciation-report-{{ date('Y-m-d') }}",
                          "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                    </table>
                </div>
            </div>
        @else
            <div class="box box-default">
                <div class="box-body">
                    <div class="alert alert-warning fade in" style="margin-bottom: 0;">
                        <i class="fas fa-exclamation-triangle faa-pulse animated"></i>
                        {!! trans('admin/depreciations/general.no_depreciations_warning') !!}
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
