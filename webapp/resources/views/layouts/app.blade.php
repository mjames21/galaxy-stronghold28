{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name', 'StrongHold28'))</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
  <div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-72 bg-white border-r flex flex-col">
      <div class="h-16 px-4 flex items-center justify-between border-b">
        <a href="{{ route('dashboard') }}" class="font-semibold text-lg">StrongHold28</a>
      </div>

      <nav class="flex-1 py-4 text-sm">
  {{-- Common --}}
  <div class="mt-4 px-4 text-xs uppercase tracking-wide text-gray-500">EMC Executive</div>

  <a href="{{ route('dashboard') }}"
     class="block px-4 py-2 hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-200 font-semibold' : '' }}">
    Insights
  </a>

  <a href="{{ route('forecast') }}"
     class="block px-4 py-2 hover:bg-gray-100 {{ request()->routeIs('forecast') ? 'bg-gray-200 font-semibold' : '' }}">
    Forecast
  </a>

  <a href="{{ route('seats') }}"
     class="block px-4 py-2 hover:bg-gray-100 {{ request()->routeIs('seats') ? 'bg-gray-200 font-semibold' : '' }}">
    Seat Projection
  </a>

  <a href="{{ route('gotv') }}"
     class="block px-4 py-2 hover:bg-gray-100 {{ request()->routeIs('gotv') ? 'bg-gray-200 font-semibold' : '' }}">
    GOTV Lab
  </a>

  <a href="{{ route('scenarios') }}"
     class="block px-4 py-2 hover:bg-gray-100 {{ request()->routeIs('scenarios') ? 'bg-gray-200 font-semibold' : '' }}">
    Scenarios
  </a>

  <a href="{{ route('pvt') }}"
     class="block px-4 py-2 hover:bg-gray-100 {{ request()->routeIs('pvt') ? 'bg-gray-200 font-semibold' : '' }}">
    PVT
  </a>

</nav>

        </nav>
    </aside>

  
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col bg-white">
            {{-- Topbar --}}
            @livewire('navigation-menu')

            {{-- Page Content --}}
            <main class="flex-1 bg-gray-50 px-6 py-4">
                {{ $slot }}
            </main>

            {{-- Footer --}}
            <footer class="bg-white border-t px-6 py-4 text-sm text-gray-500">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <span class="mb-2 md:mb-0">
                        © {{ date('Y') }} <a href="#" class="hover:underline text-gray-600">StrongHold28</a>. All Rights Reserved.
                    </span>

                    <ul class="flex space-x-4">
                        <li><a href="/privacy-policy" class="hover:underline text-gray-500">Privacy Policy</a></li>
                    </ul>
                </div>
            </footer>
        </div>
    </div>

    @livewireScripts
    @stack('modals')
</body>
</html>