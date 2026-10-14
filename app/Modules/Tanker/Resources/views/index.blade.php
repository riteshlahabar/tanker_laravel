@extends('layouts.simple.master')

@section('title', 'Tankers')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/jquery.dataTables.css') }}">
@endsection

@section('main_content')
    <x-page-header title="Tankers" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <x-list-toolbar heading="All Tankers" :create="route('admin.tanker.create')"
                            :export="route('admin.tanker.export')" create-label="Add Tanker" />
                    </div>
                    <div class="card-body">
                        @include('tanker::_partials.filters')
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
    <x-datatable-script table="tanker-table" :export="route('admin.tanker.export')" :filters="['status']" />
@endsection
