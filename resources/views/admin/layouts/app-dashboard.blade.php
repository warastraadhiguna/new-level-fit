<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="keywords" content="HTML5 Template">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    {{-- Style --}}
    @stack('prepend-style')
    @include('admin.includes.head-dashboard')
    @stack('addon-style')

</head>

<body>
    {{-- Header --}}
    @include('admin.includes.header-dashboard')

    {{-- Floating --}}
    @include('admin.includes.sidebar')

    {{-- Notify --}}
    {{-- @include('sweetalert::alert')
    @include('notify::messages')
    <x:notify-messages />
    @notifyJs --}}

    {{-- Page Content --}}
    @yield('content')

    {{-- Footer --}}
    @include('admin.includes.wallet-sidebar')

    {{-- Last Footer --}}
    {{-- @include('user.includes.last-footer') --}}

    {{-- Script --}}
    @stack('prepend-script')
    @include('admin.includes.footer-dashboard')
    @stack('addon-script')

</body>

</html>
