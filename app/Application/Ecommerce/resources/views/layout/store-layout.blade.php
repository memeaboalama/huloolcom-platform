<!doctype html>
@php
    $currentLocale = $currentLocale ?? app()->getLocale();
    $isRTL = $isRTL ?? in_array($currentLocale, ['ar', 'he'], true);
@endphp
<html lang="{{ $currentLocale }}" dir="{{ $isRTL ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('ecommerce::ecommerce.home')) - {{ __('ecommerce::ecommerce.site_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/ecommerce/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/ecommerce/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/ecommerce/css/themify.css') }}">
    @if(app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap/css/bootstrap.rtl.min.css') }}"
            id="bootstrap-css">
    @else
        <link rel="stylesheet" href="{{ asset('assets/ecommerce/css/bootstrap.css') }}" id="bootstrap-css">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #20252d;
            --muted: #777d86;
            --line: #e9ebef;
            --yellow: #ffc21c;
            --yellow-deep: #f3ad00;
            --soft: #f7f8f9;
            --shadow: 0 5px 18px rgba(29, 37, 48, .055)
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            color: var(--ink);
            background: #fff;
            font-family: 'Cairo', Tahoma, sans-serif;
            font-size: 13px
        }

        button,
        input,
        select {
            font: inherit
        }

        button {
            border: 0;
            cursor: pointer
        }

        button {
            cursor: pointer;
            border: 0
        }

        .container {
            width: min(1260px, calc(100% - 40px));
            margin: auto
        }

        .topbar {
            height: 74px;
            border-bottom: 1px solid #f0f1f3;
            display: flex;
            align-items: center
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px
        }

        .brand {
            display: flex;
            align-items: center;
            min-width: 172px
        }

        .brand-logo {
            display: block;
            width: 175px;
            height: 76px;
            max-height: 89px;
            object-fit: contain
        }

        .search {
            flex: 1;
            max-width: 535px;
            height: 42px;
            display: flex;
            direction: ltr;
            border: 1px solid #eceef0;
            border-radius: 9px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .025)
        }

        .search input {
            direction: {{ $isRTL ? 'rtl' : 'ltr' }};
            text-align: {{ $isRTL ? 'right' : 'left' }};
            flex: 1;
            border: 0;
            outline: 0;
            padding: 0 18px
        }

        .search button {
            width: 48px;
            background: var(--yellow);
            font-size: 18px
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 18px;
            direction: ltr;
            color: #5d6269;
            flex-direction: row-reverse
        }

        .action {
            position: relative;
            display: grid;
            place-items: center;
            background: transparent;
            color: inherit;
            font-size: 19px;
            padding: 0
        }

        .action.cart b {
            position: absolute;
            right: -9px;
            top: -10px;
            display: grid;
            place-items: center;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: var(--yellow);
            font-size: 10px
        }

        .service,
        .primary-btn {
            background: var(--yellow);
            color: #272727;
            border-radius: 5px;
            font-weight: 700
        }

        .service {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 35px;
            padding: 0 13px;
            border-radius: 5px;
            background: var(--yellow);
            font-size: 11px;
            font-weight: 700;
            gap: 6px
        }

        .language-action {
            position: relative
        }

        .language-toggle {
            width: 24px;
            height: 30px;
            background: transparent;
            color: #5d6269;
            font-size: 19px
        }

        .language-menu {
            display: none;
            position: absolute;
            z-index: 20;
            top: 39px;
            right: 50%;
            min-width: 88px;
            margin: 0;
            padding: 5px 0;
            list-style: none;
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: 0 5px 15px rgba(0, 0, 0, .12)
        }

        .language-action.open .language-menu {
            display: block
        }

        .language-menu button {
            width: 100%;
            background: transparent;
            padding: 6px 12px;
            font-size: 10px;
            text-align: start
        }

        .nav {
            height: 51px;
            position: sticky;
            top: 0;
            z-index: 15;
            background: #fff;
            border-bottom: 1px solid #f3f3f3;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .025)
        }

        .nav ul {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            list-style: none;
            margin: 0;
            padding: 0
        }

        .nav a {
            color: #333;
            font-size: 12px;
            text-decoration: none
        }

        .nav a.active,
        .nav a:hover {
            color: #e4a700;
            font-weight: 700
        }

        footer {
            background: #252b31;
            color: #fff;
            padding: 24px 0 18px;
            margin-top: 0
        }

        .footer-grid {
            display: flex;
            justify-content: center
        }

        .footer-brand {
            text-align: center
        }

        .footer-logo {
            display: block;
            width: 160px;
            max-height: 80px;
            object-fit: contain;
            margin: 0 auto 8px
        }

        .footer-brand p {
            font-size: 14px;
            line-height: 1.8
        }

        .copyright {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-top: 1px solid #3d4348;
            color: #afb4b8;
            font-size: 11px;
            margin-top: 15px;
            padding-top: 10px
        }

        .footer-links {
            display: flex;
            gap: 18px
        }

        .footer-links a,
        .socials a {
            color: #d2d4d6;
            font-size: 10px;
            text-decoration: none
        }

        .socials {
            display: flex;
            gap: 14px
        }

        @media(max-width:800px) {
            .container {
                width: calc(100% - 24px)
            }
        }

        @media(max-width:800px) {
            .topbar {
                height: auto;
                padding: 12px 0
            }
        }

        @media(max-width:800px) {
            .topbar-inner {
                flex-wrap: wrap;
                gap: 10px
            }
        }

        @media(max-width:800px) {
            .brand {
                min-width: 130px
            }
        }

        @media(max-width:800px) {
            .brand-logo {
                width: 120px;
                height: auto
            }
        }

        @media(max-width:800px) {
            .top-actions {
                gap: 12px
            }
        }

        @media(max-width:800px) {
            .search {
                height: 38px;
                flex-basis: 100%;
                max-width: none
            }
        }

        @media(max-width:800px) {
            .nav {
                overflow: auto
            }
        }

        @media(max-width:800px) {
            .nav ul {
                justify-content: flex-start;
                gap: 27px;
                width: max-content;
                padding: 0 10px
            }
        }

        @media(max-width:800px) {
            .copyright {
                flex-wrap: wrap;
                justify-content: center;
                text-align: center
            }
        }

        .top-actions a {
            text-decoration: none;
        }

        .top-actions .action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 36px;
            line-height: 1;
        }

        .top-actions .service {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 35px;
            padding: 0 13px;
        }

        .top-actions .language-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 36px;
            line-height: 1;
        }

        html {
            height: 100%
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column
        }

        .site-main {
            flex: 1 0 auto
        }
    </style>
    @yield('page-styles')
    @include('ecommerce::partials.direction-utils')
</head>

<body>
    @include('ecommerce::partials.store-header')
    @php $storeActiveNav = trim($__env->yieldContent('active-nav', '')) ?: ($activeNav ?? ''); @endphp
    @include('ecommerce::partials.store-nav', ['activeNav' => $storeActiveNav])
    <main class="site-main">
        @yield('content')
    </main>
    @include('ecommerce::partials.store-footer')
    @include('ecommerce::partials.static-auth-modals')
    @yield('scripts')
</body>

</html>