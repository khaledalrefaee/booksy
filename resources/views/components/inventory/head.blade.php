@props(['title', 'subtitle' => null, 'active' => 'products', 'actions' => null])

{{-- Inventory module header: the five inventory sections on top of the generic section-head component. --}}
@php
    $sections = [
        'products'   => ['href' => route('company.inventory.index'),           'icon' => 'package',     'label' => __('Products')],
        'stock'      => ['href' => route('company.inventory.stock'),           'icon' => 'bar-chart-2', 'label' => __('Stock Levels')],
        'movements'  => ['href' => route('company.inventory.movements'),       'icon' => 'repeat',      'label' => __('Movements')],
        'transfers'  => ['href' => route('company.inventory.transfers.index'), 'icon' => 'shuffle',     'label' => __('Transfers')],
        'categories' => ['href' => route('company.product-categories.index'),  'icon' => 'tag',         'label' => __('Categories')],
    ];
@endphp

<x-section-head :title="$title" :subtitle="$subtitle" :tabs="$sections" :active="$active" :actions="$actions" :nav-label="__('Inventory sections')" />
