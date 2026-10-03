<?php

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['auth', 'role:HRD']);
name('karyawan');

?>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Karyawan (HRD)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:employee-manager />
        </div>
    </div>
</x-app-layout>
