@extends('layouts.simple.master')

@section('title', 'Add Delivery Slot')

@section('main_content')
    <x-page-header title="Add Delivery Slot" :trail="['Delivery Slots']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Slot Details</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.slot.store') }}" method="POST">
                            @csrf
                            @include('slot::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
