@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item route='{{ route("backend.complaints.index") }}' icon='{{ $module_icon }}'>
        {{ __($module_title) }}
    </x-backend-breadcrumb-item>
    <x-backend-breadcrumb-item type="active">#{{ $complaint->id }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> @lang('Complaint') #{{ $complaint->id }}

            <x-slot name="subtitle">
                {{ optional($complaint->order)->car_number }}
            </x-slot>
            <x-slot name="toolbar">
                <x-backend.buttons.return-back />
            </x-slot>
        </x-backend.section-header>

        <hr>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">@lang('What the customer told us')</div>
                    <div class="card-body">
                        <p class="mb-0">{!! nl2br(e($complaint->message)) !!}</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">@lang('History')</div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <i class="fas fa-circle-dot text-warning"></i>
                                <strong>@lang('Raised')</strong>
                                by {{ optional($complaint->customer)->name }}
                                on {{ $complaint->created_at->format('d-m-Y H:i') }}
                            </li>

                            @if($complaint->status === \App\Models\Complaint::STATUS_CLOSED)
                            <li>
                                <i class="fas fa-circle-check text-success"></i>
                                <strong>@lang('Closed')</strong>
                                {{ $resolutionLabels[$complaint->resolution] ?? $complaint->resolution }}
                                @if($complaint->closed_at)
                                on {{ $complaint->closed_at->format('d-m-Y H:i') }}
                                @endif
                                @if($complaint->resolution_note)
                                <div class="text-muted mt-1">{{ $complaint->resolution_note }}</div>
                                @endif
                            </li>
                            @else
                            <li class="text-muted">
                                <i class="far fa-circle"></i> @lang('Still open')
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">@lang('Details')</div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt>@lang('Status')</dt>
                            <dd>
                                <span class="badge {{ $complaint->isOpen() ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ $statusLabels[$complaint->status] ?? $complaint->status }}
                                </span>
                            </dd>

                            <dt>@lang('Car')</dt>
                            <dd>{{ optional($complaint->order)->car_number ?? '-' }}</dd>

                            <dt>@lang('Customer')</dt>
                            <dd>{{ optional($complaint->customer)->name ?? '-' }}</dd>

                            <dt>@lang('Cleaner')</dt>
                            <dd>{{ optional($complaint->cleaner)->name ?? __('Not assigned') }}</dd>

                            <dt>@lang('Sector')</dt>
                            <dd>{{ optional($complaint->sector)->name ?? 'NA' }}</dd>
                        </dl>
                    </div>
                </div>

                @if($canResolve)
                <div class="card mt-3">
                    <div class="card-header">@lang('Close this complaint')</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="resolution">@lang('Did you speak to the customer?')</label>
                            <select id="resolution" class="form-control">
                                @foreach($resolutionLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="resolution_note">@lang('Note (optional)')</label>
                            <textarea id="resolution_note" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" id="resolve-complaint">
                            <i class="fas fa-check"></i> @lang('Close complaint')
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push ('after-scripts')
@if($canResolve)
<script type="module">
    $('#resolve-complaint').on('click', function() {
        const $button = $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("backend.complaints.resolve", $complaint->id) }}',
            method: 'PATCH',
            data: {
                resolution: $('#resolution').val(),
                resolution_note: $('#resolution_note').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert(response.message);
                window.location.reload();
            },
            error: function(xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'The complaint could not be closed.');
                $button.prop('disabled', false);
            }
        });
    });
</script>
@endif
@endpush
