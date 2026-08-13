@extends('frontend.layouts.app')

@section('title') {{ __($module_title) }} @endsection

@section('content')


<div class="page-banner-wrap text-center bg-cover" style="background-image: url('assets/img/page-banner.jpg')">
    <div class="container">
        <div class="page-heading text-white">
            <h1>{{ __($module_title) }}</h1>
        </div>
    </div>
</div>
<section class="team-experts-wrapper section-padding">
    <div class="container">
        <div class="col-12 col-xl-6 offset-xl-3 col-md-8 offset-md-2 text-center">
            <div class="section-title">
                <span>Our Amazing Team</span>
                <h2>We have Well Experience
                    Team Members</h2>
            </div>
        </div>

        <div class="row">
            @foreach ($$module_name as $$module_name_singular)
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('{{$$module_name_singular->image}}')">
                    <div class="member-info">
                        <h4>{{$$module_name_singular->name}}</h4>
                        <p>{{$$module_name_singular->role}}</p>
                    </div>
                </div>
            </div>
            @endforeach
            <!-- <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('assets/img/team/2.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">D. Maria Poddar</a></h4>
                        <p>Designer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member active text-white bg-cover" style="background-image: url('assets/img/team/3.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">Salman Ahmed</a></h4>
                        <p>Developer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('assets/img/team/4.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">RS Rahul</a></h4>
                        <p>Marketer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('assets/img/team/2.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">D. Maria Poddar</a></h4>
                        <p>Designer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('assets/img/team/5.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">Asish Patil</a></h4>
                        <p>Founder & Ceo</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('assets/img/team/4.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">RS Rahul</a></h4>
                        <p>Marketer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="single-team-member text-white bg-cover" style="background-image: url('assets/img/team/3.jpg')">
                    <div class="member-info">
                        <h4><a href="team-details.html">Salman Ahmed</a></h4>
                        <p>Developer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</section>
<!-- 
<section class="bg-white text-gray-600 p-6 sm:p-20">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
        @foreach ($$module_name as $$module_name_singular)
        @php
        $details_url = route("frontend.$module_name.show",[encode_id($$module_name_singular->id), $$module_name_singular->slug]);
        @endphp

        <x-frontend.card :url="$details_url" :name="$$module_name_singular->name">
            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                {{$$module_name_singular->description}}
            </p>
        </x-frontend.card>

        @endforeach
    </div>
    <div class="d-flex justify-content-center w-100 mt-3">
        {{$$module_name->links()}}
    </div>
</section> -->

@endsection