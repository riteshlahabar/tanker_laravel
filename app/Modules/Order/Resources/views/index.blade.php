@extends('layouts.simple.master')

@section('title', 'Orders')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/jquery.dataTables.css') }}">
@endsection

@section('main_content')
    <x-page-header title="Orders" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <x-list-toolbar heading="All Orders" :create="route('admin.order.create')"
                            :export="route('admin.order.export')" create-label="New Order" />
                    </div>
                    <div class="card-body">
                        @include('order::_partials.filters')
                        <div class="table-responsive custom-scrollbar">
                            {!! $dataTable->table(['class' => 'display table table-striped w-100']) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/datatable/datatables/jquery.dataTables.min.js') }}"></script>
    {!! $dataTable->scripts() !!}
    <x-datatable-script table="order-table" :export="route('admin.order.export')"
        :filters="['status', 'driver_id', 'from', 'to']" />
@endsection
