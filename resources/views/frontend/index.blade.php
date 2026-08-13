@extends('frontend.layouts.app')

@section('title') {{app_name()}} @endsection
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
<div id="page-loader">
  <div class="loader"></div>
</div>
<div class="modal" id="renewModal">
  <div class="modal-dialog">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title" id="renewModalLabel">Renew Subscription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Step 1: Enter Car Number -->
        <div id="step1">
          <label for="cr_number">Car Number:</label>
          <input type="text" id="cr_number" class="form-control" />
          <button class="btn btn-primary mt-2" onclick="checkCar()">Next</button>
        </div>

        <!-- Step 2: Show Car & Select Months -->
        <div id="step2" style="display: none;">
          <div id="carDetails" class="mb-2"></div>
          <label for="pop_pakage_type">Select Months:</label>
          <select id="pop_pakage_type" name="pop_pakage_type" class="pop_pakage_type form-control mb-2" onchange="calculateAmount()">
            @foreach ($durations as $duration)
            <option value="{{ $duration->id }}">{{ $duration->name }}</option>
            @endforeach
          </select>

          <div class="form-group col-sm-6 ml-auto proceed">

          </div>
          <div class="form-group col-sm-12">
            <button type="button" class="btn btn-success col-sm-5 mt-2 addToCart" onclick="return addToCart()" id="addToCart" name="addToCart" value="addToCart">Pay Now</button>
            <a class="btn btn-danger col-sm-5 mt-2" href="/">Cancel</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="welcome" class="welcome-area">
  <div class="header-text">
    <div class="container">
      <div class="row">
        <div data-scroll-reveal="enter left move 30px over 0.6s after 0.4s" class="left-text col-lg-6 col-md-6 col-sm-12 col-xs-12">
          <h1>
            <strong>A Clean car will leave us feeling happier.</strong>
          </h1>
          <p>We provide a doorstep car cleaning service daily.</p>
          <a class="main-button-slider" href="{{ route('frontend.orders.index') }}">Subscribe</a>
          <!-- <a routerlink="/admin/dashboard" class="main-button-slider ml-3" href="/admin/dashboard">Renew</a> -->
          <a href="javascript:void(0)" type="button" class="main-button-slider ml-3 text-white" onclick="return enterCarNo()">Renew</a>

        </div>
        <div data-scroll-reveal="enter right move 30px over 0.6s after 0.4s" class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
          <img src="assets/images/slider-icon.png" alt="First Vector Graphic" class="rounded img-fluid d-block mx-auto">
        </div>
      </div>
    </div>
  </div>
</div>
<marquee id="marqueeId" direction="left">
  <ul>
    <li style="display: inline-block; margin: 0 50px;">
      <strong>1. Flat Rs. 75 off on 3 month subscription.</strong>
    </li>
    <li style="display: inline-block; margin: 0 50px;">
      <strong>2. Flat Rs. 300 off on 6 month subscription.</strong>
    </li>
    <li style="display: inline-block; margin: 0 50px;">
      <strong>3. We are available in greater noida.</strong>
    </li>
  </ul>
</marquee>
<section id="about" class="section">
  <div class="container">
    <div class="row">
      <div data-scroll-reveal="enter left move 30px over 0.6s after 0.4s" class="col-lg-7 col-md-12 col-sm-12">
        <img src="assets/images/left-image.png" alt="App" class="rounded img-fluid d-block mx-auto">
      </div>
      <div class="right-text col-lg-5 col-md-12 col-sm-12 mobile-top-fix">
        <div class="left-heading">
          <h5>eSwachh is the growing daily car cleaning service platform in India.</h5>
        </div>
        <div class="left-text">
          <p>We help customers to subscribe our daily car cleaning service, in their respective sector/society. <br>
            <br> Company partners with many professional in respective areas, helping them with training, tools, technology and support etc.
          </p>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <div class="hr"></div>
      </div>
    </div>
  </div>
</section>
<section id="about2" class="section">
  <div class="container">
    <div class="row">
      <div class="col-lg-5 col-md-12 col-sm-12 mobile-bottom-fix">
        <div class="left-heading">
          <h5>Frequently Asked Questions</h5>
        </div>
        <p>All question and answer based on customer feedback.</p>
        <ul>
          <li>
            <div>
              <h6>Q. Any holiday by cleaner?</h6>
              <p>Yes, there will be one holiday in a week.</p>
            </div>
          </li>
          <li>
            <div>
              <h6>Q. Is there one-time service provided for car wash?</h6>
              <p>No, we provided subscription based services minimum for one month.</p>
            </div>
          </li>
          <li>
            <div>
              <h6>Q. Can hire a cleaner personally?</h6>
              <p>No, Its subscription based where cleaner may change.</p>
            </div>
          </li>
          <li>
            <div>
              <h6>Q. Can replace cleaner if not happy with them?</h6>
              <p>Yes, according to feedback, company will replace cleaner.</p>
            </div>
          </li>
        </ul>
      </div>
      <div data-scroll-reveal="enter right move 30px over 0.6s after 0.4s" class="right-image col-lg-7 col-md-12 col-sm-12 mobile-bottom-fix-big">
        <img src="assets/images/right-image.png" alt="App" class="rounded img-fluid d-block mx-auto">
      </div>
    </div>
  </div>
</section>
<section id="services" class="section">
  <section class="bg-white text-gray-600 p-6 sm:p-20 package">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
      @foreach ($packages as $package)
      @php
      $details_url = route("frontend.packages.show",[encode_id($package->id), $package->slug]);
      @endphp
      <x-frontend.card :url="$details_url" :name="$package->name" :rightInfo="$package->price">
        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
          {!! htmlspecialchars_decode($package->description) !!}
        </p>
      </x-frontend.card>
      @endforeach
    </div>
  </section>
  <!-- <div class="container">
      <div class="row" style="font-size: 15px;">
        @foreach($packages as $package)
          <div class="col-md-4 col-sm-12">
              <div class="item service-item">
                  <div class="icon">
                      <i>
                          <img src="assets/images/service-icon-01.png" alt="">
                      </i>
                  </div>
                  <h5 class="service-title">{{ $package->name }}</h5>
                  <h3 style="font-size: 12px;">(Starts with Rs. {{ $package->price }})</h3>
                  <section>
                  @php
                      echo nl2br(html_entity_decode($package->description));
                  @endphp
                  </section>
              </div>
          </div>
        @endforeach
      </div>
    </div>
  </section> -->
  <section id="contact-us" class="section">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
          <div class="contact-form" style="padding-bottom: 343px;">
            <h3 class="text-white mb-4 text-left" style="font-size: 40px;">Contact Us</h3>
            <div class="cont-us-dtls text-left" style="font-size: 30px;">
              <h5>Email Address: info@eswachh.in</h5>
              <h5>&nbsp;</h5>
              <h5>Phone Number: 8650316068 (10:00 AM to 06:00 PM)</h5>
            </div>
            <form novalidate="" id="contact" action="" method="post" class="ng-untouched ng-pristine ng-valid">
              <div class="row">
                <div class="col-md-6 col-sm-12"></div>
                <div class="col-md-6 col-sm-12"></div>
                <div class="col-lg-12"></div>
                <div class="col-lg-12"></div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
  </app-layout-main-header>
  <app-sidenav>
    <aside class="main-sidebar">
      <section class="sidebar"></section>
    </aside>
  </app-sidenav>
  <div class="content-wrapper">
    <router-outlet></router-outlet>
    <!---->
  </div>
</section>
@endsection

@push ("after-scripts")
<script>
  function enterCarNo() {
    $("#renewModal").modal("show");
  }
  let selectedCar = null;
  const pricePerMonth = 500; // adjust as needed

  function checkCar() {
    const carNumber = document.getElementById('cr_number').value;
    if (!carNumber) return alert('Please enter car number');
    $.ajax({
      url: '{{route("frontend.orders.checkCar")}}',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      data: JSON.stringify({
        car_number: carNumber
      }),
      processData: false,
      contentType: false,
      success: function(data) {
        if (data.error) return alert(data.error);
        selectedCar = data.car;
        window.token = data.token;
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        document.getElementById('carDetails').innerHTML = `
                <input type="hidden" name="pop_car_id" class="pop_car_id" id="pop_car_id" value="${data.order.car_id}"/>
                <input type="hidden" name="pop_car_number" class="pop_car_number" id="pop_car_number" value="${data.order.car_number}"/>
                <input type="hidden" name="pop_order_id" class="pop_order_id" id="pop_order_id" value="${data.order.id}"/>
                <input type="hidden" name="pop_package_id" class="pop_package_id" id="pop_package_id" value="${data.order.package_id}"/>
                <input type="hidden" name="pop_cleaning_type" class="pop_cleaning_type" id="pop_cleaning_type" value="${data.order.cleaning_type}"/>
                <input type="hidden" name="pop_pakage_type" class="pop_pakage_type" id="pop_pakage_type" value="${data.order.pakage_type}"/>
                <input type="hidden" name="pop_cloth_id" class="pop_cloth_id" id="pop_cloth_id" value="${data.order.cloth_id}"/>
                <input type="hidden" name="pop_society_id" class="pop_society_id" id="pop_society_id" value="${data.order.society_id}"/>
                <p><strong>Car No:</strong> ${data.order.car_number}</p>
                <p><strong>Owner:</strong> ${data.order.name}</p>
            `;
        calculateAmount();
      },
    });
  }

  function calculateAmount() {
    $('.priceCart').remove();
    const car_id = $('.pop_car_id').val();
    const package_id = $('.pop_package_id').val();
    const cleaning_type = $('.pop_cleaning_type').val();
    const pakage_type = $('.pop_pakage_type option:selected').val();
    const cloth_id = $('.pop_cloth_id').val();
    const society_id = $('.pop_societys').val();
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

  function redirectToPayment() {
    const months = document.getElementById('months').value;

    // Send to backend to set session/prepare payment
    fetch('{{route("frontend.orders.checkCar")}}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          car_id: selectedCar.id,
          months: months
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.redirect_url) {
          window.location.href = data.redirect_url;
        } else {
          alert('Error preparing payment.');
        }
      });
  }
  function addToCart() {
        $('#page-loader').show();
        $('.error').remove();
        const car_id = $('.pop_car_id').val();
        const order_id = $('.pop_order_id').val();
        const car_number = $('.pop_car_number').val();
        const package_id = $('.pop_package_id').val();
        const cleaning_type = $('.pop_cleaning_type').val();
        const pakage_type = $('.pop_pakage_type option:selected').val();
        const cloth_id = $('.pop_cloth_id').val();
        const society_id = $('.pop_societys').val();
        $.ajax({
            url: '{{route("backend.orders.renewLoginFree")}}',
            method: 'POST',
            data: {
              _token: '{{ csrf_token() }}',
              token: window.token,
              car_id: car_id,
              order_id: order_id,
              car_number: car_number,
              package_id: package_id,
              cleaning_type: cleaning_type,
              pakage_type: pakage_type,
              cloth_id: cloth_id,
              society_id: society_id,
            },
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
                        callback_url: "{{ route('backend.orders.loginFreerenewComplete') }}",
                        redirect: true,
                        handler: function(response) {
                            console.log(response);
                        },
                        prefill: {
                            name: data.name,
                            email: data.email,
                            contact: data.mobile,
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