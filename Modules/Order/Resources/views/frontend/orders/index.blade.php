@extends('frontend.layouts.app')

@section('title') {{ __($module_title) }} @endsection
<style>
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
@section('content')
<section class="bg-white-100 text-white-600 py-20">
    <div class="container">
        <form action="" id="orderForm" class="form">
            <div id="page-loader">
                <div class="loader"></div>
            </div>
            <div class="">
                <h1>User Detail</h1>
                <div class="row well">
                    <div class="col-sm-12">
                        <div class="row">
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" class="form-control" name="name" id="name">
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                            <div class="form-group col-sm-4">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="mobile_no">Mobile Number</label>
                                        <input type="text" class="form-control" name="mobile_no" id="mobile_no">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="car_number">Car Number</label>
                                        <input type="text" class="form-control" name="car_number" id="car_number">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="state_id">State</label>
                                <select class="form-control state_id" name="state_id" id="state_id" onchange="return getLocationInfos('cities',this.value)">
                                    <option> </option>
                                    @foreach ($stateList as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="city_id">City</label>
                                <select class="form-control cities" name="city_id" id="city_id" onchange="return getLocationInfos('areas',this.value)">

                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="area_id">Area</label>
                                <select class="form-control areas" name="area_id" id="area_id" onchange="return getLocationInfos('sectors',this.value)">

                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="sector_id">Sector</label>
                                <select class="form-control sectors" name="sector_id" id="sector_id" onchange="return getLocationInfos('societys',this.value)">
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="society_id">Society</label>
                                <select class="form-control societys" name="society_id" id="society_id" onchange="return getPrice()">
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="house_no">Flat No. / House No.</label>
                                <input type="text" class="form-control" name="house_no" id="house_no">
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="office_time">Office Time.</label>
                                <input type="time" class="form-control" name="office_time" id="office_time" value="09:00">
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="car_id">Car Name</label>
                                <select class="form-control car_id" name="car_id" id="car_id" onchange="return getPrice()">
                                    <option> </option>
                                    @foreach ($carList as $car)
                                    <option value="{{ $car->id }}">{{ $car->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="package_id">Package</label>
                                <select class="form-control package_id" name="package_id" id="package_id" onchange="return getPrice()">
                                    <option> </option>
                                    @foreach ($packageList as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="cleaning_type">Internal Type</label>
                                <select class="form-control cleaning_type" name="cleaning_type" id="cleaning_type" onchange="return getPrice()">
                                    <option> </option>
                                    @foreach ($internaltypeList as $internal)
                                    <option value="{{ $internal->id }}">{{ $internal->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="form-label" for="pakage_type">Duration</label>
                                <select class="form-control pakage_type" name="pakage_type" id="pakage_type" onchange="return getPrice()">
                                    <option> </option>
                                    @foreach ($durationList as $duration)
                                    <option value="{{ $duration->id }}">{{ $duration->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-4" style="display: none;">
                                <label class="form-label" for="">Cloth Ironing Service</label>
                                <div for="cloth_service">
                                    <input type="checkbox" id="cloth_service" name="cloth_service" value="0" onchange="toggleCleaningType()">
                                    Yes
                                </div>
                            </div>
                            <div class="form-group col-sm-4 cloth_id" style="display: none;">
                                <label class="form-label" for="cloth_id">Cloth Counts</label>
                                <select class="form-control" name="cloth_id" id="cloth_id" onchange="return getPrice()">
                                    <option> </option>
                                    @foreach ($clothList as $cloth)
                                    <option value="{{ $cloth->id }}">{{ $cloth->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-4 ml-auto proceed">

                            </div>
                            <div class="form-group col-sm-4 mt-8">
                                <button type="button" class="btn btn-success col-sm-5 addToCart" id="addToCart" name="addToCart" value="addToCart">Submit</button>
                                <a class="btn btn-danger col-sm-5" href="/">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- OTP Verification Modal -->
            <div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="otpModalLabel">Verify OTP</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="otpVerificationForm">
                                <div class="form-group">
                                    <label for="otp">Enter OTP:</label>
                                    <input type="text" class="form-control" id="otp" name="otp" required>
                                </div>
                                <button type="button" onclick="verifyOTP()" class="btn btn-primary">Verify</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
@push ("after-scripts")
<script>
    function toggleCleaningType() {
        var checkbox = document.getElementById('cloth_service');
        var cleaningTypeDiv = document.querySelector('.cloth_id');
        var cleaningTypeSelect = document.getElementById('cloth_id');

        if (checkbox.checked) {
            cleaningTypeDiv.style.display = 'block';
        } else {
            cleaningTypeDiv.style.display = 'none';
            cleaningTypeSelect.value = ''; // Unset the value when hiding
        }
        getPrice();
    }

    $(document).on('click', '#addToCart', function() {
        addToCart();
    })

    function addToCart() {
        var confirmation = confirm("By clicking OK, you agree to the Terms and Conditions. Do you want to proceed?");
        if(!confirmation){
            return false;
        }
        $('#page-loader').show();
        $('.error').remove();
        var formData = new FormData(document.getElementById('orderForm'));
        $.ajax({
            url: '{{route("frontend.orders.addToCart")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.success && data.razorpay_order_id) {
                    var razorpayOrderID = data.razorpay_order_id;
                    var options = {
                        key: "{{ env('RAZOR_KEY') }}",
                        amount: 100,
                        currency: 'INR',
                        name: 'Eswachh Integrated Solutions Private Limited',
                        description: 'Payment for Order',
                        order_id: razorpayOrderID,
                        callback_url: "{{ route('razorpaypayment') }}",
                        redirect: true,
                        handler: function(response) {},
                        prefill: {
                            name: formData.get('name'),
                            email: formData.get('email'),
                            contact: formData.get('mobile_no')
                        },
                        theme: {
                            color: '#F37254' // Customize the payment button color
                        }
                    };
                    $('#page-loader').show();
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
                }
                if (typeof res.otp != 'undefind' && res.otp == false) {
                    $("#otpModal").modal("show");
                    sendOTP();
                }
                $('#page-loader').hide();
            }
        });
    }

    function sendOTP() {
        $('#page-loader').show();
        $('.error').remove();
        var formData = new FormData(document.getElementById('orderForm'));
        $.ajax({
            url: '{{route("otp.send-otp")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.success == false) {
                    $('[name="otp"]').after('<div class="text-danger error">' + data.message + '</div>');
                }
                if (data.success != false) {
                    $('[name="otp"]').after('<div class="text-success error">' + data.message + '</div>');
                }
                $('#page-loader').hide();
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
                }
                $('#page-loader').hide();
            }
        });
    }

    function verifyOTP() {
        $('#page-loader').show();
        $('.error').remove();
        var formData = new FormData(document.getElementById('orderForm'));
        $.ajax({
            url: '{{route("otp.verify-otp")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {

                if (data.success) {
                    $('[name="otp"]').after('<div class="text-success error">' + data.message + '</div>');
                    $('#page-loader').show();
                    addToCart();
                    $("#otpModal").modal("hide");
                    $('[name="mobile_no"]').attr('readonly', true);
                } else {
                    $('[name="otp"]').after('<div class="text-danger error">' + data.message + '</div>');
                    $('#page-loader').hide();
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
                }
                $('#page-loader').hide();
            }
        });
    }

    function getPrice() {
        $('.priceCart').remove();
        const car_id = $('.car_id option:selected').val();
        const package_id = $('.package_id option:selected').val();
        const cleaning_type = $('.cleaning_type option:selected').val();
        const pakage_type = $('.pakage_type option:selected').val();
        const cloth_id = $('.cloth_id option:selected').val();
        const society_id = $('.societys option:selected').val();
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
                    society_id: society_id,
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

    function getLocationInfos(type, id) {
        if (type != '' && id != '') {
            $('#page-loader').show();
            $.ajax({
                url: '{{route("frontend.orders.location")}}',
                method: 'POST',
                data: {
                    parent_type: type,
                    parent_id: id,
                },
                success: function(data) {
                    $('.' + type).html(data.html);
                    $('#page-loader').hide();
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                    $('#page-loader').hide();
                }
            });
        }
    }
</script>
@endpush