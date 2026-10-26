@extends('layouts.simple.master')

@section('title', 'Filling Points')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/jquery.dataTables.css') }}">
@endsection

@section('main_content')
    <x-page-header title="Filling Points" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <x-list-toolbar heading="Filling Points" :create="route('admin.spot.create')"
                            :export="route('admin.spot.export')" create-label="Add Filling Point" />
                    </div>
                    <div class="card-body">
                        @include('spot::_partials.filters')
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
    <x-datatable-script table="spot-table" :export="route('admin.spot.export')"
        :filters="['status', 'is_available']" />
@endsection
