@extends('layouts.simple.master')

@section('title', 'Settings')

@section('main_content')
    <x-page-header title="Settings" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>General</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.setting.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('setting::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
