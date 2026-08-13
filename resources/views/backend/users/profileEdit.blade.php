@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@section('breadcrumbs')
<x-backend-breadcrumbs>
    <x-backend-breadcrumb-item route='{{route("backend.$module_name.index")}}' icon='{{ $module_icon }}'>
        {{ __($module_title) }}
    </x-backend-breadcrumb-item>
    <x-backend-breadcrumb-item route='{{route("backend.$module_name.show", $user->id)}}' icon='{{ $module_icon }}'>
        {{ $user->name }}
    </x-backend-breadcrumb-item>

    <x-backend-breadcrumb-item type="active">{{ __($module_action) }}</x-backend-breadcrumb-item>
</x-backend-breadcrumbs>
@endsection

@section('content')
<x-backend.layouts.edit :data="$user">
    <x-backend.section-header>
        <i class="{{ $module_icon }}"></i> {{ __('Profile') }} <small class="text-muted">{{ __($module_action) }}</small>

        <x-slot name="subtitle">
            @lang(":module_name Management Dashboard", ['module_name'=>Str::title($module_name)])
        </x-slot>
        <x-slot name="toolbar">
            <x-backend.buttons.return-back />
        </x-slot>
    </x-backend.section-header>

    <hr>

    <div class="row mt-4">
        <div class="col">
            {{ html()->modelForm($userprofile, 'PATCH', route('backend.users.profileUpdate', $$module_name_singular->id))->class('form-horizontal')->attributes(['enctype'=>"multipart/form-data"])->open() }}
            <div class="form-group row">
                {{ html()->label(__('labels.backend.users.fields.avatar'))->class('col-md-2 form-control-label')->for('name') }}

                <div class="col-md-5">
                    <img src="{{asset($$module_name_singular->avatar)}}" class="user-profile-image img-fluid img-thumbnail" style="max-height:200px; max-width:200px;" />
                </div>
                <div class="col-md-5">
                    <input id="file-multiple-input" name="avatar" multiple="" type="file">
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'first_name';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "required";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'last_name';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "required";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'email';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "required";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->email($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'mobile';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'gender';
                        $field_lable = label_case($field_name);
                        $field_placeholder = "-- Select an option --";
                        $required = "";
                        $select_options = [
                            'Female' => 'Female',
                            'Male' => 'Male',
                            'Other' => 'Other',
                        ];
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->select($field_name, $select_options)->placeholder($field_placeholder)->class('form-select')->attributes(["$required"]) }}
                    </div>
                </div>

                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'date_of_birth';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->date($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="state_id">State</label>
                    <select class="form-control form-select state_id" name="state_id" id="state_id" onchange="return getLocationInfos('cities',this.value)">
                        @foreach ($stateList as $state)
                        <option value="{{ $state->id }}" {{ $userprofile->state_id == $state->id ? 'selected' : '' }}>
                            {{ $state->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="city_id">City</label>
                    <select class="form-control form-select cities" name="city_id" id="city_id" onchange="return getLocationInfos('areas',this.value)">

                    </select>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="area_id">Area</label>
                    <select class="form-control form-select areas" name="area_id" id="area_id" onchange="return getLocationInfos('sectors',this.value)">

                    </select>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="sector_id">Sector</label>
                    <select class="form-control form-select sectors" name="sector_id" id="sector_id" onchange="return getLocationInfos('societys',this.value)">
                    </select>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="society_id">Society</label>
                    <select class="form-control form-select societys" name="society_id" id="society_id">
                    </select>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="house_no">Flat No. / House No.</label>
                    <input type="text" class="form-control" name="house_no" id="house_no" value="{{$userprofile->house_no}}">
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <label class="form-label" for="office_time">Office Time.</label>
                    <input type="time" class="form-control" name="office_time" id="office_time" value="09:00">
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'address';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->textarea($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <!-- <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'bio';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->textarea($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div> -->
            </div>
            <!-- <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'url_website';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'url_facebook';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'url_instagram';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'url_twitter';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
                <div class="col-12 col-sm-3 mb-3">
                    <div class="form-group">
                        <?php
                        $field_name = 'url_linkedin';
                        $field_lable = label_case($field_name);
                        $field_placeholder = $field_lable;
                        $required = "";
                        ?>
                        {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
                        {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
                    </div>
                </div>
            </div> -->
            <div class="row mt-4">
                <div class="col-6">
                    <x-backend.buttons.save />
                </div>
                <div class="col-6 text-end">
                    <x-backend.buttons.cancel />
                </div>
            </div>
            {{ html()->closeModelForm() }}
        </div>
    </div>
</x-backend.layouts.edit>
@endsection
@push ("after-scripts")
<script>
 let selectedStateId = "{{ $userprofile->state_id }}";
let selectedCityId = "{{ $userprofile->city_id }}";
let selectedAreaId = "{{ $userprofile->area_id }}";
let selectedSectorId = "{{ $userprofile->sector_id }}";
let selectedSocietyId = "{{ $userprofile->society_id }}";
if (selectedStateId) {
    getLocationInfos('cities', selectedStateId).then(() => {
        $('#city_id').val(selectedCityId);
        if (selectedCityId) {
            return getLocationInfos('areas', selectedCityId);
        }
    }).then(() => {
        $('#area_id').val(selectedAreaId);
        if (selectedAreaId) {
            return getLocationInfos('sectors', selectedAreaId);
        }
    }).then(() => {
        $('#sector_id').val(selectedSectorId);
        if (selectedSectorId) {
            return getLocationInfos('societys', selectedSectorId);
        }
    }).then(() => {
        $('#society_id').val(selectedSocietyId);
    });
}
function getLocationInfos(type, id) {
    return new Promise((resolve, reject) => {
        if (type != '' && id != '') {
            $('#page-loader').show();
            $.ajax({
                url: '{{ route("frontend.orders.location") }}',
                method: 'POST',
                data: {
                    parent_type: type,
                    parent_id: id,
                },
                success: function(data) {
                    $('.' + type).html(data.html);
                    $('#page-loader').hide();
                    resolve();
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                    $('#page-loader').hide();
                    reject(error);
                }
            });
        } else {
            resolve();
        }
    });
}
</script>
@endpush