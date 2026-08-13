@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item type="active" icon='{{ $module_icon }}'>{{ __($module_title) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection
@php
$user = auth()->user();
$roles = !empty($user)?$user->roles()->pluck('name')[0]:'';
@endphp
@section('content')
<div class="card">
    <div class="card-body">

        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> {{ __($module_title) }} <small class="text-muted">{{ __($module_action) }}</small>

            <x-slot name="subtitle">
                @lang(":module_name Management Dashboard", ['module_name'=>Str::title($module_name)])
            </x-slot>
            <x-slot name="toolbar">
                @can('add_'.$module_name)
                <x-buttons.create route='{{ route("backend.$module_name.create") }}' title="{{__('Create')}} {{ ucwords(Str::singular($module_name)) }}" />
                @endcan

                @can('restore_'.$module_name)
                <div class="btn-group">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-coreui-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href='{{ route("backend.$module_name.trashed") }}'>
                                <i class="fas fa-eye-slash"></i> @lang("View trash")
                            </a>
                        </li>
                        <!-- <li>
                            <hr class="dropdown-divider">
                        </li> -->   
                    </ul>
                </div>
                @endcan
            </x-slot>
        </x-backend.section-header>
        @if($roles=='super admin')
        <div class="row mb-3 filterclass">
            <div class="col-sm-4">
                <?php
                $field_name = 'status';
                $field_lable = label_case($field_name);
                $field_placeholder = "-- Select an option --";
                $required = "required";
                $select_options = [
                    '*' => 'None',
                    'Picked Up' => 'Picked Up',
                    'Delivered' => 'Delivered',
                ];
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->select($field_name, $select_options)->class('form-control select2')->attributes(["$required"]) }}
            </div>
            <div class="col-sm-4">
                <?php
                $field_name = 'created_by';
                $field_lable = __("Cleaner Name");
                $field_relation = "user";
                $field_placeholder = __("Select an Cleaner");
                $required = "";
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-cleaner')->attributes(["$required"]) }}
            </div>
            <div class="col-sm-4">
                <?php
                $field_name = 'date';
                $field_lable = label_case($field_name);
                $field_placeholder = $field_lable;
                $required = "";
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->date($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
            </div>
            <div class="col-sm-4">
                <br/>
                <?php
                $field_name = 'cloth_count';
                $field_lable = label_case($field_name);
                $field_placeholder = $field_lable;
                $required = "";
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
            </div>
            <div class="col-sm-4"><br/>
                <a href="{{route('backend.reports.clothReport')}}" style="margin-top:30px;" class="btn btn-primary" style="margin-top:20px;">Clear Filter</a>
            </div>
        </div>
        @endif
        <div class="row mt-4">
            <div class="col">
                <table id="datatable" class="table table-bordered table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>
                                #
                            </th>
                            <th>
                                @lang("Car Number")
                            </th>
                            <th>
                                @lang("Cleaner Name")
                            </th>
                            <th>
                                @lang("Status")
                            </th>
                            <th>
                                @lang("Cloth Count")
                            </th>
                            <th>
                                @lang("Date")
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-7">
                <div class="float-left">

                </div>
            </div>
            <div class="col-5">
                <div class="float-end">

                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push ('after-styles')
<!-- DataTables Core and Extensions -->
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush
<x-library.select2 />
@push ('after-scripts')
<!-- DataTables Core and Extensions -->
<script type="module" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script type="module">
    $(document).ready(function() {
        $(document).on('change', '.filterclass select,.filterclass input', function() {
            if ($.fn.DataTable.isDataTable('#datatable')) {
                table.draw(); // Redraw the table when the input changes
            }
        });
        $(document).on('select2:open', () => {
            document.querySelector('.select2-search__field').focus();
            document.querySelector('.select2-container--open .select2-search__field').focus();
        });
        $('.select2-cleaner').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.users.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term),
                        user_type:'cleaner',
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
    });
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: true,
        responsive: true,
        pageLength: 25,
        ajax: {
            url: '{{ route("backend.$module_name.clothReportData") }}',
            data: function(d) {
                d.filter_status = $('#status option:selected').val();
                d.created_by = $('#created_by option:selected').val();
                d.filter_date = $('#date').val();
                d.filter_cloth_count = $('#cloth_count').val();
            },
            dataSrc: 'data',
        },
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'car_number',
                name: 'car_number'
            },
            {
                data: 'cleaner_name',
                name: 'cleaner_name'
            },
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'cloth_count',
                name: 'cloth_count',
            },
            {
                data: 'date',
                name: 'date',
            }
        ]
    });
</script>
@endpush