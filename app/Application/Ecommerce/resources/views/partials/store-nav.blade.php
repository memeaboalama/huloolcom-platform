{{--
    Shared main navigation.
    Optional: $activeNav = home|categories|merchants|wishlist|cart|account|offers|auth
    'auth' is used by the login page: it keeps the standard items and appends
    the login link as the active entry.
--}}
@php $storeActiveNav = $activeNav ?? 'home'; @endphp
<nav class="nav">
    <ul>
        <li><a class="{{ $storeActiveNav === 'home' ? 'active' : '' }}" href="{{ route('ecommerce.static') }}">@lang('ecommerce::ecommerce.home')</a></li>
        <li><a class="{{ $storeActiveNav === 'categories' ? 'active' : '' }}" href="{{ route('categories.index') }}">@lang('ecommerce::ecommerce.categories.categories')</a></li>
        <li><a class="{{ $storeActiveNav === 'merchants' ? 'active' : '' }}" href="{{ route('merchants.index') }}">@lang('ecommerce::ecommerce.merchants_list')</a></li>
        {{-- [TEMP DISABLED] تبويب العروض الخاصة معلق مؤقتاً
        <li><a class="{{ $storeActiveNav === 'offers' ? 'active' : '' }}" href="{{ route('offers.index') }}">@lang('ecommerce::ecommerce.special_offers')</a></li>
        --}}
        @if($storeActiveNav === 'auth')
            <li><a class="active" href="{{ route('auth.login') }}">@lang('ecommerce::ecommerce.auth.login')</a></li>
        @endif
    </ul>
</nav>
