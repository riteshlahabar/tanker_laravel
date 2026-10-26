<?php

declare(strict_types=1);

/*
 * Admin sidebar menu. Rendered by resources/views/layouts/simple/sidebar.blade.php
 * using Cuba's markup — add a module here rather than editing the Blade.
 *
 * icon      : name in public/assets/svg/icon-sprite.svg (without the stroke-/fill- prefix)
 * route     : named route; rendered as "#" until that route exists
 * children  : submenu entries
 */

return [
    [
        'title' => 'Dashboard',
        'icon' => 'home',
        'route' => 'admin.dashboard',
    ],
    [
        'title' => 'Tankers',
        'icon' => 'task',
        'route' => 'admin.tanker.index',
    ],
    [
        'title' => 'Drivers',
        'icon' => 'user',
        'route' => 'admin.driver.index',
    ],
    [
        'title' => 'Customers',
        'icon' => 'contact',
        'route' => 'admin.customer.index',
    ],
    [
        'title' => 'Filling Points',
        'icon' => 'maps',
        'route' => 'admin.spot.index',
    ],
    [
        'title' => 'Orders',
        'icon' => 'ecommerce',
        'route' => 'admin.order.index',
    ],
    [
        'title' => 'Standing Orders',
        'icon' => 'to-do',
        'route' => 'admin.schedule.index',
    ],
    [
        'title' => 'Delivery Slots',
        'icon' => 'coming-soon',
        'route' => 'admin.slot.index',
    ],
    [
        // 'charts' rather than a wallet icon: the sprite has no money icon,
        // and every menu icon must exist as both stroke- and fill- in
        // public/assets/svg/icon-sprite.svg.
        'title' => 'Driver Expenses',
        'icon' => 'charts',
        'route' => 'admin.expense.index',
    ],
    [
        'title' => 'Reports',
        'icon' => 'reports',
        'children' => [
            ['title' => 'Driver Salary', 'route' => 'admin.report.driver-salary'],
            ['title' => 'Daily Delivery', 'route' => 'admin.report.daily-delivery'],
            ['title' => 'Spot Refills', 'route' => 'admin.report.spot-refill'],
            ['title' => 'Customer Orders', 'route' => 'admin.report.customer-order'],
            ['title' => 'Tanker Utilisation', 'route' => 'admin.report.tanker-utilisation'],
            ['title' => 'Schedule Adherence', 'route' => 'admin.report.schedule-adherence'],
            ['title' => 'Driver Expenses', 'route' => 'admin.report.driver-expense'],
        ],
    ],
    [
        'title' => 'Settings',
        'icon' => 'form',
        'route' => 'admin.setting.edit',
    ],
    [
        'title' => 'WhatsApp Log',
        'icon' => 'chat',
        'route' => 'admin.whatsapp.index',
    ],
    [
        'title' => 'WhatsApp Scan',
        'icon' => 'chat',
        'route' => 'admin.whatsapp.scan',
    ],
];
