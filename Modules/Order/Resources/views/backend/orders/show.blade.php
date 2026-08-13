@extends('backend.layouts.app')
@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item route='{{route("backend.$module_name.index")}}' icon='{{ $module_icon }}'>
        {{ __($module_title) }}
    </x-backend-breadcrumb-item>
    <x-backend-breadcrumb-item type="active">{{ __($module_action) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection
<?php
    $user = auth()->user();
    $roles = !empty($user)?$user->roles()->pluck('name')[0]:'';
?>
@section('content')
@props(["data"=>"", "module_name", "module_path", "module_title"=>"", "module_icon"=>"", "module_action"=>""])
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
        <x-backend.section-header :data="$$module_name_singular" :module_name="$module_name" :module_title="$module_title" :module_icon="$module_icon" :module_action="$module_action" />
        <div class="row mt-4">
            <div class="col-12">
                <p>
                    @lang("All values of :module_name (Id: :id)", ['module_name'=>ucwords($module_name_singular), 'id'=>$$module_name_singular->id])
                </p>
                <table class="table table-responsive-sm table-hover table-bordered">
                    <?php
                    $all_columns = $$module_name_singular->getTableColumns();
                    ?>
                    <thead>
                        <tr>
                            <th scope="col">
                                <strong>
                                    @lang('Name')
                                </strong>
                            </th>
                            <th scope="col">
                                <strong>
                                    @lang('Value')
                                </strong>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Order Number") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->id !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Car Name") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->car_name !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Car Number") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->car_number !!}
                            </td>
                        </tr>
                        <!-- <tr>
                            <td>
                                <strong>
                                    {{ __("User Name") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->user_name !!}
                            </td>
                        </tr> -->
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Package Name") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->package_name !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Amount Paid") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->paid_amount !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Cleaning Type") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->internaltype_name !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Subscription Start Date") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->start_date !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Subscription End Date") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->renew_date !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Payment Mode") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->payment_mode !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Payment Date") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->payment_date !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Transaction Id") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->transaction_id !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Oder Type") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->order_type !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Payment Id") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->payment_id !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Payment Status") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->status !!}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <legend>Cloth Details</legend>
                <table class="table table-responsive-sm table-hover table-bordered">
                    <tbody>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Active Service") }}
                                </strong>
                            </td>
                            <td>
                            @if(!empty($$module_name_singular->cloth_service))
                            Yes
                            @else
                            No
                            @endif
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Cloth Cleaning") }}
                                </strong>
                            </td>
                            <td>
                            {!! $$module_name_singular->cloth_name !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Cloth Count") }}
                                </strong>
                            </td>
                            <td>
                            {!! $$module_name_singular->cloth_count !!}
                            </td>
                        </tr>
                    </tbody>
                </table>
                @if($roles=='cleaner' || $roles=='super admin')
                <legend>Customer Details</legend>
                <table class="table table-responsive-sm table-hover table-bordered">
                    <tbody>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Customer Name") }}
                                </strong>
                            </td>
                            <td>
                                {!! $$module_name_singular->user_name !!} | {!! $$module_name_singular->user_mobile !!}
                            </td>
                        </tr>
                    </tbody>
                </table>
                @endif
                @if($roles=='customer' || $roles=='super admin')
                <legend>Cleaner Details</legend>
                <table class="table table-responsive-sm table-hover table-bordered">
                    <tbody>
                        <tr>
                            <td>
                                <strong>
                                    {{ __("Cleaner Name") }}
                                </strong>
                            </td>
                            <td>
                            @if(!empty($$module_name_singular->assigned_user))
                            {!! $$module_name_singular->assigned_user !!} | {!! $$module_name_singular->cleaner_mobile !!}
                            @else
                            Not Assigned
                            @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
                @endif
                {{-- Lightbox2 Library --}}
                <x-library.lightbox />
            </div>
        </div>
    </div>

    <div class="card-footer">
        <div class="row">
            <div class="col">
                @if ($data != "")
                <small class="float-end text-muted">
                    @lang('Updated at'): {{$data->updated_at->diffForHumans()}},
                    @lang('Created at'): {{$data->created_at->isoFormat('LLLL')}}
                </small>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection