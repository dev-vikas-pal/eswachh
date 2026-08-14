@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item route='{{ route("backend.payments.index") }}' icon='{{ $module_icon }}'>
        @lang('Payments')
    </x-backend-breadcrumb-item>
    <x-backend-breadcrumb-item type="active">{{ __($module_action) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@php
    // Only completed payments are counted here, so these totals are money
    // actually taken rather than money attempted.
    $periodTotal = function ($rows) {
        return $rows->sum('total');
    };
@endphp

@section('content')
<div class="card">
    <div class="card-body">
        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> {{ __($module_title) }}

            <x-slot name="subtitle">
                @lang('Completed payments per franchise sector')
            </x-slot>
        </x-backend.section-header>

        <form method="GET" action="{{ route('backend.payments.reports') }}" class="row mb-4">
            <div class="col-sm-3">
                <label class="form-label" for="filter_sector_id">@lang('Sector')</label>
                <select name="filter_sector_id" id="filter_sector_id" class="form-control" onchange="this.form.submit()">
                    @if($canSeeAllSectors || count($sectorOptions) > 1)
                    <option value="*">{{ $canSeeAllSectors ? __('All Sectors') : __('All My Sectors') }}</option>
                    @endif
                    @foreach($sectorOptions as $sector_id => $sector_name)
                    <option value="{{ $sector_id }}" @if((string) $selectedSector === (string) $sector_id) selected @endif>{{ $sector_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label" for="days">@lang('Daily: last')</label>
                <select name="days" id="days" class="form-control" onchange="this.form.submit()">
                    @foreach([7, 30, 90] as $option)
                    <option value="{{ $option }}" @if($days === $option) selected @endif>{{ $option }} @lang('days')</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label" for="months">@lang('Monthly: last')</label>
                <select name="months" id="months" class="form-control" onchange="this.form.submit()">
                    @foreach([6, 12, 24] as $option)
                    <option value="{{ $option }}" @if($months === $option) selected @endif>{{ $option }} @lang('months')</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="row">
            <div class="col-lg-6">
                <h5>@lang('Daily')</h5>
                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>@lang('Date')</th>
                                <th>@lang('Sector')</th>
                                <th class="text-end">@lang('Payments')</th>
                                <th class="text-end">@lang('Total')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daily as $period => $rows)
                                @foreach($rows as $row)
                                <tr>
                                    @if($loop->first)
                                    <td rowspan="{{ $rows->count() }}" class="align-middle fw-semibold text-nowrap">{{ $period }}</td>
                                    @endif
                                    <td>{{ $row->sector_name }}</td>
                                    <td class="text-end">{{ $row->payments }}</td>
                                    <td class="text-end">&#8377;{{ number_format($row->total, 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-semibold">@lang('Day total')</td>
                                    <td class="text-end fw-semibold">&#8377;{{ number_format($periodTotal($rows), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">@lang('No payments in this period.')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <h5>@lang('Monthly')</h5>
                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>@lang('Month')</th>
                                <th>@lang('Sector')</th>
                                <th class="text-end">@lang('Payments')</th>
                                <th class="text-end">@lang('Total')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthly as $period => $rows)
                                @foreach($rows as $row)
                                <tr>
                                    @if($loop->first)
                                    <td rowspan="{{ $rows->count() }}" class="align-middle fw-semibold text-nowrap">{{ $period }}</td>
                                    @endif
                                    <td>{{ $row->sector_name }}</td>
                                    <td class="text-end">{{ $row->payments }}</td>
                                    <td class="text-end">&#8377;{{ number_format($row->total, 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-semibold">@lang('Month total')</td>
                                    <td class="text-end fw-semibold">&#8377;{{ number_format($periodTotal($rows), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">@lang('No payments in this period.')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
