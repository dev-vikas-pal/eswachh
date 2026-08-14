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

            <x-slot name="subtitle">
                @lang('Customer complaints and how they were dealt with')
            </x-slot>
            <x-slot name="toolbar">
                @if($canRaise)
                <a href="{{ route('backend.complaints.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> @lang('Raise a Complaint')
                </a>
                @endif
            </x-slot>
        </x-backend.section-header>

        <div class="row mb-3 filterclass">
            <div class="col-sm-2">
                <label class="form-label" for="filter_status">@lang('Status')</label>
                <select name="filter_status" id="filter_status" class="form-control select2">
                    <option value="*">@lang('All')</option>
                    @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($showSectorFilter)
            <div class="col-sm-2">
                <label class="form-label" for="filter_sector_id">@lang('Sector')</label>
                <select name="filter_sector_id" id="filter_sector_id" class="form-control select2">
                    @if($canSeeAllSectors || count($sectorOptions) > 1)
                    <option value="*">{{ $canSeeAllSectors ? __('All Sectors') : __('All My Sectors') }}</option>
                    @endif
                    @foreach($sectorOptions as $sector_id => $sector_name)
                    <option value="{{ $sector_id }}">{{ $sector_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <div class="row mt-2">
            <div class="col">
                <table id="datatable" class="table table-bordered table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>@lang('Raised')</th>
                            <th>@lang('Car')</th>
                            <th>@lang('Customer')</th>
                            @if($showSectorFilter)<th>@lang('Sector')</th>@endif
                            <th>@lang('Cleaner')</th>
                            <th>@lang('Complaint')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Outcome')</th>
                            <th class="text-end">@lang('Action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@if($canResolve)
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Close complaint')</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="close_complaint_id">
                <div class="mb-3">
                    <label class="form-label" for="close_resolution">@lang('Did you speak to the customer?')</label>
                    <select id="close_resolution" class="form-control">
                        @foreach($resolutionLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="close_note">@lang('Note (optional)')</label>
                    <textarea id="close_note" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">@lang('Cancel')</button>
                <button type="button" class="btn btn-primary" id="close-save">@lang('Close complaint')</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push ('after-styles')
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

<x-library.select2 />

@push ('after-scripts')
<script type="module" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script type="module">
    $(document).ready(function() {
        var columns = [
            { data: 'created_at', name: 'complaints.created_at' },
            { data: 'car_number', name: 'orders.car_number' },
            { data: 'customer_name', name: 'customers.name' },
            @if($showSectorFilter)
            { data: 'sector_name', name: 'sectors.name' },
            @endif
            { data: 'cleaner_name', name: 'cleaners.name' },
            { data: 'message', name: 'complaints.message' },
            { data: 'status', name: 'complaints.status' },
            { data: 'resolution_label', name: 'complaints.resolution' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ];

        var table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            ajax: {
                url: '{{ route("backend.complaints.index_data") }}',
                data: function(d) {
                    d.filter_status = $('#filter_status').val();
                    d.filter_sector_id = $('#filter_sector_id').val();
                },
                dataSrc: 'data'
            },
            columns: columns,
            order: [[0, 'desc']]
        });

        $(document).on('change', '.filterclass select', function() {
            table.draw();
        });

        @if($canResolve)
        $(document).on('click', '.close-complaint', function() {
            $('#close_complaint_id').val($(this).data('id'));
            $('#close_note').val('');
            new coreui.Modal(document.getElementById('closeModal')).show();
        });

        $('#close-save').on('click', function() {
            const id = $('#close_complaint_id').val();
            const $button = $(this).prop('disabled', true);

            $.ajax({
                url: '{{ url("admin/complaints") }}/' + id + '/resolve',
                method: 'PATCH',
                data: {
                    resolution: $('#close_resolution').val(),
                    resolution_note: $('#close_note').val(),
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert(response.message);
                    coreui.Modal.getInstance(document.getElementById('closeModal')).hide();
                    table.draw(false);
                },
                error: function(xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'The complaint could not be closed.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
        @endif
    });
</script>
@endpush
