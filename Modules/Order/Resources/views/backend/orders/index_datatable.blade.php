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
        @if($roles=='super admin' || $roles=='supervisor')
        <div class="row mb-3 filterclass">
            <div class="col-sm-2">
                <?php
                $field_name = 'status';
                $field_lable = label_case($field_name);
                $field_placeholder = "-- Select an option --";
                $required = "required";
                $field_value = request()->get('with_status',2);
                $select_options = [
                    '*' => 'None',
                    '1' => 'Pending',
                    '2' => 'Active',
                    '3' => 'Deactive',
                    '4' => 'Hold'
                ];
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->select($field_name, $select_options,$field_value)->class('form-control select2')->attributes(["$required"]) }}
            </div>
            <div class="col-sm-2">
                <?php
                $field_name = 'assigned_user_id';
                $field_lable = __("Cleaner Name");
                $field_relation = "user";
                $field_placeholder = __("Select an user");
                $required = "";
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-cleaner')->attributes(["$required"]) }}
            </div>
            <div class="col-sm-2">
                <?php
                $field_name = 'package_id';
                $field_lable = __("Package Name");
                $field_relation = "package";
                $field_placeholder = __("Select an Package");
                $required = "";
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-package')->attributes(["$required"]) }}
            </div>
            <!-- <div class="col-sm-3">
                <?php
                $field_name = 'order_date';
                $field_lable = label_case($field_name);
                $field_placeholder = $field_lable;
                $required = "";
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->date($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
            </div> -->
            <div class="col-sm-4">
                <?php
                $clothexpired = request()->has('clothexpired') ? true : false;
                $field_name = 'renew_date';
                $field_lable = label_case($field_name);
                $field_placeholder = $field_lable;
                $required = "";
                $field_value = request()->has('expired') ? date('d-m-Y') : '';
                $field_value_end = !empty($field_value) ? \Carbon\Carbon::createFromFormat('d-m-Y', $field_value)->format('Y-m-d') : '';
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                <div class="input-group">
                    <input type="hidden" name="clothexpired" id="clothexpired" value="{{ $clothexpired }}">
                    {{ html()->date($field_name.'_start')->placeholder('Start '.$field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    <div class="input-group-append">
                        <span class="input-group-text">to</span>
                    </div>
                    {{ html()->date($field_name.'_end')->placeholder('End '.$field_placeholder)->class('form-control')->attributes(["$required"])->value($field_value_end) }}
                </div>
            </div>
            <div class="col-sm-2">
                <label></label>
                <a href="{{route('backend.orders.index')}}" class="btn btn-primary" style="margin-top:32px;">Clear Filter</a>
            </div>
        </div>
        @endif
        @if($roles=='super admin')
        <div class="row">
            <div class="col-sm-3">
                <label></label>
                <button class="btn btn-primary" id="send-renew-notification" style="margin-top:25px;"> Send Renew Notification</button>
            </div>
            <div class="col-sm-3">
                <label></label>
                <button class="btn btn-primary" id="send-hold-notification" style="margin-top:25px;"> Send Hold Notification</button>
            </div>
            <div class="col-sm-3">
                <label></label>
                <button class="btn btn-primary" id="send-cloth-notification" style="margin-top:25px;"> Send Cloth Renew Notification</button>
            </div>
            <div class="col-sm-3">
                <label></label>
                <input type="text" name="dynamic_template_name" id="dynamic_template_name" placeholder="Template Name" class="form-control dynamic_template_name">
                <button class="btn btn-primary" id="send-dynamic-notification" style="margin-top:25px;"> Send Dynamic SMS</button>
            </div>
        </div>
        @endif
        <div class="row mt-4">
            <div class="col">
                <table id="datatable" class="table table-bordered table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>
                                #
                            </th>
                            <th>
                                @lang("order::text.car_name")
                            </th>
                            <th>
                                @lang("order::text.car_number")
                            </th>
                            <th>
                                @lang("order::text.user_name")
                            </th>
                            <th>
                                @lang("order::text.mobile")
                            </th>
                            <th>
                                @lang("order::text.paid_amount")
                            </th>
                            <th>
                                @lang("order::text.status")
                            </th>
                            <th>
                                @lang("order::text.assigned_user")
                            </th>
                            <th>
                                @lang("Renew Date")
                            </th>
                            <th class="text-end">
                                @lang("order::text.action")
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
    $('#select-all').on('change', function() {
        $('input[type="checkbox"]').prop('checked', this.checked);
    });
    $(document).ready(function() {
        $('#send-renew-notification').on('click', function() {
            const selectedIds = [];
            table.$('input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });

            if (selectedIds.length > 0) {
                $.ajax({
                    url: '{{ route("backend.orders.send.renew.notification") }}',
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert(response.message);
                    }
                });
            } else {
                alert('No rows selected.');
            }
        });
        
        $('#send-cloth-notification').on('click', function() {
            const selectedIds = [];
            table.$('input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });

            if (selectedIds.length > 0) {
                $.ajax({
                    url: '{{ route("backend.orders.send.renew.notification") }}',
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        cloth_notification: true,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert(response.message);
                    }
                });
            } else {
                alert('No rows selected.');
            }
        });
        
        $('#send-hold-notification').on('click', function() {
            const selectedIds = [];
            table.$('input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });

            if (selectedIds.length > 0) {
                $.ajax({
                    url: '{{ route("backend.orders.send.renew.notification") }}',
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        hold_notification: true,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert(response.message);
                    }
                });
            } else {
                alert('No rows selected.');
            }
        });
        $('#send-dynamic-notification').on('click', function() {
            const selectedIds = [];
            table.$('input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });

            if (selectedIds.length > 0) {
                $.ajax({
                    url: '{{ route("backend.orders.send.renew.notification") }}',
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        dynamic_notification: true,
                        dynamic_template_name: $('#dynamic_template_name').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        alert(response.message);
                    }
                });
            } else {
                alert('No rows selected.');
            }
        });


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
                url: '{{ route("backend.users.index_list", ["user_type" => "cleaner"]) }}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    var results = [{
                        id: '*',
                        text: 'None'
                    }, {
                        id: 'null',
                        text: 'Not Assigned'
                    }].concat(data);

                    return {
                        results: results
                    };
                },
                cache: true
            }
        });
        $('.select2-package').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.packages.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    var results = [{
                        id: '*',
                        text: 'None'
                    }].concat(data);

                    return {
                        results: results
                    };
                },
                cache: true
            }
        });
        var table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: true,
            responsive: true,
            pageLength: 50,
            ajax: {
                url: '{{ route("backend.$module_name.index_data") }}',
                data: function(d) {
                    d.filter_status = $('#status option:selected').val();
                    d.filter_package_id = $('#package_id option:selected').val();
                    d.filter_assigned_user_id = $('#assigned_user_id option:selected').val();
                    d.filter_order_date = $('#order_date').val();
                    d.filter_renew_date_start = $('#renew_date_start').val();
                    d.filter_renew_date_end = $('#renew_date_end').val();
                    d.filter_clothexpired = $('#clothexpired').val();
                    // if ($('#renew_date_end').val() == '') {
                    //     d.filter_renew_date_end = $('#renew_date_start').val();
                    //     $('#renew_date_end').val($('#renew_date_start').val());
                    // }
                },
                dataSrc: 'data',
            },
            columns: [
                { 
                    data: 'checkbox', 
                    orderable: false, 
                    searchable: false 
                },
                {
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'car_name',
                    name: 'car_name',
                    sortable: false,
                    searchable: false
                },
                {
                    data: 'car_number',
                    name: 'car_number'
                },
                {
                    data: 'user_name',
                    name: 'user_name',
                    sortable: false,
                    searchable: false
                },
                {
                    data: 'mobile',
                    name: 'users.mobile',
                    sortable: false,
                //    searchable: false
                },
                {
                    data: 'paid_amount',
                    name: 'paid_amount'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'assigned_user',
                    name: 'assigned_user',
                    sortable: false,
                    searchable: false
                },
                {
                    data: 'renew_date',
                    name: 'renew_date'
                },
                {
                    data: 'action',
                    name: 'action',
                    searchable: false
                }
            ],
            columnDefs: [
                { targets: 0, orderable: false } // Ensure checkbox column is non-sortable
            ],
            order: [[1, 'desc']]
        });
    });
</script>
@endpush