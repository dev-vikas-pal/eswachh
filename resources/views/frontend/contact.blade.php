@extends('frontend.layouts.app')

@section('title') {{app_name()}} @endsection

@section('content')
<div class="page-banner-wrap text-center bg-cover" style="background-image: url('assets/img/page-banner.jpg')">
    <div class="container">
        <div class="page-heading text-white">
            <h1>Contact Us</h1>
        </div>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">contact Us</li>
            </ol>
        </nav>
    </div>
</div>
<section class="contact-page-wrap section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="single-contact-card card1">
                        <div class="top-part">
                            <div class="icon">
                                <i class="fal fa-envelope"></i>
                            </div>
                            <div class="title">
                                <h4>Email Address</h4>
                                <span>Sent mail asap anytime</span>
                            </div>
                        </div>
                        <div class="bottom-part">                            
                            <div class="info">
                                <p>{{ setting('email') }}</p>
                            </div>
                            <div class="icon">
                                <i class="fal fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="single-contact-card card2">
                        <div class="top-part">
                            <div class="icon">
                                <i class="fal fa-phone"></i>
                            </div>
                            <div class="title">
                                <h4>Phone Number</h4>
                                <span>call us asap anytime</span>
                            </div>
                        </div>
                        <div class="bottom-part">                            
                            <div class="info">
                                <p>{{ setting('mobile') }}</p>
                            </div>
                            <div class="icon">
                                <i class="fal fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="single-contact-card card3">
                        <div class="top-part">
                            <div class="icon">
                                <i class="fal fa-map-marker-alt"></i>
                            </div>
                            <div class="title">
                                <h4>Office Address</h4>
                                <span>Sent mail asap anytime</span>
                            </div>
                        </div>
                        <div class="bottom-part">                            
                            <div class="info">
                                <p>{{ setting('address') }}</p>
                            </div>
                            <div class="icon">
                                <i class="fal fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="row pt-5">
                <div class="col-12 col-xl-8 offset-xl-2 text-center">
                    <div class="section-title">
                        <span>fil the form</span>
                        <h2>get in touch</h2>
                    </div>
                </div>
    
                <div class="col-12 col-lg-12">
                    <div class="contact-form">                                                        
                        <form action="" class="row" id="contact-form">
                            <div class="col-md-6 col-12">
                                <div class="single-personal-info">
                                    <label for="fname">Full Name</label>
                                    <input type="text" id="fname" placeholder="Enter Name" >                                         
                                </div>
                            </div>                            
                            <div class="col-md-6 col-12">
                                <div class="single-personal-info">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" placeholder="Enter Email Address" >                                         
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="single-personal-info">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" id="phone" placeholder="Enter Number">                                         
                                </div>
                            </div>                                      
                            <div class="col-md-6 col-12">
                                <div class="single-personal-info">
                                    <label for="subject">Subject</label>
                                    <input type="text" id="subject" placeholder="Enter Subject">                                         
                                </div>
                            </div>                                      
                            <div class="col-md-12 col-12">
                                <div class="single-personal-info">
                                    <label for="message">Enter Message</label>
                                    <textarea id="message" placeholder="Enter message"></textarea>                                        
                                </div>
                            </div>                                      
                            <div class="col-md-12 col-12 text-center">
                                <input class="submit-btn" type="submit" value="Get A Quote">
                            </div>                                      
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-banner-wrapper">
        <div class="container">
            <div class="cta-banner-box section-padding bg-cover" style="background-image: url('assets/img/cta-banner-bg.jpg')">
                <div class="row align-center">
                    <div class="col-xl-7 text-center text-xl-start offset-xl-1 offset-xl-1">
                        <div class="section-title mb-0">
                            <span>Get A Quote</span>
                            <h2 class="mb-md-0">Need Any Consultations or <br> Work Next Projects</h2>
                        </div>
                    </div>
                    <div class="col-xl-4 mt-4 mt-xl-0 text-center">
                        <a href="contact.html" class="theme-btn">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection