@extends('layouts.simple.master')

@section('title', $report->title())

@section('main_content')
    <x-page-header :title="$report->title()" :trail="['Reports']" />

    <div class="container-fluid">
        @include('report::_partials.totals')

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header card-no-border">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ $filters->label() }}</h4>
                            <a class="btn btn-outline-primary" href="{{ $exportUrl }}">
                                <i data-feather="download"></i> Export Excel
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('report::_partials.filter-form')
                        @include('report::_partials.table')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
