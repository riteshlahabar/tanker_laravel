@extends('layouts.simple.master')

@section('title', 'New Order')

@section('main_content')
    <x-page-header title="New Order" :trail="['Orders']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Order Details</h4>
                        <span class="f-light">For an order taken over the phone. Orders placed in the app arrive
                            here already.</span>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.order.store') }}" method="POST">
                            @csrf
                            @include('order::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('order::_partials.customer-autofill')
@endsection
