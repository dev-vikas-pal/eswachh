@extends('frontend.layouts.app')

@section('title') Success @endsection

@section('content')
<section class="bg-white-100 text-white-600 py-20">
    <div class="container">
  @if(session('success') === \App\Services\RazorpayService::VERIFIED_MESSAGE)
  <h1 style="font-size: 32px; font-weight: 800; color: #0f7a3a;">{{ session('success') }}</h1>
  @else
  <h1>Order Placed Successfully!</h1>
  @endif

  <p>Your order number is: {{ $order_number }}</p>
  <p>You can check the status of your order by logging into your account.</p>

  <a href="">Home</a>
    </div>
</section>
@endsection