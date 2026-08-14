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
                @lang('Every payment taken, with the sector it belongs to')
            </x-slot>
            <x-slot name="toolbar">
                <a href="{{ route('backend.payments.reports') }}" class="btn btn-secondary">
                    <i class="fas fa-chart-column"></i> @lang('Reports')
                </a>
            </x-slot>
        </x-backend.section-header>

        <div class="row mb-3 filterclass">
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
            <div class="col-sm-2">
                <label class="form-label" for="filter_status">@lang('Status')</label>
                <select name="filter_status" id="filter_status" class="form-control select2">
                    <option value="*">@lang('All')</option>
                    @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label" for="filter_payment_for">@lang('For')</label>
                <select name="filter_payment_for" id="filter_payment_for" class="form-control select2">
                    <option value="*">@lang('All')</option>
                    <option value="Subscription">@lang('Subscription')</option>
                    <option value="Cloth">@lang('Cloth')</option>
                </select>
            </div>
            <div class="col-sm-4">
                <label class="form-label" for="filter_date_start">@lang('Payment date')</label>
                <div class="input-group">
                    <input type="date" name="filter_date_start" id="filter_date_start" class="form-control">
                    <div class="input-group-append"><span class="input-group-text">to</span></div>
                    <input type="date" name="filter_date_end" id="filter_date_end" class="form-control">
                </div>
            </div>
            <div class="col-sm-1 d-grid align-self-end">
                <a href="{{ route('backend.payments.index') }}" class="btn btn-primary mb-1" title="{{ __('Clear Filter') }}">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </div>

        <div class="row mb-3" id="payment-summary"></div>

        <div class="row mt-2">
            <div class="col">
                <table id="datatable" class="table table-bordered table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Car')</th>
                            <th>@lang('Customer')</th>
                            <th>@lang('Sector')</th>
                            <th>@lang('For')</th>
                            <th>@lang('Method')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Gateway Id')</th>
                            <th class="text-end">@lang('Action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@if($canOverride)
<div class="modal fade" id="overrideModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Set payment status')</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    @lang('Use this once the payment has been confirmed against the bank. The change is recorded against your name.')
                </p>
                <input type="hidden" id="override_payment_id">
                <div class="mb-3">
                    <label class="form-label" for="override_status">@lang('Status')</label>
                    <select id="override_status" class="form-control">
                        @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="override_note">@lang('Note')</label>
                    <textarea id="override_note" class="form-control" rows="2" maxlength="500"
                              placeholder="@lang('e.g. confirmed against bank statement')"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">@lang('Cancel')</button>
                <button type="button" class="btn btn-primary" id="override-save">@lang('Save')</button>
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
        function currentFilters() {
            return {
                filter_sector_id: $('#filter_sector_id').val(),
                filter_status: $('#filter_status').val(),
                filter_payment_for: $('#filter_payment_for').val(),
                filter_date_start: $('#filter_date_start').val(),
                filter_date_end: $('#filter_date_end').val()
            };
        }

        function loadSummary() {
            $.get('{{ route("backend.payments.summary") }}', currentFilters(), function(response) {
                let html = '';
                response.summary.forEach(function(row) {
                    html += '<div class="col-sm-6 col-lg-3">' +
                        '<div class="card mb-2"><div class="card-body py-2">' +
                        '<div class="fs-5 fw-semibold">&#8377;' + row.total + '</div>' +
                        '<div class="text-muted">' + row.status + ' (' + row.count + ')</div>' +
                        '</div></div></div>';
                });
                $('#payment-summary').html(html);
            });
        }

        var table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            pageLength: 50,
            ajax: {
                url: '{{ route("backend.payments.index_data") }}',
                data: function(d) {
                    $.extend(d, currentFilters());
                },
                dataSrc: 'data'
            },
            columns: [
                { data: 'payment_date_time', name: 'payment_history.payment_date_time' },
                { data: 'car_number', name: 'orders.car_number' },
                { data: 'customer_name', name: 'users.name' },
                { data: 'sector_name', name: 'sectors.name' },
                { data: 'payment_for', name: 'payment_history.payment_for' },
                { data: 'payment_method', name: 'payment_history.payment_method' },
                { data: 'payment_amount', name: 'payment_history.payment_amount' },
                { data: 'payment_status', name: 'payment_history.payment_status' },
                { data: 'payment_id', name: 'payment_history.payment_id' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });

        $(document).on('change', '.filterclass select, .filterclass input', function() {
            table.draw();
            loadSummary();
        });

        loadSummary();

        @if($canOverride)
        $(document).on('click', '.override-status', function() {
            $('#override_payment_id').val($(this).data('id'));
            $('#override_status').val($(this).data('status'));
            $('#override_note').val('');
            new coreui.Modal(document.getElementById('overrideModal')).show();
        });

        $('#override-save').on('click', function() {
            const id = $('#override_payment_id').val();
            const $button = $(this).prop('disabled', true);

            $.ajax({
                url: '{{ url("admin/payments") }}/' + id + '/status',
                method: 'PATCH',
                data: {
                    payment_status: $('#override_status').val(),
                    note: $('#override_note').val(),
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert(response.message);
                    coreui.Modal.getInstance(document.getElementById('overrideModal')).hide();
                    table.draw(false);
                    loadSummary();
                },
                error: function(xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'The status could not be updated.');
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
