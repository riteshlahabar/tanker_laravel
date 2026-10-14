@extends('layouts.simple.master')

@section('title', 'Edit Tanker')

@section('main_content')
    <x-page-header title="Edit Tanker" :trail="['Tankers']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $tanker->label() }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.tanker.update', $tanker) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('tanker::_partials.fields', ['tanker' => $tanker])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
