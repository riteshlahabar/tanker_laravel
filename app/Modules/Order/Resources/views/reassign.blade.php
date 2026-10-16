@extends('layouts.simple.master')

@section('title', 'Change Driver')

@section('main_content')
    <x-page-header title="Change Driver" :trail="['Orders', $order->order_no]" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $order->order_no }}</h4>
                        <span class="f-light">{{ $order->customer?->full_name }} ·
                            {{ $order->delivery_date?->format('d M Y') }}</span>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.order.reassign.update', $order) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            @include('order::_partials.reassign-fields')
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                @include('order::_partials.delivery-summary')
            </div>
        </div>
    </div>
@endsection
