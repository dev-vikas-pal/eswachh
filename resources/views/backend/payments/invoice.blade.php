@extends('backend.layouts.app')

@section('title') @lang('Invoice') @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item type="active" icon="fa-solid fa-receipt">@lang('Invoice')</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
<div class="card invoice-sheet">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 class="mb-1">Eswachh Integrated Solutions Private Limited</h4>
                <div class="text-muted">@lang('Payment receipt')</div>
            </div>
            <div class="text-end">
                <div class="fs-5 fw-semibold">@lang('Invoice') #{{ $payment->id }}</div>
                <div class="text-muted">
                    {{ \Carbon\Carbon::parse($payment->payment_date_time)->format('d-m-Y H:i') }}
                </div>
                @php
                    $isPaid = $payment->payment_status === \App\Services\RazorpayService::STATUS_CAPTURED;
                @endphp
                <span class="badge {{ $isPaid ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $statusLabels[$payment->payment_status] ?? ucfirst($payment->payment_status) }}
                </span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <div class="text-muted text-uppercase small">@lang('Billed to')</div>
                <div class="fw-semibold">{{ $payment->customer_name }}</div>
                <div>{{ $payment->customer_email }}</div>
                <div>{{ $payment->customer_mobile }}</div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="text-muted text-uppercase small">@lang('Service')</div>
                <div class="fw-semibold">{{ $payment->car_number ?? '-' }}</div>
                <div>{{ $payment->sector_name ?? 'NA' }}</div>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>@lang('Description')</th>
                    <th>@lang('Period')</th>
                    <th class="text-end">@lang('Amount')</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $payment->payment_for }}</strong>
                        @if($payment->package_name)
                        <div class="text-muted">{{ $payment->package_name }} @if($payment->duration_name) &mdash; {{ $payment->duration_name }} @endif</div>
                        @endif
                    </td>
                    <td>
                        @if($payment->start_date && $payment->renew_date)
                            {{ \Carbon\Carbon::parse($payment->start_date)->format('d-m-Y') }}
                            &ndash;
                            {{ \Carbon\Carbon::parse($payment->renew_date)->format('d-m-Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-end">&#8377;{{ number_format($payment->payment_amount, 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">@lang('Total paid')</th>
                    <th class="text-end">&#8377;{{ number_format($payment->payment_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <div class="row text-muted small">
            <div class="col-sm-4">
                <div class="text-uppercase">@lang('Payment method')</div>
                <div>{{ strtoupper($payment->payment_method ?: '-') }}</div>
            </div>
            <div class="col-sm-4">
                <div class="text-uppercase">@lang('Gateway reference')</div>
                <div>{{ $payment->payment_id ?? '-' }}</div>
            </div>
            <div class="col-sm-4">
                <div class="text-uppercase">@lang('Transaction id')</div>
                <div>{{ $payment->transaction_id ?: '-' }}</div>
            </div>
        </div>

        <hr>

        <div class="d-print-none">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> @lang('Print')
            </button>
            <x-backend.buttons.return-back />
        </div>
    </div>
</div>
@endsection

@push ('after-styles')
<style>
    /* Print just the receipt, not the chrome around it. */
    @media print {
        .sidebar, .header, .footer, .breadcrumb, .d-print-none {
            display: none !important;
        }
        .invoice-sheet {
            border: 0;
            box-shadow: none;
        }
        body, .wrapper, .body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>
@endpush
