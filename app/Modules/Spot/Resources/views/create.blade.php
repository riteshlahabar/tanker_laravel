@extends('layouts.simple.master')

@section('title', 'Add Filling Point')

@section('main_content')
    <x-page-header title="Add Filling Point" :trail="['Filling Points']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Filling Point Details</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.spot.store') }}" method="POST">
                            @csrf
                            @include('spot::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
