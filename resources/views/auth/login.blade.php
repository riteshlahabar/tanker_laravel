@extends('layouts.authentication.master')

@section('title', 'Login')

@section('main_content')
    <div class="container-fluid p-0">
        <div class="row m-0">
            <div class="col-12 p-0">
                <div class="login-card login-dark">
                    <div>
                        <div>
                            <a class="logo" href="{{ url('/') }}">
                                <img class="img-fluid for-light" src="{{ asset('assets/images/logo/logo.png') }}" alt="{{ config('app.name') }}">
                                <img class="img-fluid for-dark" src="{{ asset('assets/images/logo/logo_dark.png') }}" alt="{{ config('app.name') }}">
                            </a>
                        </div>
                        <div class="login-main">
                            @include('auth._partials.login-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('auth._partials.submit-spinner')
@endsection
