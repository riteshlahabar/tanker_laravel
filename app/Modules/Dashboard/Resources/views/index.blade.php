@extends('layouts.simple.master')

@section('title', 'Dashboard')

@section('main_content')
    <x-page-header title="Dashboard" />

    <div class="container-fluid">
        <div class="row">
            <x-stat-tile label="Orders Today" :value="$summary['orders_today']" icon="stroke-ecommerce"
                colour="primary" />
            <x-stat-tile label="Pending" :value="$summary['pending']" icon="fill-coming-soon" colour="warning" />
            <x-stat-tile label="Delivered Today" :value="$summary['delivered_today']" icon="stroke-delivered"
                colour="success" />
            <x-stat-tile label="Revenue Today"
                :value="'₹ ' . number_format($summary['revenue_today'], 2)" icon="stroke-price" colour="secondary" />
        </div>

        <div class="row">
            <x-stat-tile label="Tankers Delivered Today" :value="$summary['tankers_delivered_today']"
                icon="stroke-package" colour="primary" />
            <x-stat-tile label="Active Tankers" :value="$summary['tankers']" icon="stroke-progress-delivery"
                colour="secondary" />
            <x-stat-tile label="Drivers" :value="$summary['drivers']" icon="fill-user" colour="success" />
            <x-stat-tile label="Customers" :value="$summary['customers']" icon="fill-contact" colour="warning" />
        </div>

        <div class="row">
            {{-- Generated at 00:05, so this is what the morning's driver
                 assignments have to cover. --}}
            <x-stat-tile label="Scheduled Tomorrow" :value="$summary['scheduled_tomorrow']"
                icon="stroke-calendar" colour="primary" />
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('dashboard::_partials.open-orders')
            </div>
        </div>
    </div>
@endsection
