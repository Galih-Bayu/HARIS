<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 flex items-center justify-center min-h-screen relative" style="background-image: url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'); background-size: cover; background-position: center;">
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-blue-900 bg-opacity-70"></div>

        <div class="relative z-10 w-full sm:max-w-md px-6 py-8 bg-white shadow-2xl overflow-hidden sm:rounded-xl">
            <div class="flex flex-col items-center mb-6">
                <a href="/" wire:navigate>
                    <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </a>
                <h1 class="mt-4 text-2xl font-bold text-gray-800">HRIS Lite</h1>
                <p class="text-sm text-gray-500">Smart Attendance & Leave Management</p>
            </div>

            {{ $slot }}
        </div>
    </body>
</html>
