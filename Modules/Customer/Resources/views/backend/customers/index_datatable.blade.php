@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item type="active" icon='{{ $module_icon }}'>{{ __($module_title) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
<div class="card">
    <div class="card-body">

        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> {{ __($module_title) }} <small class="text-muted">{{ __($module_action) }}</small>
            <?php
            $module_name = 'users';
            ?>
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
        <div class="row mb-3 filterclass">
            <div class="col-sm-4">
                <?php
                $field_name = 'user_type';
                $field_lable = label_case($field_name);
                $field_placeholder = "-- Select an option --";
                $required = "required";
                $select_options = [
                    '*' => 'ALL',
                    'customer' => 'Customer',
                    'cleaner' => 'Cleaner',
                    'super admin' => 'Admin',
                ];
                ?>
                {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                {{ html()->select($field_name, $select_options)->class('form-control select2')->attributes(["$required"]) }}
            </div>
        </div>
        <div class="row mt-4">
            <div class="col">
                <table id="datatable" class="table table-bordered table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>
                                #
                            </th>
                            <th>
                                @lang("customer::text.name")
                            </th>
                            <th>
                                @lang("customer::text.email")
                            </th>
                            <th>
                                @lang("customer::text.address")
                            </th>
                            <th>
                                @lang("customer::text.status")
                            </th>
                            <th>
                                @lang("customer::text.user_roles")
                            </th>
                            <th class="text-end">
                                @lang("customer::text.action")
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
<?php
$module_name = 'customers';
?>
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
                table.draw();
            }
        });
        $(document).on('select2:open', () => {
            document.querySelector('.select2-search__field').focus();
            document.querySelector('.select2-container--open .select2-search__field').focus();
        });
    });
    var table = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: true,
        responsive: true,
        pageLength: 25,
        ajax: {
            url: '{{ route("backend.$module_name.index_data") }}',
            data: function(d) {
                d.user_type = $('#user_type option:selected').val();
            },
            dataSrc: 'data',
        },
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'email',
                name: 'email'
            },
            {
                data: 'address',
                name: 'address',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'user_roles',
                name: 'user_roles'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ]
    });
</script>
@endpush