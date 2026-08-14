@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item type="active" icon='{{ $module_icon }}'>{{ __($module_title) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
@if($isCleaner)
<div class="card mb-4">
    <div class="card-body">
        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> @lang('Today') <small class="text-muted">{{ $today->format('d-m-Y') }}</small>

            <x-slot name="subtitle">
                @lang('How many of your cars did you service today?')
            </x-slot>
        </x-backend.section-header>

        @if($totalCars === 0)
        <div class="alert alert-info mb-0">
            @lang('You have no active cars assigned, so there is nothing to report today.')
        </div>
        @else
        @if($todaysEntry)
        <div class="alert alert-success">
            @lang('Already recorded today'):
            <strong>{{ $todaysEntry->cars_serviced }} / {{ $todaysEntry->total_cars }}</strong>
            @lang('cars') &mdash;
            <span class="badge {{ $todaysEntry->status === \App\Models\CleanerAttendance::STATUS_PRESENT ? 'bg-success' : 'bg-danger' }}">
                {{ ucfirst($todaysEntry->status) }}
            </span>
            <div class="small text-muted mt-1">@lang('Submitting again will correct today\'s entry.')</div>
        </div>
        @endif

        <form method="POST" action="{{ route('backend.attendances.store') }}" class="row">
            @csrf
            <div class="col-sm-3 mb-3">
                <label class="form-label" for="cars_serviced">@lang('Cars serviced')</label>
                <select name="cars_serviced" id="cars_serviced" class="form-control" required>
                    @for($count = 0; $count <= $totalCars; $count++)
                    <option value="{{ $count }}"
                        @if($todaysEntry && (int) $todaysEntry->cars_serviced === $count) selected @endif>
                        {{ $count }}@if($count === 0) &mdash; @lang('none') @endif
                    </option>
                    @endfor
                </select>
                <small class="form-text text-muted">
                    @lang('You have :count active car(s).', ['count' => $totalCars])
                </small>
            </div>
            <div class="col-sm-5 mb-3">
                <label class="form-label" for="note">@lang('Note (optional)')</label>
                <input type="text" name="note" id="note" class="form-control" maxlength="255"
                       value="{{ $todaysEntry->note ?? '' }}">
            </div>
            <div class="col-sm-3 align-self-end mb-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> @lang('Submit Attendance')
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
@endif

<div class="card">
    <div class="card-body">
        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> {{ __($module_title) }} <small class="text-muted">@lang('History')</small>
        </x-backend.section-header>

        <div class="row mb-3 filterclass">
            <div class="col-sm-2">
                <label class="form-label" for="filter_date">@lang('Date')</label>
                <input type="date" name="filter_date" id="filter_date" class="form-control">
            </div>
            <div class="col-sm-2">
                <label class="form-label" for="filter_status">@lang('Status')</label>
                <select name="filter_status" id="filter_status" class="form-control select2">
                    <option value="*">@lang('All')</option>
                    <option value="present">@lang('Present')</option>
                    <option value="absent">@lang('Absent')</option>
                </select>
            </div>
            @unless($isCleaner)
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
            @endunless
        </div>

        <div class="row mt-2">
            <div class="col">
                <table id="datatable" class="table table-bordered table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            @unless($isCleaner)<th>@lang('Cleaner')</th>@endunless
                            @unless($isCleaner)<th>@lang('Sector')</th>@endunless
                            <th>@lang('Serviced')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Note')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
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
            { data: 'date', name: 'cleaner_attendances.date' },
            @unless($isCleaner)
            { data: 'cleaner_name', name: 'users.name' },
            { data: 'sector_name', name: 'sectors.name' },
            @endunless
            { data: 'serviced', name: 'cleaner_attendances.cars_serviced' },
            { data: 'status', name: 'cleaner_attendances.status' },
            { data: 'note', name: 'cleaner_attendances.note' }
        ];

        var table = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            ajax: {
                url: '{{ route("backend.attendances.index_data") }}',
                data: function(d) {
                    d.filter_date = $('#filter_date').val();
                    d.filter_status = $('#filter_status').val();
                    d.filter_sector_id = $('#filter_sector_id').val();
                },
                dataSrc: 'data'
            },
            columns: columns,
            order: []
        });

        $(document).on('change', '.filterclass select, .filterclass input', function() {
            table.draw();
        });
    });
</script>
@endpush
