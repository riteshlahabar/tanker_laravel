@extends('layouts.simple.master')

@section('title', 'Edit Expense')

@section('main_content')
    <x-page-header title="Edit Expense" :trail="['Driver Expenses']" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $expense->driver?->full_name }}</h4>
                        <p class="f-light">
                            Added by {{ $expense->enteredByAdmin() ? 'the office' : 'the driver' }}
                            on {{ $expense->created_at?->format('d M Y') }}.
                        </p>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" action="{{ route('admin.expense.update', $expense) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('expense::_partials.fields', ['expense' => $expense])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
