@php
    $selectedDepreciationMethod = old('depreciation_method', $item->depreciation_method);
@endphp

<!-- Asset depreciation -->
<div class="form-group {{ $errors->has('depreciation_method') ? ' has-error' : '' }}">
    <label for="depreciation_method" class="col-md-3 control-label">{{ trans('admin/hardware/form.depreciation') }}</label>
    <div class="col-md-7">
        <x-input.select
            name="depreciation_method"
            id="depreciation_method"
            :options="[
                '' => trans('admin/hardware/form.do_not_depreciate'),
                'straight_line' => trans('admin/hardware/form.depreciation_straight_line'),
                'reducing_balance' => trans('admin/hardware/form.depreciation_reducing_balance'),
            ]"
            :selected="$selectedDepreciationMethod"
            style="width:350px;"
            aria-label="depreciation_method"
        />
        {!! $errors->first('depreciation_method', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div id="depreciation_months_group" class="form-group {{ $errors->has('depreciation_months') ? ' has-error' : '' }}" @if($selectedDepreciationMethod !== 'straight_line') style="display:none;" @endif>
    <label for="depreciation_months" class="col-md-3 control-label">{{ trans('admin/hardware/form.depreciation_months') }}</label>
    <div class="col-md-3">
        <input
            class="form-control"
            type="number"
            min="1"
            max="3600"
            name="depreciation_months"
            id="depreciation_months"
            value="{{ old('depreciation_months', $item->depreciation_months) }}"
        />
        {!! $errors->first('depreciation_months', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div id="depreciation_percentage_group" class="form-group {{ $errors->has('depreciation_percentage') ? ' has-error' : '' }}" @if($selectedDepreciationMethod !== 'reducing_balance') style="display:none;" @endif>
    <label for="depreciation_percentage" class="col-md-3 control-label">{{ trans('admin/hardware/form.depreciation_percentage') }}</label>
    <div class="col-md-3">
        <div class="input-group">
            <input
                class="form-control"
                type="number"
                min="0.01"
                max="100"
                step="0.01"
                name="depreciation_percentage"
                id="depreciation_percentage"
                value="{{ old('depreciation_percentage', $item->depreciation_percentage) }}"
            />
            <span class="input-group-addon">%</span>
        </div>
        <p class="help-block">{{ trans('admin/hardware/form.depreciation_percentage_help') }}</p>
        {!! $errors->first('depreciation_percentage', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>
