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
                                {{-- Capped inline: Cuba's own CSS loads after @section('css') and would win the cascade. The full wordmark is tall, so it needs a height limit the theme does not give it. --}}
                                <img class="img-fluid for-light" style="max-height: 56px; width: auto" src="{{ asset('assets/images/logo/logo.png') }}" alt="{{ config('app.name') }}">
                                <img class="img-fluid for-dark" style="max-height: 56px; width: auto" src="{{ asset('assets/images/logo/logo_dark.png') }}" alt="{{ config('app.name') }}">
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
