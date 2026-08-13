<html>

<head>
  <meta charset="utf-8">
  <title>Daily car cleaning service</title>
  <base href="/">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <meta name="keywords" content="car cleaning, car cleaning services, eswachh, greater noida car cleaning, greater noida car cleaning service, car cleaning services at home, car cleaning at chi, sector chi greater noida">
  <!-- Additional CSS Files -->
  <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
  <link rel="stylesheet" type="text/css" href="assets/css/owl-carousel.css">
  <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
</head>

<body data-new-gr-c-s-check-loaded="14.1130.0" data-gr-ext-installed="" cz-shortcut-listen="true">
  <app-root ng-version="10.1.1">
    <div class="wrapper">
      <app-layout-main-header>
        <header class="header-area header-sticky background-header">
          <div class="container">
            <div class="row">
              <div class="col-12">
                <nav class="main-nav">
                  <a href="/" class="logo">
                    <img src="assets\images\logo.png">
                  </a>
                  <ul class="nav" id="nav-menu">
                    <li class="scroll-to-section">
                      <a href="/#welcome" class="active">Home</a>
                    </li>
                    <li class="scroll-to-section">
                      <a href="/#about">About Us</a>
                    </li>
                    <li class="scroll-to-section">
                      <a href="/#services">Packages</a>
                    </li>
                    <li class="scroll-to-section">
                      <a href="/#about2">FAQ</a>
                    </li>
                    <li class="scroll-to-section">
                      <a href="/#contact-us">Contact Us</a>
                    </li>
                    <li class="scroll-to-section">
                      <a href="{{ route('frontend.orders.index') }}">Subscribe</a>
                    </li>
                    @guest
                    <li class="scroll-to-section">
                      <a href="{{ route('login') }}">Login</a>
                    </li>
                    @endif
                    @auth
                    <li class="scroll-to-section">
                      <a href='{{ route("backend.dashboard") }}'>&nbsp;{{__('Dashboard')}}</a>
                    </li>
                    <li class="scroll-to-section">
                      <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">&nbsp;{{__('Logout')}}</a>
                    </li>
                    @endif
                  </ul>
                  <a class="menu-trigger">
                    <span>Menu</span>
                  </a>
                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    {{ csrf_field() }}
                  </form>
                </nav>
              </div>
            </div>
          </div>
        </header>
        <script>
    document.addEventListener('DOMContentLoaded', function() {
      const menuTrigger = document.querySelector('.menu-trigger');
      const navMenu = document.getElementById('nav-menu');

      menuTrigger.addEventListener('click', function() {
        if (navMenu.style.display === 'block') {
          navMenu.style.display = 'none';
        } else {
          navMenu.style.display = 'block';
        }
      });
    });
  </script>