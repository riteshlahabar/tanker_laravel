@extends('layouts.simple.master')

@section('title', 'Change Tanker')

@section('main_content')
    <x-page-header title="Change Tanker" :trail="['Drivers', $driver->full_name]" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $driver->full_name }}</h4>
                        <span class="f-light">Currently:
                            {{ $driver->currentAssignment?->tanker?->label() ?? 'no tanker assigned' }}</span>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.driver.tanker.update', $driver) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            @include('driver::_partials.change-tanker-fields')
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                @include('driver::_partials.assignment-history')
            </div>
        </div>
    </div>
@endsection
