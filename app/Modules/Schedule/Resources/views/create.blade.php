@extends('layouts.simple.master')

@section('title', 'Add Standing Order')

@section('main_content')
    <x-page-header title="Add Standing Order" :trail="['Standing Orders']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-9">
                <div class="card">
                    <div class="card-header">
                        <h4>Routine Details</h4>
                        <p class="f-m-light mt-1 mb-0">
                            The customer does not place these orders — they are created automatically
                            the night before each delivery.
                        </p>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.schedule.store') }}" method="POST">
                            @csrf
                            @include('schedule::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
