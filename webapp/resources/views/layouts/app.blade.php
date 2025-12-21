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
      {{-- Brand / Logo --}}
      <div class="h-16 px-4 flex items-center justify-between border-b">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
            SH
          </div>
          <div>
            <div class="font-semibold text-lg tracking-tight">Stronghold 28</div>
            <div class="text-[11px] text-slate-500">Election Management Console</div>
          </div>
        </a>
      </div>

      {{-- Navigation --}}
      <nav class="flex-1 py-4 text-sm overflow-y-auto">

        {{-- EMC Executive section --}}
        <div class="px-4 mt-2 mb-1 text-[11px] uppercase tracking-wide text-slate-500">
          EMC Executive
        </div>

        {{-- Dashboard / Insights --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('dashboard') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 13h6v8H3zM9 3h6v18H9zM15 9h6v12h-6z" />
          </svg>
          <span>Insights</span>
        </a>

        {{-- Forecast --}}
        <a href="{{ route('forecast') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('forecast') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19l5-6 4 3 7-9" />
            <path d="M4 5h4v4H4z" />
            <path d="M10 11h4v4h-4z" />
            <path d="M16 5h4v4h-4z" />
          </svg>
          <span>Forecast</span>
        </a>

        {{-- Seat Projection --}}
        <a href="{{ route('seats') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('seats') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 20h16" />
            <path d="M6 20V9a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v11" />
            <path d="M8 7V5a4 4 0 0 1 8 0v2" />
          </svg>
          <span>Seat Projection</span>
        </a>

        {{-- GOTV Lab --}}
        <a href="{{ route('gotv') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('gotv') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 11l4 4L19 5" />
            <circle cx="5" cy="19" r="2" />
            <circle cx="12" cy="19" r="2" />
            <circle cx="19" cy="19" r="2" />
          </svg>
          <span>GOTV Lab</span>
        </a>

        {{-- Scenario Lab --}}
        <a href="{{ route('scenarios') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('scenarios') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 4h7v7H4z" />
            <path d="M13 4h7v7h-7z" />
            <path d="M4 13h7v7H4z" />
            <path d="M17 13l3 3-3 3-3-3 3-3z" />
          </svg>
          <span>Scenario Lab</span>
        </a>

        {{-- PVT --}}
        <a href="{{ route('pvt') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('pvt') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 3L4 8v8l8 5 8-5V8z" />
            <path d="M9 14l2 2 4-4" />
          </svg>
          <span>PVT Verifier</span>
        </a>

        {{-- Divider --}}
        <div class="mt-6 mb-1 px-4 text-[11px] uppercase tracking-wide text-slate-500">
          EMC Data Team
        </div>

        {{-- Districts --}}
        <a href="{{ route('manage.districts') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.districts') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 4h7v7H4z" />
            <path d="M13 4h7v7h-7z" />
            <path d="M4 13h7v7H4z" />
            <path d="M13 13h7v7h-7z" />
          </svg>
          <span>Districts</span>
        </a>

        {{-- Populations --}}
        <a href="{{ route('manage.populations') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.populations') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M8 7a3 3 0 1 1 6 0v1H8z" />
            <path d="M5 20v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2" />
            <circle cx="5" cy="9" r="2" />
            <circle cx="19" cy="9" r="2" />
          </svg>
          <span>Populations</span>
        </a>

        {{-- Elections --}}
        <a href="{{ route('manage.elections') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.elections') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 4h14v4H5z" />
            <path d="M6 8v12h12V8" />
            <path d="M9 4V2" />
            <path d="M15 4V2" />
            <path d="M9 12h6" />
          </svg>
          <span>Elections</span>
        </a>
    <a href="{{ route('manage.results') }}"
   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100
          {{ request()->routeIs('manage.results') ? 'bg-gray-200 font-semibold text-gray-900' : 'text-gray-700' }}">
    {{-- Bar chart icon --}}
    <svg class="w-4 h-4 {{ request()->routeIs('manage.results') ? 'text-gray-900' : 'text-gray-400' }}"
         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M4.5 19.5h15M7.5 17.25V9.75M12 17.25V6.75M16.5 17.25V12.75" />
    </svg>

    <span>Results</span>
</a>



        {{-- Results Import --}}
        <a href="{{ route('manage.results.import') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.results.import') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 4h16v6H4z" />
            <path d="M4 14h4v6H4z" />
            <path d="M10 14h4v6h-4z" />
            <path d="M16 14h4v6h-4z" />
          </svg>
          <span>Results Import</span>
        </a>

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
            © {{ date('Y') }} <a href="#" class="hover:underline text-gray-600">Stronghold 28</a>. All Rights Reserved.
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
