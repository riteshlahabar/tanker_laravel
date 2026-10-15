@extends('layouts.simple.master')

@section('title', 'Standing Order')

@section('main_content')
    <x-page-header :title="$schedule->customer?->full_name ?? 'Standing Order'" :trail="['Standing Orders']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-5">
                @include('schedule::_partials.summary-card')
                @include('schedule::_partials.pause-card')
            </div>
            <div class="col-xl-7">
                @include('schedule::_partials.upcoming-card')
                @include('schedule::_partials.skips-card')
                @include('schedule::_partials.orders-card')
            </div>
        </div>
    </div>
@endsection
