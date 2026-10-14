@extends('layouts.simple.master')

@section('title', 'Add Driver')

@section('main_content')
    <x-page-header title="Add Driver" :trail="['Drivers']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Driver Details</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.driver.store') }}" method="POST">
                            @csrf
                            @include('driver::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
