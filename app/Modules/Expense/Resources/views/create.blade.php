@extends('layouts.simple.master')

@section('title', 'Add Expense')

@section('main_content')
    <x-page-header title="Add Expense" :trail="['Driver Expenses']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Expense Details</h4>
                        <p class="f-light">Record what a driver spent when they have not entered it themselves.</p>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.expense.store') }}" method="POST">
                            @csrf
                            @include('expense::_partials.fields')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
