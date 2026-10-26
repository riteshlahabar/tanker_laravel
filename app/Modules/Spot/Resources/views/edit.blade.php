@extends('layouts.simple.master')

@section('title', 'Edit Filling Point')

@section('main_content')
    <x-page-header title="Edit Filling Point" :trail="['Filling Points']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $spot->name }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.spot.update', $spot) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('spot::_partials.fields', ['spot' => $spot])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
