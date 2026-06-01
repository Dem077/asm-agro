@extends('layouts/default')

@section('title')
    {{ trans('admin/hardware/general.bulk_checkin') }}
    @parent
@stop

@section('content')

<style>
    .input-group {
        padding-left: 0px !important;
    }
</style>

<div class="row">
    <div class="col-md-7">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('admin/hardware/form.tag') }}</h2>
            </div>
            <div class="box-body">
                <form class="form-horizontal" method="post" action="{{ route('hardware.bulkcheckin.store') }}" autocomplete="off">
                    {{ csrf_field() }}

                    @if ($removed_assets->isNotEmpty())
                        <div class="box box-solid box-warning">
                            <div class="box-header with-border">
                                <span class="box-title col-xs-12">Warning</span>
                            </div>
                            <div class="box-body">
                                <p>{{ trans('admin/hardware/message.multi-checkin.not_checked_out') }}</p>
                                <ul>
                                    @foreach($removed_assets as $removed_asset)
                                        <li>
                                            <a href="{{ route('hardware.show', $removed_asset->id) }}">
                                                {{ $removed_asset->present()->fullName }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @include('partials.forms.edit.asset-select', [
                        'translated_name' => trans('general.assets'),
                        'fieldname' => 'selected_assets[]',
                        'multiple' => true,
                        'required' => true,
                        'asset_status_type' => 'Deployed',
                        'select_id' => 'selected_assets_checkin',
                        'asset_selector_div_id' => 'assets_to_checkin_div',
                        'asset_ids' => old('selected_assets'),
                    ])

                    <div class="form-group {{ $errors->has('status_id') ? 'error' : '' }}">
                        <label for="status_id" class="col-md-3 control-label">
                            {{ trans('admin/hardware/form.status') }}
                        </label>
                        <div class="col-md-7 required">
                            <x-input.select
                                name="status_id"
                                :options="$statusLabel_list"
                                :selected="old('status_id')"
                                style="width: 100%;"
                                aria-label="status_id"
                            />
                            {!! $errors->first('status_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <x-input.location-select
                        :label="trans('general.location')"
                        name="location_id"
                        :selected="old('location_id')"
                    />

                    <div class="form-group">
                        <div class="col-md-9 col-md-offset-3">
                            <label class="form-control">
                                <input name="update_default_location" type="radio" value="1" checked="checked" aria-label="update_default_location" />
                                {{ trans('admin/hardware/form.asset_location') }}
                            </label>
                            <label class="form-control">
                                <input name="update_default_location" type="radio" value="0" aria-label="update_default_location" />
                                {{ trans('admin/hardware/form.asset_location_update_default_current') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('checkin_at') ? ' has-error' : '' }}">
                        <label for="checkin_at" class="col-sm-3 control-label">
                            {{ trans('admin/hardware/form.checkin_date') }}
                        </label>
                        <div class="col-md-8">
                            <div class="input-group col-md-5" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-autoclose="true">
                                <input type="text" class="form-control" placeholder="{{ trans('general.select_date') }}"
                                       name="checkin_at" id="checkin_at" value="{{ old('checkin_at', date('Y-m-d')) }}">
                                <span class="input-group-addon"><x-icon type="calendar" /></span>
                            </div>
                            {!! $errors->first('checkin_at', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('note') ? 'error' : '' }}">
                        <label for="note" class="col-sm-3 control-label">{{ trans('general.notes') }}</label>
                        <div class="col-md-8">
                            <textarea class="col-md-6 form-control" id="note" name="note" @required($snipeSettings->require_checkinout_notes)>{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('download_form') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('general.form') }}</label>
                        <div class="col-md-8">
                            <label class="form-control">
                                <input type="checkbox" name="download_form" id="download_form" value="1" {{ old('download_form', true) ? 'checked' : '' }}>
                                <span>{{ trans('general.download_form') }} ({{ trans('admin/hardware/form.return_form') }})</span>
                            </label>
                        </div>
                    </div>

            </div>
            <div class="box-footer">
                <a class="btn btn-link" href="{{ URL::previous() }}">{{ trans('button.cancel') }}</a>
                <button type="submit" class="btn btn-success pull-right"><x-icon type="checkmark" /> {{ trans('general.checkin') }}</button>
            </div>
        </div>
        </form>
    </div>
</div>

@stop
