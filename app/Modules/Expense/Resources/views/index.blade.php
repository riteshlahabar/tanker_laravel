@extends('layouts.simple.master')

@section('title', 'Driver Expenses')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/jquery.dataTables.css') }}">
@endsection

@section('main_content')
    <x-page-header title="Driver Expenses" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <x-list-toolbar heading="Driver Expenses" :create="route('admin.expense.create')"
                            :export="route('admin.expense.export')" create-label="Add Expense" />
                    </div>
                    <div class="card-body">
                        @include('expense::_partials.filters')
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
    <x-datatable-script table="expense-table" :export="route('admin.expense.export')"
        :filters="['driver_id', 'category', 'from', 'to']" />
@endsection
