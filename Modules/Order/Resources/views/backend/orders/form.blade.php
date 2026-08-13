<style>
    .disabled {
        pointer-events: none;
        background-color: #f2f2f2;
    }

    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        z-index: 9999;
    }

    .loader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 2s linear infinite;
    }

    .strikethrough {
        text-decoration: line-through;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
<div id="page-loader">
    <div class="loader"></div>
</div>
@php
$user = auth()->user();
$roles = !empty($user)?$user->roles()->pluck('name')[0]:'';
@endphp
@if($roles=='customer')
<style>
    .btnsave {
        display: none;
    }
</style>
<div class="row">
    <div class="col-12 col-sm-4 mb-3 @if(!empty($data->id)) disabled @endif">
        <div class="form-group">
            <?php
            $field_name = 'car_id';
            $field_lable = __("Car Name");
            $field_relation = "car";
            $field_placeholder = __("Select an Car");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-car')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3  @if(!empty($data->id)) disabled @endif">
        <div class="form-group">
            <?php
            $field_name = 'package_id';
            $field_lable = __("Package Name");
            $field_relation = "package";
            $field_placeholder = __("Select an Package");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-package')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3  @if(!empty($data->id)) disabled @endif">
        <div class="form-group">
            <?php
            $field_name = 'car_number';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'cleaning_type';
            $field_lable = label_case($field_name);
            $field_relation = "internaltype";
            $field_placeholder = __("-- Select an option --");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-cleaningType')->attributes(["$required"]) }}
            <input type="hidden" name="order_id" value="<?= $data->id ?? '' ?>">
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'pakage_type';
            $field_lable = 'Package Type';
            $field_relation = "duration";
            $field_placeholder = __("-- Select an option --");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-pakage')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3" style="display: none;">
        <div class="form-group">
            <label class="form-label" for="">Cloth Ironing Service</label>
            <div for="cloth_service">
                @if(!empty($data->cloth_service) && $data->cloth_service==1)
                <input type="checkbox" id="cloth_service" name="cloth_service" value="0">
                @else
                <input type="checkbox" id="cloth_service" name="cloth_service" value="0">
                @endif
                Yes
            </div>
        </div>
    </div>
    <!-- <div class="col-12 col-sm-4 mb-3">
        <div class="form-group cloth_id" style="display: none;">
            <?php
            $field_name = 'cloth_id';
            $field_lable = label_case("Cloths");
            $field_relation = "cloth";
            $field_placeholder = __("-- Select an option --");
            $required = "";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-cloth_id')->attributes(["$required"]) }}
        </div>
    </div> -->
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group proceed">
        </div>
    </div>
</div>
<div class="row">
    <div class="form">
        <div class="col-12 col-sm-4 mb-3">
            <div class="form-group">
                <button type="button" class="btn btn-info text-white col-sm-5 addToCart" id="addToCart" name="addToCart" value="addToCart">Submit</button>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'user_id';
            $field_lable = __("User Name");
            $field_relation = "user";
            $field_placeholder = __("Select an user");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-user')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'car_id';
            $field_lable = __("Car Name");
            $field_relation = "car";
            $field_placeholder = __("Select an Package");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-car')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'package_id';
            $field_lable = __("Package Name");
            $field_relation = "package";
            $field_placeholder = __("Select an Package");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-package')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'cleaning_type';
            $field_lable = label_case($field_name);
            $field_relation = "internaltype";
            $field_placeholder = __("-- Select an option --");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-cleaningType')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'pakage_type';
            $field_lable = 'Package Type';
            $field_relation = "duration";
            $field_placeholder = __("-- Select an option --");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-pakage')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'car_number';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'start_date';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->date($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'renew_date';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->date($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'payment_mode';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'order_type';
            $field_lable = label_case($field_name);
            $field_placeholder = "-- Select an option --";
            $required = "required";
            $select_options = [
                'offline' => 'Offline',
                'online' => 'Online',
            ];
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, $select_options)->placeholder($field_placeholder)->class('form-control select2')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'payment_date';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->date($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <!-- <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'transaction_id';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div> -->
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'payment_id';
            $field_lable = label_case($field_name);
            $field_placeholder = $field_lable;
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->text($field_name)->placeholder($field_placeholder)->class('form-control')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'assigned_user_id';
            $field_lable = __("Cleaner Name");
            $field_relation = "user";
            $field_placeholder = __("Select an user");
            $required = "required";
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, isset($data->$field_relation)?optional($data->$field_relation)->pluck('name', 'id'):'')->placeholder($field_placeholder)->class('form-control select2-cleaner')->attributes(["$required"]) }}
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3">
        <div class="form-group">
            <?php
            $field_name = 'status';
            $field_lable = label_case($field_name);
            $field_placeholder = "-- Select an option --";
            $required = "required";
            $select_options = [
                '1' => 'Pending',
                '2' => 'Active',
                '3' => 'Deactive',
                '4' => 'Hold'
            ];
            ?>
            {{ html()->label($field_lable, $field_name)->class('form-label') }} {!! fielf_required($required) !!}
            {{ html()->select($field_name, $select_options)->placeholder($field_placeholder)->class('form-control select2')->attributes(["$required"]) }}
        </div>
    </div>
</div>
@endif
<x-library.select2 />
@push ('after-scripts')
<script type="module">
    function toggleCleaningType() {
        var checkbox = document.getElementById('cloth_service');
        var cleaningTypeDiv = document.querySelector('.cloth_id');
        var cleaningTypeSelect = document.getElementById('cloth_id');

        if (checkbox && checkbox.checked) {
            cleaningTypeDiv.style.display = 'block';
        } else {
            cleaningTypeDiv.style.display = 'none';
        }
    }
    $(document).on('click', '#cloth_service', function() {
        toggleCleaningType();
        getPrice();
    })
    $(document).on('click', '#addToCart', function() {
        addToCart();
    })
    $(document).ready(function() {
        getPrice();
        toggleCleaningType();
    })
    $(document).ready(function() {
        $(document).on('select2:open', () => {
            document.querySelector('.select2-search__field').focus();
            document.querySelector('.select2-container--open .select2-search__field').focus();
        });

        $('.select2-user').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{ route("backend.users.index_list", ["user_type" => "customer"]) }}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
        $('.select2-cleaner').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{ route("backend.users.index_list", ["user_type" => "cleaner"]) }}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
        $('.select2-package').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.packages.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
        $('.select2-car').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.cars.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
        $('.select2-cloth_id').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.cloths.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    var results = [{
                        id: '0',
                        text: 'Select an option'
                    }].concat(data);
                    return {
                        results: results
                    };
                },
                cache: true
            }
        });
        $('.select2-cleaningType').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.internaltypes.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
        $('.select2-pakage').select2({
            theme: "bootstrap4",
            placeholder: '@lang("Select an option")',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{route("backend.durations.index_list")}}',
                dataType: 'json',
                data: function(params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
    });
    $('#pakage_type,#cloth_id,#cleaning_type').on('change', function() {
        getPrice();
    });

    function getPrice() {
        $('.priceCart').remove();
        const car_id = $('#car_id option:selected').val();
        const package_id = $('#package_id option:selected').val();
        const cleaning_type = $('#cleaning_type option:selected').val();
        const pakage_type = $('#pakage_type option:selected').val();
        //const cloth_id = $('#cloth_id option:selected').val();
        var checkbox = document.getElementById('cloth_service');
        if (checkbox && checkbox.checked) {
            var cloth_id = $('#cloth_id option:selected').val();
        } else {
            var cloth_id = 0;
        }
        const user_id = $('#user_id option:selected').val();
        if (car_id != '' && package_id != '' && cleaning_type != '' && pakage_type != '') {
            $('#page-loader').show();
            $.ajax({
                url: '{{route("frontend.orders.price")}}',
                method: 'POST',
                data: {
                    car_id: car_id,
                    package_id: package_id,
                    cleaning_type: cleaning_type,
                    pakage_type: pakage_type,
                    cloth_id: cloth_id,
                    user_id: user_id,
                },
                success: function(data) {
                    if (data.final_price != '') {
                        var html = '<button type="button" class="btn btn-success col-sm-12 priceCart" name="priceCart" value="priceCart">';

                        html += '<span class="">Sub-Total ₹' + data.subTotal + '</span><br/>';

                        if (data.discount > 0) {
                            html += '<span>Discount ₹' + data.discount + '</span><br/>';
                        }
                        if (data.cloth_price > 0) {
                            html += '<span>Cloth Price  ₹' + data.cloth_price + '</span><br/>';
                        }
                        html += '<b>Grand Total  ₹' + data.final_price + '</b></button>';
                        $('.proceed').html(html);
                    }
                    $('#page-loader').hide();
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                    $('#page-loader').hide();
                }
            });
        }
    }

    function addToCart() {
        $('#page-loader').show();
        var userData = @json(optional(auth()->user())->only(['name','email','mobile']))??null;
        $('.error').remove();
        //var formData = new FormData(document.getElementById('orderForm'));
        var formData = new FormData(document.querySelector('.form'));
        $.ajax({
            url: '{{route("backend.orders.renew")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.success && data.razorpay_order_id) {
                    var razorpayOrderID = data.razorpay_order_id;
                    var csrfToken = "{{ csrf_token() }}";
                    var options = {
                        key: "{{ env('RAZOR_KEY') }}",
                        amount: 100,
                        currency: 'INR',
                        name: 'Eswachh Integrated Solutions Private Limited',
                        description: 'Payment for Order',
                        order_id: razorpayOrderID,
                        callback_url: "{{ route('backend.orders.renewComplete') }}",
                        redirect: true,
                        handler: function(response) {
                            console.log(response);
                        },
                        prefill: {
                            name: userData.name,
                            email: userData.email,
                            contact: userData.mobile,
                        },
                        theme: {
                            color: '#F37254' // Customize the payment button color
                        }
                    };
                    $('#page-loader').show();
                    options['headers'] = {
                        'X-CSRF-TOKEN': csrfToken
                    };
                    var rzp = new Razorpay(options);
                    rzp.open();
                }
            },
            error: function(data) {
                var res = data.responseJSON;
                var errors = res.errors;
                for (var fieldName in errors) {
                    var errorMessage = errors[fieldName][0];
                    var fieldElement = $('[name="' + fieldName + '"]');
                    if (fieldElement.length) {
                        fieldElement.after('<div class="text-danger error">' + errorMessage + '</div>');
                    }
                    console.log(fieldName);
                    console.log(errorMessage);
                }
                if (typeof res.otp != 'undefind' && res.otp == false) {
                    $("#otpModal").modal("show");
                    sendOTP();
                }
                $('#page-loader').hide();
            }
        });
    }
</script>
@endpush