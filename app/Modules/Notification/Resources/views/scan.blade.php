@extends('layouts.simple.master')

@section('title', 'WhatsApp Scan')

@section('main_content')
    <x-page-header title="WhatsApp Scan" :trail="['WhatsApp']" />

    <div class="container-fluid">
        @include('notification::_partials.scan-notices')

        <div class="row">
            <div class="col-xl-5 col-md-6">
                @include('notification::_partials.scan-qr')
            </div>
            <div class="col-xl-7 col-md-6">
                @include('notification::_partials.scan-status')
                @include('notification::_partials.scan-test-form')
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('notification::_partials.scan-poller')
@endsection
