@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item route='{{ route("backend.complaints.index") }}' icon='{{ $module_icon }}'>
        {{ __($module_title) }}
    </x-backend-breadcrumb-item>
    <x-backend-breadcrumb-item type="active">{{ __($module_action) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> @lang('Raise a Complaint')

            <x-slot name="subtitle">
                @lang('Tell us what went wrong and we will look into it')
            </x-slot>
            <x-slot name="toolbar">
                <x-backend.buttons.return-back />
            </x-slot>
        </x-backend.section-header>

        <hr>

        @if($orders->isEmpty())
        <div class="alert alert-info">
            @lang('You have no active cars to raise a complaint about.')
        </div>
        @else
        <form method="POST" action="{{ route('backend.complaints.store') }}">
            @csrf

            <div class="row">
                <div class="col-12 col-sm-6 mb-3">
                    <div class="form-group">
                        <label class="form-label" for="order_id">@lang('Which car?') <span class="text-danger">*</span></label>
                        <select name="order_id" id="order_id" class="form-control" required>
                            @foreach($orders as $order)
                            <option value="{{ $order->id }}" @if(old('order_id') == $order->id) selected @endif>
                                {{ $order->car_number }}
                            </option>
                            @endforeach
                        </select>
                        @error('order_id')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-group">
                        <label class="form-label" for="message">
                            @lang('What happened?') <span class="text-danger">*</span>
                        </label>
                        <textarea name="message" id="message" rows="6" class="form-control" required
                                  placeholder="@lang('Describe the problem in up to :count words', ['count' => $maxWords])">{{ old('message') }}</textarea>
                        <small class="form-text text-muted">
                            <span id="word-count">0</span> / {{ $maxWords }} @lang('words')
                        </small>
                        @error('message')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-success" id="submit-complaint">
                        <i class="fas fa-paper-plane"></i> @lang('Submit Complaint')
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

@push ('after-scripts')
<script type="module">
    $(document).ready(function() {
        const maxWords = {{ $maxWords }};
        const $message = $('#message');
        const $count = $('#word-count');
        const $submit = $('#submit-complaint');

        function countWords() {
            const words = $message.val().trim().split(/\s+/).filter(Boolean).length;
            $count.text(words);

            const tooLong = words > maxWords;
            $count.toggleClass('text-danger', tooLong);
            $submit.prop('disabled', tooLong);
        }

        $message.on('input', countWords);
        countWords();
    });
</script>
@endpush
