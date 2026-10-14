@extends('layouts.simple.master')

@section('title', 'Edit Driver')

@section('main_content')
    <x-page-header title="Edit Driver" :trail="['Drivers']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $driver->full_name }}</h4>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.driver.tanker.form', $driver) }}">
                            Change Tanker
                        </a>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.driver.update', $driver) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('driver::_partials.fields', ['driver' => $driver])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
