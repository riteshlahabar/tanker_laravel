@extends('layouts.simple.master')

@section('title', 'Dashboard')

@section('main_content')
    <x-page-header title="Dashboard" />

    <div class="container-fluid">
        <div class="row">
            <x-stat-tile label="Orders Today" :value="$summary['orders_today']" icon="stroke-ecommerce"
                colour="primary" />
            <x-stat-tile label="Pending" :value="$summary['pending']" icon="stroke-task" colour="warning" />
            <x-stat-tile label="Delivered Today" :value="$summary['delivered_today']" icon="stroke-reports"
                colour="success" />
            <x-stat-tile label="Revenue Today"
                :value="'₹ ' . number_format($summary['revenue_today'], 2)" icon="stroke-charts" colour="secondary" />
        </div>

        <div class="row">
            <x-stat-tile label="Driver Earnings Today"
                :value="'₹ ' . number_format($summary['driver_earning_today'], 2)" icon="stroke-user"
                colour="primary" />
            <x-stat-tile label="Active Tankers" :value="$summary['tankers']" icon="stroke-task" colour="secondary" />
            <x-stat-tile label="Drivers" :value="$summary['drivers']" icon="stroke-user" colour="success" />
            <x-stat-tile label="Customers" :value="$summary['customers']" icon="stroke-contact" colour="warning" />
        </div>

        <div class="row">
            {{-- Generated at 00:05, so this is what the morning's driver
                 assignments have to cover. --}}
            <x-stat-tile label="Scheduled Tomorrow" :value="$summary['scheduled_tomorrow']"
                icon="stroke-to-do" colour="primary" />
        </div>
    </div>
@endsection
