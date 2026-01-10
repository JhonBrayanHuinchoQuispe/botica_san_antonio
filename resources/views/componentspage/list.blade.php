@extends('layout.layout')
@php
    $title='List';
    $subTitle = 'Components / List';

@endphp

@section('content')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card h-full p-0 border-0 overflow-hidden">
            <div class="card-header border-b border-neutral-200 bg-white py-4 px-6">
                <h6 class="text-lg font-semibold mb-0">Default List</h6>
            </div>
            <div class="card-body p-6">
                <ul class="rounded-lg border border-neutral-200 overflow-hidden">
                    <li class="text-secondary-light p-4 bg-neutral-50 border-b border-neutral-200">1. This is list trust fund seitan letterpress, keytar raw denim keffiye</li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">2. This is list trust fund seitan letterpress, keytar raw denim </li>
                    <li class="text-secondary-light p-4 bg-neutral-50 border-b border-neutral-200">3. This is list trust fund seitan letterpress, keytar raw </li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">4. This is list trust fund seitan letterpress, keytar raw denim keffiye</li>
                    <li class="text-secondary-light p-4 bg-neutral-50">5. This is list trust fund seitan letterpress, keytar raw denim </li>
                </ul>
            </div>
        </div>
        <div class="card h-full p-0 border-0 overflow-hidden">
            <div class="card-header border-b border-neutral-200 bg-white py-4 px-6">
                <h6 class="text-lg font-semibold mb-0">Active List</h6>
            </div>
            <div class="card-body p-6">
                <ul class="rounded-lg border border-neutral-200 overflow-hidden">
                    <li class="text-secondary-light p-4 bg-primary-600 border-b border-neutral-200 text-white">1. This is list trust fund seitan letterpress, keytar raw denim keffiye</li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">2. This is list trust fund seitan letterpress, keytar raw denim </li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">3. This is list trust fund seitan letterpress, keytar raw </li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">4. This is list trust fund seitan letterpress, keytar raw denim keffiye</li>
                    <li class="text-secondary-light p-4 bg-white">5. This is list trust fund seitan letterpress, keytar raw denim </li>
                </ul>
            </div>
        </div>
        <div class="card h-full p-0 border-0 overflow-hidden">
            <div class="card-header border-b border-neutral-200 bg-white py-4 px-6">
                <h6 class="text-lg font-semibold mb-0">Active List</h6>
            </div>
            <div class="card-body p-6">
                <ul class="rounded-lg border border-neutral-200 overflow-hidden">
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="ci:bell-notification" class="text-xl"></iconify-icon></span>
                            Push Notification (This is push notifications)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="mynaui:cart-check" class="text-xl"></iconify-icon></span>
                            New Orders confirmed (This is Orders confirmed)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="mdi:security-lock-outline" class="text-xl"></iconify-icon></span>
                            Security Access (This is Security Access)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="tabler:folder-open" class="text-xl"></iconify-icon></span>
                            Storage Folder (This is Storage Folder)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-white">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="flowbite:forward-outline" class="text-xl"></iconify-icon></span>
                            Forward Message (This is Forward Message)
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card h-full p-0 border-0 overflow-hidden">
            <div class="card-header border-b border-neutral-200 bg-white py-4 px-6">
                <h6 class="text-lg font-semibold mb-0">List Icons & label</h6>
            </div>
            <div class="card-body p-6">
                <ul class="rounded-lg border border-neutral-200 overflow-hidden">
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="ci:bell-notification" class="text-xl"></iconify-icon></span>
                            Push Notification (This is push notifications)
                        </div>
                        <span class="text-xs bg-danger-100 text-danger-600 rounded px-2.5 py-1 font-semibold">Low</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="mynaui:cart-check" class="text-xl"></iconify-icon></span>
                            New Orders confirmed (This is Orders confirmed)
                        </div>
                        <span class="text-xs bg-success-100 text-success-600 rounded px-2.5 py-1 font-semibold">High</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="mdi:security-lock-outline" class="text-xl"></iconify-icon></span>
                            Security Access (This is Security Access)
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-600 rounded px-2.5 py-1 font-semibold">Medium</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="tabler:folder-open" class="text-xl"></iconify-icon></span>
                            Storage Folder (This is Storage Folder)
                        </div>
                        <span class="text-xs bg-danger-100 text-danger-600 rounded px-2.5 py-1 font-semibold">Low</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white">
                        <div class="flex items-center gap-2">
                            <span class="flex"><iconify-icon icon="flowbite:forward-outline" class="text-xl"></iconify-icon></span>
                            Forward Message (This is Forward Message)
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-600 rounded px-2.5 py-1 font-semibold">Medium</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card h-full p-0 border-0 overflow-hidden">
            <div class="card-header border-b border-neutral-200 bg-white py-4 px-6">
                <h6 class="text-lg font-semibold mb-0">Colored Lists</h6>
            </div>
            <div class="card-body p-6">
                <ul class="rounded-lg border border-neutral-200 overflow-hidden">
                    <li class="text-secondary-light p-4 bg-success-100 text-success-600 border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img1.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Push Notification (This is push notifications)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-info-100 text-info-600 border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img2.png') }}" class="w-8 h-8 rounded-full" alt="">
                            New Orders confirmed (This is Orders confirmed)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-purple-100 text-purple-600 border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img3.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Security Access (This is Security Access)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-warning-100 text-warning-600 border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img4.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Storage Folder (This is Storage Folder)
                        </div>
                    </li>
                    <li class="text-secondary-light p-4 bg-danger-100 text-danger-600">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img5.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Forward Message (This is Forward Message)
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card h-full p-0 border-0 overflow-hidden">
            <div class="card-header border-b border-neutral-200 bg-white py-4 px-6">
                <h6 class="text-lg font-semibold mb-0">List Icons &amp; label</h6>
            </div>
            <div class="card-body p-6">
                <ul class="rounded-lg border border-neutral-200 overflow-hidden">
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img1.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Push Notification (This is push notifications)
                        </div>
                        <span class="text-xs bg-danger-100 text-danger-600 rounded px-2.5 py-1 font-semibold">Low</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img2.png') }}" class="w-8 h-8 rounded-full" alt="">
                            New Orders confirmed (This is Orders confirmed)
                        </div>
                        <span class="text-xs bg-success-100 text-success-600 rounded px-2.5 py-1 font-semibold">High</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img3.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Security Access (This is Security Access)
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-600 rounded px-2.5 py-1 font-semibold">Medium</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white border-b border-neutral-200">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img4.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Storage Folder (This is Storage Folder)
                        </div>
                        <span class="text-xs bg-danger-100 text-danger-600 rounded px-2.5 py-1 font-semibold">Low</span>
                    </li>
                    <li class="flex items-center justify-between text-secondary-light p-4 bg-white">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('assets/images/lists/list-img5.png') }}" class="w-8 h-8 rounded-full" alt="">
                            Forward Message (This is Forward Message)
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-600 rounded px-2.5 py-1 font-semibold">Medium</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

@endsection 
