@extends('layouts.simple.master')

@section('title', 'Edit Customer')

@section('main_content')
    <x-page-header title="Edit Customer" :trail="['Customers']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $customer->full_name }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.customer.update', $customer) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('customer::_partials.fields', ['customer' => $customer])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
