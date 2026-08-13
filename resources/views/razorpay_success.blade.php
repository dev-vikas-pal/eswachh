@extends('frontend.layouts.app')

@section('title') Success @endsection

@section('content')
<section class="bg-white-100 text-white-600 py-20">
    <div class="container">
  <h1>Order Placed Successfully!</h1>

  <p>Your order number is: {{ $order_number }}</p>
  <p>You can check the status of your order by logging into your account.</p>

  <a href="">Home</a>
    </div>
</section>
@endsection