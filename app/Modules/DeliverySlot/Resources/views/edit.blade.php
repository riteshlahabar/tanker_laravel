@extends('layouts.simple.master')

@section('title', 'Edit Delivery Slot')

@section('main_content')
    <x-page-header title="Edit Delivery Slot" :trail="['Delivery Slots']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $slot->label() }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.slot.update', $slot) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('slot::_partials.fields', ['slot' => $slot])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
