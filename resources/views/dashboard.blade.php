<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in as ") }} <strong>{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</strong>!
                </div>
            </div>

            @role('Karyawan')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <livewire:attendance />
                </div>
                
                <livewire:leave-request />
            @endrole

            @role('HRD')
                <livewire:hrd-dashboard />
                <livewire:leave-approvals />
            @endrole
        </div>
    </div>
</x-app-layout>
