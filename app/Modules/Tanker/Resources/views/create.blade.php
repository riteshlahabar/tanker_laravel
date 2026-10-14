@extends('layouts.simple.master')

@section('title', 'Add Tanker')

@section('main_content')
    <x-page-header title="Add Tanker" :trail="['Tankers']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Tanker Details</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.tanker.store') }}" method="POST">
                            @csrf
                            @include('tanker::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
