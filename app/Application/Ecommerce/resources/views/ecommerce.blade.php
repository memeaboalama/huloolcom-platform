@extends('ecommerce::layout.store-layout')

{{-- @section bodies are evaluated BEFORE the parent layout runs, so anything the
     sections need must be resolved here, not in store-layout. --}}
@php
    $currentLocale = $currentLocale ?? app()->getLocale();
    $isRTL = $isRTL ?? in_array($currentLocale, ['ar', 'he', 'fa', 'ur'], true);
@endphp

@section('title', __('ecommerce::ecommerce.home'))

@section('active-nav', 'home')

@section('page-styles')
    <style>
        .hero {
            height: 300px;
            margin-top: 22px;
            border-radius: 11px;
            overflow: hidden;
            position: relative;
            background: #f7f3ea
        }

        .hero-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            visibility: hidden;
            background-position: center;
            background-size: cover;
            transition: opacity .55s ease
        }

        .hero-slide.active {
            opacity: 1;
            visibility: visible
        }

        .hero-slide:after {
            content: '';
            position: absolute;
            inset: 0 0 0 auto;
            width: 43%;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, .42))
        }

        .hero-content {
            position: absolute;
            right: 53px;
            top: 34px;
            width: 410px;
            text-align: start;
            z-index: 2
        }

        .hero h1 {
            margin: 0;
            font-size: 31px;
            line-height: 1.35;
            font-weight: 800
        }

        .hero h1 span {
            color: var(--yellow-deep);
            display: block
        }

        .hero p {
            color: #626870;
            line-height: 2;
            margin: 8px 0 14px;
            font-size: 14px
        }

        .primary-btn {
            border-radius: 22px;
            padding: 11px 23px;
            font-size: 12px;
            box-shadow: 0 4px 9px rgba(246, 181, 0, .16)
        }

        .hero-arrows {
            position: absolute;
            bottom: 13px;
            left: 13px;
            display: flex;
            gap: 8px;
            direction: ltr;
            z-index: 2
        }

        .circle-btn {
            width: 22px;
            height: 22px;
            padding: 0;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .16)
        }

        .dots {
            position: absolute;
            bottom: 13px;
            left: 50%;
            display: flex;
            gap: 6px;
            direction: ltr;
            z-index: 2
        }

        .dot {
            width: 8px;
            height: 8px;
            border: 0;
            padding: 0;
            border-radius: 50%;
            background: #a9adb2
        }

        .dot.active {
            background: var(--yellow)
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 0;
            padding: 14px 12px 12px;
            margin-top: 15px;
            border: 1px solid #edf0f2;
            border-radius: 9px;
            box-shadow: 0 3px 12px rgba(29, 37, 48, .045)
        }

        .category {
            text-align: center;
            text-decoration: none;
            color: #343a42;
            border-left: 1px solid #f0f1f3
        }

        .category:last-child {
            border-left: 0
        }

        .category-icon {
            width: 40px;
            height: 40px;
            margin: 0 auto 6px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #fff4dc;
            color: #f0ac00
        }

        .category-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round
        }

        .category span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 600
        }

        .promo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 17px;
            margin-top: 21px
        }

        .promo {
            height: 160px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            padding: 22px 19px;
            background-size: cover;
            background-position: center
        }

        .promo-content {
            position: relative;
            z-index: 1;
            width: 55%
        }

        .promo h3 {
            margin: 0 0 7px;
            font-size: 13px;
            line-height: 1.6
        }

        .promo p {
            margin: 0 0 10px;
            font-size: 12px
        }

        .small-btn {
            background: #fff;
            padding: 7px 15px;
            border-radius: 15px;
            font-size: 10px
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0 13px
        }

        .section-head h2 {
            margin: 0;
            font-size: 15px;
            padding-right: 12px;
            border-right: 3px solid var(--yellow)
        }

        .view-all {
            color: #e6ad00;
            text-decoration: none;
            font-size: 10px
        }

        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px
        }

        .product {
            min-height: 235px;
            border: 1px solid #e8ebee;
            border-radius: 8px;
            padding: 12px 13px 10px;
            box-shadow: var(--shadow);
            position: relative;
            text-align: start
        }

        .favorite {
            background: transparent;
            border: 0;
            color: #9da5ad;
            position: absolute;
            z-index: 2;
            top: 9px;
            left: 9px;
            width: 22px;
            height: 22px;
            padding: 0;
            cursor: pointer;
            font-size: 16px
        }

        .product-image {
            height: 132px;
            width: 100%;
            object-fit: contain;
            display: block
        }

        .product h3 {
            font-size: 14px;
            margin: 2px 0 4px
        }

        .rating {
            font-size: 16px;
            color: #ffba00;
            direction: ltr;
            unicode-bidi: isolate
        }

        .rating span {
            color: #777;
            margin-inline-start: 4px
        }

        .price {
            font-weight: 700;
            font-size: 14px;
            direction: ltr;
            unicode-bidi: isolate;
            text-align: start;
            margin-top: 1px
        }

        .add-cart {
            width: 100%;
            height: 33px;
            margin-top: 8px;
            border-radius: 18px;
            color: #5d4800;
            background: var(--yellow);
            font-size: 12px;
            font-weight: 700
        }

        .wide-banner {
            height: 150px;
            border-radius: 8px;
            margin-top: 18px;
            background-position: center;
            background-size: cover;
            position: relative;
            padding: 13px 70px
        }

        .wide-banner-content {
            position: absolute;
            right: 9%;
            top: 50%;
            width: 310px;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            transform: translateY(-50%)
        }

        .wide-banner h2 {
            margin: 0;
            font-size: 19px
        }

        .wide-banner p {
            margin: 0;
            color: #7b8086;
            font-size: 13px
        }

        .wide-banner .primary-btn {
            padding: 5px 18px;
            font-size: 10px
        }

        .benefits {
            margin-top: 15px;
            padding: 12px 30px;
            background: #fafbfc;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            text-align: center;
            gap: 15px
        }

        .benefit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px
        }

        .benefit-icon {
            font-size: 22px
        }

        .benefit strong {
            display: block;
            font-size: 16px
        }

        .benefit span {
            display: block;
            font-size: 14px;
            color: #858a90;
            margin-top: 3px
        }

        .extra-section {
            padding: 25px 0
        }

        .stores-section {
            padding: 28px 0 34px
        }

        .stores-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px
        }

        .stores-heading h2 {
            margin: 0;
            padding-right: 12px;
            border-right: 3px solid var(--yellow);
            font-size: 19px;
            font-weight: 400
        }

        .stores-heading a {
            color: #e6ad00;
            font-size: 11px;
            text-decoration: none
        }

        .stores-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 28px;
            max-width: 820px;
            margin: auto
        }

        .store-card {
            overflow: hidden;
            border: 1px solid #e5e8eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 6px 18px rgba(29, 37, 48, .06)
        }

        .store-cover {
            height: 150px;
            position: relative;
            background-position: center;
            background-size: cover
        }

        .trusted-badge {
            position: absolute;
            top: 12px;
            right: 14px;
            padding: 4px 9px;
            border-radius: 14px;
            background: #fff;
            color: #3bbb4a;
            font-size: 10px
        }

        .store-info {
            padding: 14px 18px 18px
        }

        .store-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px
        }

        .store-title-row h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 400
        }

        .store-rating {
            color: var(--yellow-deep);
            font-size: 12px
        }

        .store-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 12px
        }

        .store-tags span {
            padding: 5px 9px;
            border: 1px solid #e7e8ea;
            border-radius: 16px;
            color: #777;
            background: #fafafa;
            font-size: 10px
        }

        .store-stats {
            display: flex;
            justify-content: space-between;
            border-top: 1px dashed #e5e5e5;
            margin-top: 15px;
            padding-top: 12px;
            color: #777;
            font-size: 10px
        }

        .store-link {
            display: block;
            margin-top: 15px;
            padding: 8px;
            border: 1px solid var(--yellow);
            border-radius: 7px;
            color: #000;
            text-align: center;
            text-decoration: none;
            font-size: 11px
        }

        @media(max-width:800px) {
            .hero {
                height: 300px;
                margin-top: 14px
            }
        }

        @media(max-width:800px) {
            .hero-content {
                right: 18px;
                top: 26px;
                width: 205px
            }
        }

        @media(max-width:800px) {
            .hero h1 {
                font-size: 24px
            }
        }

        @media(max-width:800px) {
            .hero p {
                font-size: 10px;
                line-height: 1.8
            }
        }

        @media(max-width:800px) {
            .categories {
                grid-template-columns: repeat(4, 1fr);
                padding: 12px 4px;
                gap: 14px 0
            }
        }

        @media(max-width:800px) {
            .category-icon {
                width: 36px;
                height: 36px
            }
        }

        @media(max-width:800px) {
            .category span {
                font-size: 9px
            }
        }

        @media(max-width:800px) {
            .promo-grid {
                grid-template-columns: 1fr;
                gap: 10px
            }
        }

        @media(max-width:800px) {
            .promo {
                height: 128px
            }
        }

        @media(max-width:800px) {
            .products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px
            }
        }

        @media(max-width:800px) {
            .product {
                min-height: 220px;
                padding: 9px
            }
        }

        @media(max-width:800px) {
            .product-image {
                height: 105px
            }
        }

        @media(max-width:800px) {
            .product h3 {
                font-size: 10px
            }
        }

        @media(max-width:800px) {
            .rating {
                font-size: 10px
            }
        }

        @media(max-width:800px) {
            .price {
                font-size: 11px
            }
        }

        @media(max-width:800px) {
            .add-cart {
                height: 28px;
                font-size: 10px
            }
        }

        @media(max-width:800px) {
            .wide-banner {
                height: 125px;
                padding: 10px
            }
        }

        @media(max-width:800px) {
            .wide-banner-content {
                right: 6%;
                width: 48%
            }
        }

        @media(max-width:800px) {
            .wide-banner h2 {
                font-size: 14px
            }
        }

        @media(max-width:800px) {
            .wide-banner p {
                font-size: 9px
            }
        }

        @media(max-width:800px) {
            .benefits {
                grid-template-columns: repeat(2, 1fr);
                padding: 12px 4px
            }
        }

        @media(max-width:800px) {
            .benefit strong {
                font-size: 10px
            }
        }

        @media(max-width:800px) {
            .benefit span {
                font-size: 8px
            }
        }

        @media(max-width:800px) {
            .stores-grid {
                grid-template-columns: 1fr;
                gap: 16px
            }
        }

        @media(max-width:800px) {
            .store-title-row h3 {
                font-size: 15px
            }
        }

        .favorite.is-favorite {
            color: #e5aa00
        }
    </style>
@endsection

@section('content')
    @php
        $localized = static function ($value, $fallback = '') use ($currentLocale) {
            if (is_array($value))
                return $value[$currentLocale] ?? $value['en'] ?? reset($value) ?? $fallback;
            return $value ?: $fallback;
        };
        $image = static function ($value, $fallback = 'assets/ecommerce/images/slider/4.webp') {
            return $value ?: asset($fallback);
        };
        $products = static function ($items, $limit = 6) {
            return collect($items ?? [])->filter()->take($limit);
        };
        $renderProduct = static function ($product) use ($image, $localized) {
            $title = $localized($product->title ?? null, __('ecommerce::ecommerce.product_label'));
            $productImage = $image($product->image_path ?? $product->image ?? null);
            $price = number_format((float) ($product->default_price ?? $product->price ?? 0), 2);
            $rate = number_format((float) ($product->rate ?? 4.8), 1);
            return compact('title', 'productImage', 'price', 'rate');
        };
    @endphp

    <div id="home" class="container home-page">
        @php $heroBanners = collect($mainSlider ?? []); @endphp
        <section class="hero" aria-label="@lang('ecommerce::ecommerce.home_hero_aria')">
            @forelse($heroBanners->take(3) as $index => $banner)
                <a class="hero-slide {{ $index === 0 ? 'active' : '' }}" href="{{ $banner->click_url ?? '#' }}"
                    style="background-image:url('{{ $image($banner->desktop_image_url ?? $banner->image_desktop ?? null) }}')"></a>
            @empty
                <div class="hero-slide active" style="background-image:url('{{ asset('manarimage/1.png') }}')"></div>
            @endforelse
            @php $hero = $heroBanners->first(); @endphp
            <div class="hero-content">
                <h1>{{ $localized($hero->localized_title ?? $hero->title ?? null, __('ecommerce::ecommerce.home_hero_title')) }}</h1>
                <p>{{ $localized($hero->localized_subtitle ?? $hero->subtitle ?? null, __('ecommerce::ecommerce.home_hero_subtitle')) }}
                </p><a class="primary-btn" href="#products">@lang('ecommerce::ecommerce.shop_now') <span class="arrow-end" aria-hidden="true">→</span></a>
            </div>
            <div class="hero-arrows"><button class="circle-btn" type="button" id="heroPrev"><span class="arrow-start" aria-hidden="true">←</span></button><button
                    class="circle-btn" type="button" id="heroNext"><span class="arrow-next" aria-hidden="true">→</span></button></div>
            <div class="dots">@foreach($heroBanners->take(3) as $index => $banner)<button
            class="dot {{ $index === 0 ? 'active' : '' }}" type="button"></button>@endforeach</div>
        </section>
        <section id="categories" class="categories">
            @foreach(collect($categories ?? []) as $category)<a class="category"
                href="{{ route('categories.index', ['main_category' => $category->id]) }}"><i class="category-icon"><svg
                        viewBox="0 0 24 24">
                        <path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5" />
            </svg></i><span>{{ $localized($category->title ?? $category->name ?? null, __('ecommerce::ecommerce.home_category_fallback')) }}</span></a>@endforeach
        </section>
        <section id="offers" class="promo-grid">@foreach(collect($productBanners ?? [])->take(3) as $banner)
            <article class="promo"
                style="background-image:url('{{ $image($banner->desktop_image_url ?? $banner->image_desktop ?? null) }}')">
                <div class="promo-content">
                    <h3>{{ $localized($banner->localized_title ?? $banner->title ?? null, __('ecommerce::ecommerce.home_banner_fallback')) }}</h3>
                    <p>{{ $localized($banner->localized_subtitle ?? $banner->subtitle ?? null) }}</p><a class="small-btn"
                        href="{{ $banner->click_url ?? '#products' }}">@lang('ecommerce::ecommerce.shop_now')</a>
                </div>
        </article>@endforeach
        </section>
        @php $productSections = [['products' => $bestSellers ?? [], 'title' => __('ecommerce::ecommerce.best_sellers'), 'id' => 'best-sellers'], ['products' => $featuredProducts ?? [], 'title' => __('ecommerce::ecommerce.featured_products'), 'id' => 'featured-products'], ['products' => $newProducts ?? [], 'title' => __('ecommerce::ecommerce.new_products'), 'id' => 'new-products'], ['products' => $exclusiveProducts ?? [], 'title' => __('ecommerce::ecommerce.exclusive_collections'), 'id' => 'exclusive-products'], ['products' => $sectionNewArrivals ?? [], 'title' => __('ecommerce::ecommerce.new_arrival'), 'id' => 'new-arrivals']]; @endphp
        @foreach($productSections as $section)
            @if(collect($section['products'])->isNotEmpty())
                <section id="{{ $section['id'] }}" class="extra-section">
                    <div class="section-head">
                        <h2>{{ $section['title'] }}</h2><a class="view-all" href="{{ route('categories.index') }}">@lang('ecommerce::ecommerce.view_all')
                            ></a>
                    </div>
                    <div class="products">
                        @foreach($products($section['products'], 8) as $product)
                            @php $card = $renderProduct($product); @endphp
                            <article class="product"><button type="button" data-product-id="{{ $product->id }}" class="favorite{{ isset($favoriteIds[$product->id]) ? ' is-favorite' : '' }}"
                                    aria-label="@lang('ecommerce::ecommerce.add_to_wishlist')"><i
                                        class="{{ isset($favoriteIds[$product->id]) ? 'fa fa-heart' : 'ti-heart' }}"></i></button><a
                                    class="product-img-wrap" href="{{ route('product.static', $product->id) }}"><img
                                        class="product-image" src="{{ $card['productImage'] }}" alt="{{ $card['title'] }}"
                                        loading="lazy">@if(!$product->isInStock())<span
                                        class="product-stock-badge">@lang('ecommerce::ecommerce.out_of_stock')</span>@endif</a>
                                <h3><a href="{{ route('product.static', $product->id) }}" style="color:inherit;text-decoration:none">{{ $card['title'] }}</a></h3>
                                <div class="rating">★★★★★ <span>({{ $card['rate'] }})</span></div>
                                <div class="price">{{ \App\Domain\AppBase\Models\Setting\Currency::format($card['price']) }}</div>
                                <form class="add-cart-form" action="{{ route('cart.add') }}" method="POST">@csrf<input type="hidden"
                                        name="item_id" value="{{ $product->id }}"><input type="hidden" name="qty"
                                        value="1">@if(!$product->isInStock())<button class="add-cart" type="button" disabled
                                        style="background:#e5e7eb !important;color:#9ca3af !important;cursor:not-allowed">@lang('ecommerce::ecommerce.out_of_stock')</button>@else<button
                                        class="add-cart" type="submit"><i class="ti-shopping-cart"></i> @lang('ecommerce::ecommerce.add_to_cart')</button>@endif
                                </form>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
        @if(collect($largeBanner ?? [])->isNotEmpty())
            @php $banner = collect($largeBanner)->first(); @endphp
            <section class="wide-banner"
                style="background-image:url('{{ $image($banner->desktop_image_url ?? $banner->image_desktop ?? null) }}')">
                <div class="wide-banner-content">
                    <p>{{ $localized($banner->localized_subtitle ?? $banner->subtitle ?? null) }}</p>
                    <h2>{{ $localized($banner->localized_title ?? $banner->title ?? null, __('ecommerce::ecommerce.home_banner2_fallback')) }}
                    </h2><a class="primary-btn" href="{{ $banner->click_url ?? '#products' }}">@lang('ecommerce::ecommerce.shop_now') <span class="arrow-end" aria-hidden="true">→</span></a>
                </div>
            </section>
        @endif
        </div>
        <section class="benefits">
            <div class="container" style="display:contents">
                <div class="benefit"><i class="benefit-icon">♧</i>
                    <div><strong>@lang('ecommerce::ecommerce.home_feature_fast_title')</strong><span>@lang('ecommerce::ecommerce.home_feature_fast_desc')</span></div>
                </div>
                <div class="benefit"><i class="benefit-icon">♢</i>
                    <div><strong>@lang('ecommerce::ecommerce.home_feature_secure_title')</strong><span>@lang('ecommerce::ecommerce.home_feature_secure_desc')</span></div>
                </div>
                <div class="benefit"><i class="benefit-icon">⚙</i>
                    <div><strong>@lang('ecommerce::ecommerce.home_feature_quality_title')</strong><span>@lang('ecommerce::ecommerce.home_feature_quality_desc')</span></div>
                </div>
                <div class="benefit"><i class="benefit-icon">♧</i>
                    <div><strong>@lang('ecommerce::ecommerce.home_feature_support_title')</strong><span>@lang('ecommerce::ecommerce.home_feature_support_desc')</span></div>
                </div>
            </div>
        </section>
        @if(collect($merchantSlider ?? [])->isNotEmpty())
            <section id="stores" class="stores-section">
                <div class="container">
                    <div class="stores-heading">
                        <h2>@lang('ecommerce::ecommerce.merchants')</h2><a href="{{ route('merchants.index') }}">@lang('ecommerce::ecommerce.view_all') ></a>
                    </div>
                    <div class="stores-grid">
                        @foreach($merchantSlider as $merchant)
                            @php
                                $merchantName = $localized($merchant->company_name ?? $merchant->name ?? null, __('ecommerce::ecommerce.home_merchant_fallback'));
                                $branch = collect($merchant->branches ?? [])->first();
                                $cover = $branch->cover ?? $branch->cover_image ?? null;
                            @endphp
                            <article class="store-card">
                                <div class="store-cover" style="background-image:url('{{ $image($cover) }}')">
                                    @if(($merchant->status ?? null) === 'active')<span class="trusted-badge">@lang('ecommerce::ecommerce.verified_seller')
                                    ✓</span>@endif
                                </div>
                                <div class="store-info">
                                    <div class="store-title-row">
                                        <h3>{{ $merchantName }}</h3>
                                        <div class="store-rating">{{ number_format((float) ($merchant->rate ?? 1), 1) }} ★
                                            <span>({{ $merchant->reviews_count ?? 0 }})</span>
                                        </div>
                                    </div>
                                    <div class="store-tags">
                                        @foreach(collect($merchant->productCategories ?? [])->take(3) as $category)<span>#
                                        {{ $localized($category->title ?? $category->name ?? null) }}</span>@endforeach
                                    </div>
                                    <div class="store-stats"><span><i class="fa fa-building-o"></i>
                                            {{ $merchant->branches_count ?? 0 }} @lang('ecommerce::ecommerce.branches')</span><span><i class="fa fa-cube"></i>
                                            {{ $merchant->items_count ?? 0 }} @lang('ecommerce::ecommerce.items_label')</span></div><a class="store-link"
                                        href="{{ route('merchant.show', ['merchant' => $merchant->id]) }}">@lang('ecommerce::ecommerce.visit_store') ></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
        @foreach([['items' => $recentNews ?? [], 'title' => __('ecommerce::ecommerce.recent_news')], ['items' => $clientBrands ?? [], 'title' => __('ecommerce::ecommerce.our_brands')], ['items' => $testimonials ?? [], 'title' => __('ecommerce::ecommerce.testimonials')]] as $extra)
            @if(collect($extra['items'])->isNotEmpty())
                <section class="extra-section">
                    <div class="container">
                        <div class="section-head">
                            <h2>{{ $extra['title'] }}</h2>
                        </div>
                        <div class="products">@foreach(collect($extra['items'])->take(4) as $item)
                            <article class="product">
                                <h3>{{ $localized($item->localized_title ?? $item->title ?? $item['name'] ?? null, $item['name'] ?? '') }}
                                </h3>
                                <p>{{ $localized($item->localized_subtitle ?? $item->subtitle ?? $item['content'] ?? null) }}</p>
                        </article>@endforeach
                        </div>
                    </div>
            </section>@endif
        @endforeach
@endsection

    @section('scripts')
        <script>
            const slides = [...document.querySelectorAll('.hero-slide')], dots = [...document.querySelectorAll('.dot')]; let slideIndex = 0, slideTimer;
            function showSlide(index) { if (!slides.length) return; slideIndex = (index + slides.length) % slides.length; slides.forEach((slide, i) => slide.classList.toggle('active', i === slideIndex)); dots.forEach((dot, i) => dot.classList.toggle('active', i === slideIndex)); }
            function restartSlider() { clearInterval(slideTimer); slideTimer = setInterval(() => showSlide(slideIndex + 1), 4500) }
            document.getElementById('heroPrev')?.addEventListener('click', () => { showSlide(slideIndex - 1); restartSlider() }); document.getElementById('heroNext')?.addEventListener('click', () => { showSlide(slideIndex + 1); restartSlider() }); dots.forEach((dot, i) => dot.addEventListener('click', () => { showSlide(i); restartSlider() })); restartSlider();
            const search = document.querySelector('.search input'); search?.addEventListener('input', event => { const query = event.target.value.trim().toLowerCase(); document.querySelectorAll('.product').forEach(card => card.hidden = query && !card.textContent.toLowerCase().includes(query)) });
            document.querySelectorAll('.add-cart-form').forEach(form => form.addEventListener('submit', async event => {
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                const originalText = button ? button.innerHTML : '';
                if (button) { button.disabled = true; button.textContent = @json(__('ecommerce::ecommerce.product.adding')); }

                try {
                    if (!window.submitCartForm) throw new Error(@json(__('ecommerce::ecommerce.cart_service_unavailable')));
                    const result = await window.submitCartForm(form, {});
                    if (result && (result.login || result.cancelled)) return;
                    if (button) { button.textContent = @json(__('ecommerce::ecommerce.added')); button.classList.add('added'); }
                } catch (error) {
                    const message = error && error.message ? error.message : @json(__('ecommerce::ecommerce.cart.add_error'));
                    if (window.showStaticConflictModal && typeof window.showStaticConflictModal === 'function') {
                        window.showStaticConflictModal(message).catch(function () { });
                        return;
                    }
                    window.alert(message);
                } finally {
                    if (button && !button.classList.contains('added')) {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                }
            }));
            document.addEventListener('click', async function (event) {
                const favorite = event.target.closest('.favorite');
                if (!favorite) return;
                event.preventDefault();
                event.stopPropagation();
                const form = favorite.closest('.product')?.querySelector('form.add-cart-form');
                const itemId = favorite.dataset.productId;
                const token = form?.querySelector('input[name="_token"]')?.value;
                if (!itemId || !token || favorite.dataset.loading === '1') return;
                favorite.dataset.loading = '1';
                try {
                    const body = new FormData();
                    body.append('_token', token);
                    body.append('product_id', itemId);
                    const response = await fetch('{{ route('wishlist.add') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: body
                    });
                    const data = await response.json().catch(function () { return null; });
                    if ((!data && response.url.includes('/login')) || (data && data.login)) {
                        window.location.href = data && data.redirect ? data.redirect : response.url;
                        return;
                    }
                    const isFavorite = data && data.status === 'added';
                    favorite.classList.toggle('is-favorite', isFavorite);
                    favorite.innerHTML = isFavorite ? '<i class="fa fa-heart"></i>' : '<i class="ti-heart"></i>';
                    if (typeof window.updateWishlistBadge === 'function' && data && typeof data.count !== 'undefined') {
                        window.updateWishlistBadge(data.count);
                    }
                } finally {
                    delete favorite.dataset.loading;
                }
            }, true);
        </script>
    @endsection
