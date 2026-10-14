@extends('layouts.simple.master')

@section('title', 'WhatsApp Log')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/jquery.dataTables.css') }}">
@endsection

@section('main_content')
    <x-page-header title="WhatsApp Log" />

    <div class="container-fluid">
        @include('notification::_partials.driver-notice')

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">Outbound Messages</h4>
                            @include('notification::_partials.send-now-form')
                        </div>
                    </div>
                    <div class="card-body">
                        @include('notification::_partials.filters')
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
    <x-datatable-script table="whatsapp-table" :filters="['status', 'template']" />
@endsection
