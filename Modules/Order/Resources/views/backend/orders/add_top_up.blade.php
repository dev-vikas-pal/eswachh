@extends('backend.layouts.app')

@section('title') Order Top Up @endsection
@section('content')
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
    .payment-warning {
        position: absolute;
        top: calc(50% + 45px);
        left: 50%;
        transform: translateX(-50%);
        width: 90%;
        max-width: 420px;
        text-align: center;
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.5;
        text-shadow: 0 1px 2px rgba(0,0,0,.6);
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
    <div class="payment-warning">Please do not close or refresh this window while your payment is being processed.</div>
</div>
<div class="card">
    <div class="card-body">
        <form action='{{ route("backend.orders.store") }}' method="POST" class="form" id="cleanerForm" enctype="multipart/form-data">
            @csrf
            <div class="row mt-4">
                <h4>Your Current Cloth Counts: {{ $order_info->cloth_count}}</h4>
                <div class="col">
                    <div class="row">
                        <div class="col-sm-4">
                            <label class="form-label" for="cloth_id">Add Cloth Counts</label>
                            <select class="form-control" name="cloth_id" id="cloth_id">
                                @foreach ($clothList as $cloth)
                                @if($cloth->id==$order_info->cloth_id)
                                <option value="{{ $cloth->id }}" selected>{{ $cloth->name }} ({{ $cloth->price }})</option>
                                @else
                                <option value="{{ $cloth->id }}">{{ $cloth->name }} (Rs.{{ $cloth->price }})</option>
                                @endif
                                @endforeach
                            </select>
                            <input type="hidden" name="order_id" value="{{ $orderId }}">
                        </div>
                        <div class="col-sm-4">
                            <button type="button" style="margin-top:30px;" class="btn btn-success col-sm-5 addTopUP" id="addTopUP" name="addTopUP" value="addTopUP" onclick=" return addToCart()">Pay</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@push ("after-scripts")
<script>
    function addToCart() {
        var userData = @json(optional(auth()->user())->only(['name', 'email', 'mobile'])) ?? null;
        $('.error').remove();
        var formData = new FormData(document.querySelector('.form'));
        $.ajax({
            url: '{{route("backend.orders.addTopUp")}}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.success && data.razorpay_order_id) {
                    var razorpayOrderID = data.razorpay_order_id;
                    var csrfToken = "{{ csrf_token() }}";
                    var options = {
                        key: "{{ config('services.razorpay.key') }}",
                        amount: 100,
                        currency: 'INR',
                        name: 'Eswachh Integrated Solutions Private Limited',
                        description: 'Payment for Order',
                        order_id: razorpayOrderID,
                        callback_url: "{{ route('backend.orders.addTopUpComplete') }}",
                        redirect: true,
                        handler: function(response) {
                            console.log(response);
                        },
                        prefill: {
                            name:userData.name,
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