@extends('backend.layouts.app')

@section('title') {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item type="active" icon='{{ $module_icon }}'>{{ __($module_title) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
@if(session('success'))
    <div class="p-4 my-4 text-sm font-semibold border border-green-800 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
        {{ session('success') }}
    </div>
@endif
@php
    session()->forget('success');
@endphp
<div class="card">
    <div class="card-body">
        <form action='{{ route("backend.$module_name.clothDelivery") }}' method="POST" class="form" id="cleanerForm" enctype="multipart/form-data">
            @csrf
            <div class="row mt-4">
                <div class="col">
                    <div class="row mb-3 filterclass">
                        <div class="col-sm-4">
                            <label for="filter_car_number" class="form-label">Car Number</label>
                            <input type="text" name="filter_car_number" id="filter_car_number" value="" class="form-control">
                        </div>
                        <div class="col-sm-4">
                            <button name="submit" style="margin-top: 30px;" class="btn btn-primary">Find Car</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @if($order)
        <form action='{{ route("backend.$module_name.clothDelivery") }}' method="POST" class="form" id="cleanerForm" enctype="multipart/form-data">
            @csrf
            <div class="row mt-4">
                <div class="col">
                    <div class="row mb-3">
                        <div class="col-sm-12">
                            <label class="form-label col-sm-3">Car Number:</label>
                            <span class="col-sm-9">{{$order->car_number}}</span>
                        </div>
                        <div class="col-sm-12">
                            <label class="form-label col-sm-3">Cloth Pick Up Count:</label>
                            <span class="col-sm-9">{{$order->cloth_count}}</span>
                        </div>
                        <div class="col-sm-12">
                            <label class="form-label col-sm-3">Cloth Delivery Count:</label>
                            <div class="col-sm-5">
                                <input type="text" name="delivery_count" id="delivery_count" value="" class="form-control">
                                <input type="hidden" name="order_id" value="{{$order->order_id}}">
                                <input type="hidden" name="pickup_count" value="{{$order->cloth_count}}">
                            </div>
                            <br>
                            <div class="col-sm-3">
                                <button name="submit" name="deliver" class="btn btn-primary">Deliver</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>

@endsection
<x-library.select2 />
@push ("after-scripts")
<script>
    $('#filter_assigned_user_id,#filter_date').change(function() {
        var url = '{{ route("backend.cleaners.index") }}?';
        var filter_assigned_user_id = $('#filter_assigned_user_id').val();
        if (filter_assigned_user_id!='*') {
            url += 'filter_assigned_user_id=' + filter_assigned_user_id + '&';
        }
        var filter_date = $('#filter_date').val();
        if (filter_date) {
            url += 'filter_date=' + filter_date;
        }
        url = url.replace(/&$/, '');
        window.location.href = url;
    });
    function addCleanerActivity() {
        var formData = new FormData(document.getElementById('cleanerForm'));
        $.ajax({
            url: '{{route("backend.cleaners.store")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {

            },
            error: function(data) {

            }
        });
    }
</script>
@endpush
