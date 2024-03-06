<!DOCTYPE html>
<html lang="en">
<head>
  @include('partials.header')
</head>
<body class="min-h-full bg-mainwhitebg font-poppins">
    <x-message />
  <section class="flex flex-row w-full h-auto text-mainwhitebg">
    @include('partials.admin_head')
    @include('partials.sidebar')
    @yield('content')
  
  </section>


@include('partials.footer')