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
    @include('admin.includes.header')

    {{-- Floating --}}
    @include('admin.includes.sidebar')

    {{-- Page Content --}}
    @yield('content')

    {{-- Script --}}
    @stack('prepend-script')
    @include('admin.includes.footer')
    @stack('addon-script')

</body>

</html>
