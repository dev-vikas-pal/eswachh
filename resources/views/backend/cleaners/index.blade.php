@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item type="active" icon='{{ $module_icon }}'>{{ __($module_title) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection
<?php
    $user = auth()->user();
    $roles = !empty($user)?$user->roles()->pluck('name')[0]:'';
    $isFranchiseOwner = !empty($user) && \App\Services\SectorService::isFranchiseOwner($user);
?>
@section('content')
<link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
<div class="card">
    <div class="card-body">
        <form action='{{ route("backend.$module_name.store") }}' method="POST" class="form" id="cleanerForm" enctype="multipart/form-data">
            @csrf
            <!-- <x-backend.section-header>
                <i class="{{ $module_icon }}"></i> {{ __($module_title) }} <small class="text-muted">{{ __($module_action) }}</small>

                <x-slot name="subtitle">
                    @lang(":module_name Management Dashboard", ['module_name'=>Str::title($module_name)])
                </x-slot>
                <x-slot name="toolbar">
                    <button class="btn btn-success" type="submit"> <i class="fas fa-save"></i> Submit </button>
                </x-slot>
            </x-backend.section-header> -->
            <div class="row mt-4">
                <div class="col">
                    @if($roles=='super admin' || $isFranchiseOwner)
                    <div class="row mb-3 filterclass">
                        <div class="col-sm-4">
                            <label for="filter_sector_id" class="form-label">{{ __('Sector') }}</label>
                            <select class="form-control select2" id="filter_sector_id" name="filter_sector_id">
                                @if($canSeeAllSectors || count($sectorOptions) > 1)
                                <option value="*">{{ $canSeeAllSectors ? __('All Sectors') : __('All My Sectors') }}</option>
                                @endif
                                @foreach($sectorOptions as $sector_id => $sector_name)
                                <option value="{{ $sector_id }}" @if((string) $filter['filter_sector_id'] === (string) $sector_id) selected @endif>{{ $sector_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label for="filter_assigned_user_id" class="form-label">Cleaner Name</label>
                            <select class="form-control select2" id="filter_assigned_user_id" name="filter_assigned_user_id">
                                <option value="*">--All--</option>
                                @foreach($cleanerDatas as $cleaner)
                                    @if($cleaner->id==$filter['filter_assigned_user_id'])
                                        <option value="{{ $cleaner->id }}" selected>{{ $cleaner->name }}</option>
                                    @else
                                        <option value="{{ $cleaner->id }}">{{ $cleaner->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label for="filter_date" class="form-label">Cleaner Name</label>
                            <input type="date" name="filter_date" id="filter_date" value="{{$filter['filter_date']}}" class="form-control">
                        </div>
                    </div>
                    @endif
                    <table class="table table-hover table-responsive-sm">
                        <thead>
                            <tr>
                                <th>{{ __("Customer Name") }}</th>
                                <th>{{ __("Customer Mobile No") }}</th>
                                <th>{{ __("Car Number") }}</th>
                                <th>{{ __("Customer Address") }}</th>
                                <th>{{ __("Internal Type") }}</th>
                                <th>{{ __("Time") }}</th>
                                <th>{{ __("Status") }}</th>
                                <th class="text-end">{{ __("labels.backend.action") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($$module_name as $module_name_singular)
                            <tr>
                                <td>
                                    {{ $module_name_singular->user_name }}
                                </td>
                                <td>
                                    {{ $module_name_singular->user_mobile }}
                                </td>
                                <td>
                                    {{ $module_name_singular->car_number }}
                                </td>
                                <td>
                                    {{ $module_name_singular->house_no }}
                                </td>
                                <td>
                                    {{ $module_name_singular->internaltype_name }}
                                </td>
                                <td>
                                    {{ $module_name_singular->office_time }}
                                </td>
                                <td>
                                    @php
                                    $select_options = [
                                        '1' => 'Pending',
                                        '2' => 'Active',
                                        '3' => 'Deactive',
                                        '4' => 'Hold'
                                    ];
                                    @endphp
                                    {{ $select_options[$module_name_singular->order_status] }}
                                </td>
                                <td class="text-end">
                                    <input type="hidden" name="post_data[{{$module_name_singular->id}}][id]" value="{{$module_name_singular->id}}">
                                    @if(!empty($module_name_singular->status))
                                    <input type="checkbox" name="post_data[{{$module_name_singular->id}}][status]" value="1" checked data-toggle="toggle" data-on="Car Washed" data-off="Discussed" data-onstyle="success" data-offstyle="danger" onchange="addCleanerActivity()">
                                    @else
                                    <input type="checkbox" name="post_data[{{$module_name_singular->id}}][status]" value="1" data-toggle="toggle" data-on="Car Washed" data-off="Discussed" data-onstyle="success" data-offstyle="danger" onchange="addCleanerActivity()">
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-7">
                <div class="float-left">
                    {!! $$module_name->total() !!} {{ __('labels.backend.total') }}
                </div>
            </div>
            <div class="col-5">
                <div class="float-end">
                    {!! $$module_name->render() !!}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
<x-library.select2 />
@push ("after-scripts")
<script>
    $('#filter_assigned_user_id,#filter_date,#filter_sector_id').change(function() {
        var url = '{{ route("backend.cleaners.index") }}?';
        var filter_sector_id = $('#filter_sector_id').val();
        if (filter_sector_id && filter_sector_id != '*') {
            url += 'filter_sector_id=' + filter_sector_id + '&';
        }
        var filter_assigned_user_id = $('#filter_assigned_user_id').val();
        if (filter_assigned_user_id!='*') {
            url += 'filter_assigned_user_id=' + filter_assigned_user_id + '&';
        }
        var filter_date = $('#filter_date').val();
        if (filter_date) {
            url += 'filter_date=' + filter_date;
        }
        url = url.replace(/&$/, '');
        window.location.href = url;
    });
    function addCleanerActivity() {
        var formData = new FormData(document.getElementById('cleanerForm'));
        $.ajax({
            url: '{{route("backend.cleaners.store")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {

            },
            error: function(data) {

            }
        });
    }
</script>
@endpush
