@extends('layouts.simple.master')

@section('title', 'Standing Orders')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/jquery.dataTables.css') }}">
@endsection

@section('main_content')
    <x-page-header title="Standing Orders" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <x-list-toolbar heading="Fixed-Schedule Customers"
                            :create="route('admin.schedule.create')"
                            :export="route('admin.schedule.export')"
                            create-label="Add Standing Order" />
                    </div>
                    <div class="card-body">
                        @include('schedule::_partials.generate-form')
                        @include('schedule::_partials.filters')
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
    <x-datatable-script table="schedule-table" :export="route('admin.schedule.export')"
        :filters="['status', 'paused', 'customer_id']" />
@endsection
