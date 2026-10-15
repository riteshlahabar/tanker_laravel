@extends('layouts.simple.master')

@section('title', 'Edit Standing Order')

@section('main_content')
    <x-page-header title="Edit Standing Order" :trail="['Standing Orders']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-9">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $schedule->customer?->full_name }}</h4>
                        <p class="f-m-light mt-1 mb-0">
                            Changes apply to future deliveries only — orders already generated are untouched.
                        </p>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.schedule.update', $schedule) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('schedule::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
