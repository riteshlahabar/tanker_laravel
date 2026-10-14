@extends('layouts.simple.master')

@section('title', 'Order ' . $order->order_no)

@section('main_content')
    <x-page-header :title="$order->order_no" :trail="['Orders']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-7">
                @include('order::_partials.detail-card')
                @include('order::_partials.actions-card')
            </div>
            <div class="col-xl-5">
                @include('order::_partials.timeline')
            </div>
        </div>
    </div>
@endsection
