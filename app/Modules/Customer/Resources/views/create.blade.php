@extends('layouts.simple.master')

@section('title', 'Add Customer')

@section('main_content')
    <x-page-header title="Add Customer" :trail="['Customers']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Customer Details</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.customer.store') }}" method="POST">
                            @csrf
                            @include('customer::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
