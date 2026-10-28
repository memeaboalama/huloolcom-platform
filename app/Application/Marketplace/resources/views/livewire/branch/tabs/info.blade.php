@push('css')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/leaflet/leaflet.css') }}" />
<style>
    #map {
        height: 350px;
        width: 100%;
        border-radius: 8px;
        z-index: 1;
    }
    .leaflet-pane {
        z-index: 10 !important;
    }
    .leaflet-bottom, .leaflet-top {
        z-index: 20 !important;
    }
    #search-suggestions {
        position: absolute;
        top: 100%;
        right: 0;
        left: 0;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        margin-top: 2px;
    }
    #search-suggestions .list-group-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
</style>
@endpush
<div>
<!-- <form wire:submit.prevent="storeData"> -->
<form method="post" wire:submit.prevent="store" enctype="multipart/form-data">
    <fieldset {{ $editing ? '' : 'disabled' }}>
    <div class="modal-body">
        <div class="row gx-5">
            <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-6">
                <div class="card custom-card shadow-none mb-0">
                    <div class="card-body p-0">
                        <div class="row gy-3">
                            @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                            <div class="col-xl-3">
                                <label for="branch-name"
                                    class="form-label text-black">@lang('marketplace::home.Branch Name') {{$properties['native']}} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch-name"
                                wire:model.defer="title.{{$localeCode}}">
                                @error("title." . $localeCode) <span
                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                            </div>
                            @endforeach

                            <div class="col-xl-3">
                                <label for="branch-menu"
                                    class="form-label text-black ">@lang('marketplace::home.Menu Name')</label>

                                    <div wire:ignore>
                                    <select class="form-control"
                                        wire:model.defer="merchant_service_id"
                                        id="merchant_service_id">
                                        <option
                                            value="">@lang('marketplace::home.Select') @lang('marketplace::home.Menu Name')</option>
                                        @foreach($menus as $menu)
                                        <option
                                            value="{{ $menu->id }}" {{ $menu->id == $merchant_service_id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::of($menu->title)->limit(30) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('merchant_service_id') <span
                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-xl-3">
                                <div>
                                    <label for="manager_id"
                                        class="form-label text-black">@lang('marketplace::home.Branch Manager')</label>
                                    <select class="form-control"
                                        wire:model.lazy="manager_id"
                                        id="manager_id">
                                        <option value="">@lang('marketplace::home.Select')</option>
                                        @foreach($employees as $employee)
                                        <option
                                            value="{{ $employee->id }}" {{ $employee->id == $manager_id ? 'selected' : '' }}>{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('manager_id') <span
                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-xl-4">
                                <label for="branch-country"
                                    class="form-label text-black ">@lang('marketplace::home.Country') <span class="text-danger">*</span></label>

                                    <div wire:ignore>
                                    <select class="form-control selectCountry"
                                        data-trigger
                                        wire:model.defer="country_id"
                                        id="branch-country-add">
                                        <option value="">@lang('marketplace::home.Select')</option>
                                        @foreach($counties as $country)
                                        <option
                                            value="{{ $country->id  }}" {{ $country->id == $country_id ? 'selected' : '' }}>{{ $country->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('country_id') <span
                                    class="d-inline-block text-info">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-xl-4">
                                <div>
                                    <label for="branch-region" class="form-label text-black">@lang('company::home.Region') <span class="text-danger">*</span></label>
                                    <select class="form-control selectRegion"
                                        wire:model="region_id"
                                        id="branch-region">
                                        <option value="">@lang('company::home.select')</option>
                                        @foreach($regions as $region)
                                        <option value="{{ $region->id }}" {{ $region->id == $region_id ? 'selected' : '' }}>{{ $region->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('region_id') <span
                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-xl-4">
                                <div>
                                    <label for="branch-city"
                                        class="form-label text-black">@lang('marketplace::home.City') <span class="text-danger">*</span></label>
                                    <select class="form-control selectCity"
                                        wire:model="city_id"
                                        id="branch-city">
                                        <option value="">@lang('marketplace::home.Select')</option>
                                        @foreach($cities as $city)
                                        <option value="{{ $city->id }}" {{ $city->id == $city_id ? 'selected' : '' }}>{{ $city->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('city_id') <span
                                    class="d-inline-block text-info">{{ $message }}</span> @enderror
                            </div>

                            {{-- <div class="col-xl-4">
                                    <div>
                                        <label for="branch-distract"
                                               class="form-label text-black">@lang('marketplace::home.District') <span class="text-danger">*</span></label>
                                        <select class="form-control selectDistrict"
                                                wire:model.lazy="distract_id"
                                                id="branch-distract">
                                            <option value="">@lang('marketplace::home.Select')</option>
                                            @foreach($districts as $district)
                                                <option
                                                    value="{{ $district->id }}" {{ $district->id == $distract_id ? 'selected' : '' }}>{{ $district->title }}</option>
                            @endforeach
                            </select>
                        </div>
                        @error('distract_id') <span
                            class="d-inline-block text-info">{{ $message }}</span> @enderror
                    </div> --}}
                </div>
                <div class="row gy-3 mt-2">
                    <div class="col-md-12">
                        <div class="form-group">

                            <table class="table">
                                <thead>
                                    <th>@lang('admin::home.servicer type') </th>
                                    <th>@lang('admin::home.Description')</th>
                                    <th>@lang('admin::home.commission percentage')</th>
                                    <th>@lang('admin::home.discount percentage')</th>
                                    <th>@lang('admin::home.status')</th>

                                </thead>

                                <tbody>
                                    @foreach($package_type_services as $index =>$package_type_service)
                                    <tr>
                                        <input wire:model.defer="package_type_services.{{$index}}.type_service_id" class="form-control" type="hidden" />
                                        <td>
                                            <input value="{{ \Illuminate\Support\Facades\Lang::has('admin::home.'.$package_type_services[$index]['type_service']['name']) ? __('admin::home.'.$package_type_services[$index]['type_service']['name']) : $package_type_services[$index]['type_service']['name'] }}" class="form-control" disabled />
                                        </td>

                                        <td>
                                            <input wire:model.defer="package_type_services.{{$index}}.desc" class="form-control" />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control"
                                                wire:model.defer="package_type_services.{{$index}}.commission"
                                                value="{{$package_type_services[$index]['commission']}}"
                                                disabled />
                                        </td>
                                        <td>
                                            <input wire:model.defer="package_type_services.{{$index}}.discount_rate" value="{{$package_type_services[$index]['discount_rate']}}" disabled class="form-control" />
                                        </td>
                                        <td>
                                            <div class="custom-toggle-switch d-flex align-items-center mb-4 mt-3">
                                                <input id="{{'active'.$index + 1000 }}"
                                                    type="checkbox" value="1"
                                                    wire:model.defer="{{ 'package_type_services.' . $index . '.active' }}" {{ $package_type_services[$index]['status'] == "active"  ? 'checked' : '' }}>
                                                <label for="{{'active'.$index + 1000 }}" class="label-success"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    @endforeach


                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-4 mt-2">
                        <label for="cover_image"
                            class="form-label text-black">@lang('marketplace::home.cover_image') <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="cover_image"
                            placeholder="{{ __("marketplace::home.cover_image") }}"
                            wire:model.defer="cover_image">
                        <label for="cover_image" class="form-label mt-2 text-danger">Max size : 1 MB , 500 px * 200 px</label>
                        @error("cover_image")
                        <span class="text-danger d-block mt-2">{{ $message }}</span>
                        @enderror
                    </div>


                    <div class="col-xl-8">
                        <img src="{{ $cover_image_path }}"
                            class="img-fluid rounded-start mt-3"
                            alt="@lang('marketplace::home.cover_image')" width="150">
                    </div>
                </div>
                <div class="row gy-3 mt-3">
                    @error('longitude')
                    <div class="alert alert-danger" role="alert">
                        {{ __('marketplace::messages.MAP_MESSAGE') }}
                    </div>
                    @enderror
                </div>
                <div class="row gy-3 mt-1">
                    <div class="col-xl-6">
                        <label for="latitude" class="form-label text-black">
                            @lang('marketplace::home.latitude') (Latitude) <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="latitude" class="form-control"
                            wire:model.defer="latitude"
                            placeholder="مثال: 24.774265">
                        @error('latitude')
                        <span class="d-inline-block text-danger mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-xl-6">
                        <label for="longitude" class="form-label text-black">
                            @lang('marketplace::home.longitude') (Longitude) <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="longitude" class="form-control"
                            wire:model.defer="longitude"
                            placeholder="مثال: 46.738586">
                        @error('longitude')
                        <span class="d-inline-block text-danger mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row gy-3 mt-2">
                    <div class="col-xl-12 position-relative">
                        <label for="map-search-input" class="form-label text-black">
                            @lang('marketplace::home.search_map_location')
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" id="map-search-input"
                                class="form-control"
                                placeholder="{{ __('marketplace::home.search_map_placeholder') }}"
                                autocomplete="off">
                            <button class="btn btn-primary" type="button" id="btn-map-search">
                                <span class="search-text"><i class="ri-search-2-line me-1"></i> بحث</span>
                                <span class="search-loading d-none spinner-border spinner-border-sm"></span>
                            </button>
                        </div>
                        <div id="search-suggestions" class="list-group position-absolute w-100 shadow" style="display: none; z-index: 1050; max-height: 220px; overflow-y: auto;"></div>
                        <small class="text-muted d-block mt-1">@lang('marketplace::home.map_hint')</small>
                    </div>
                </div>
                <div class="row gy-3 mt-1">
                    <div class="col-xl-12" wire:ignore>
                        <div id="map" style="height:350px; border-radius: 8px;" class="my-2 border"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </fieldset>
    @MerchantHasRole(['edit info_branches'])
    <div class="modal-footer">
        <button type="submit" class="btn btn-success" {{ $editing ? '' : 'hidden' }}>
            <span class="indicator-label" wire:loading.class="d-none">
                @lang('marketplace::home.Save')
            </span>
            <span class="d-none" wire:loading.class.remove="d-none">
                @lang('marketplace::home.Wait') <span
                    class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
        </button>
        
    </div>
    @endMerchantHasRole
</form>
</div>
@push('js')
<script>
    $(document).on('change', '.selectCountry', function() {
        //     $('.selectCity').empty();
        //     window.livewire.emit('updateCountryId', $(this).val());
        // });
        $('.selectRegion').empty();
        $('.selectCity').empty();
        window.livewire.emit('updateCountry', $(this).val());
    });
    // $(document).on('change', '.selectRegion', function() {
    //     $('.selectCity').empty();
    //     window.livewire.emit('updateRegion', $(this).val());
    // });

    // $(document).on('change', '.selectCity', function() {
    //     $('.selectDistrict').empty();
    //     window.livewire.emit('updateCity', $(this).val());
    // });

    // $(document).on('change', '.selectDistrict', function() {
    //     window.livewire.emit('updateDistrict', $(this).val());
    // });


    Livewire.on('renderRegions', regions => {
        let element = $('.selectRegion');
        element.append(`<option value="">select</option>`);
        for (let i = 0; i < cities.length; i++) {
            element.append(`<option value="${cities[i].id}">${cities[i]?.title?.en}</option>`);
        }
        // Livewire.emit('renderSelect');
    });
    Livewire.on('renderCities', cities => {
        let element = $('.selectCity');
        element.append(`<option value="">select</option>`);
        for (let i = 0; i < cities.length; i++) {
            element.append(`<option value="${cities[i].id}">${cities[i]?.title?.en}</option>`);
        }
        // Livewire.emit('renderSelect');
    });

    Livewire.on('renderDistricts', districts => {
        console.log(districts);

        for (let i = 0; i < districts.length; i++) {
            $('.selectDistrict').append(`<option value="${districts[i].id}">${districts[i]?.title?.en}</option>`);
        }
        Livewire.emit('renderSelect');
    });
</script>



<script src="{{ asset('assets/vendor/libs/leaflet/leaflet.js') }}"></script>
<script type="text/javascript">
(function() {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: '{{ asset("assets/vendor/libs/leaflet/images/marker-icon-2x.png") }}',
        iconUrl: '{{ asset("assets/vendor/libs/leaflet/images/marker-icon.png") }}',
        shadowUrl: '{{ asset("assets/vendor/libs/leaflet/images/marker-shadow.png") }}'
    });

    const initLat = {{ is_numeric($latitude) ? $latitude : 24.774265 }};
    const initLng = {{ is_numeric($longitude) ? $longitude : 46.738586 }};

    function updateCoordinates(latitude, longitude) {
        var formattedLat = typeof latitude === 'number' ? latitude.toFixed(7) : latitude;
        var formattedLng = typeof longitude === 'number' ? longitude.toFixed(7) : longitude;
        $('#latitude').val(formattedLat);
        $('#longitude').val(formattedLng);
        window.livewire.emit('set:latitude-longitude', formattedLat, formattedLng);
    }

    var map = null;
    var marker = null;

    function initLeafletMap() {
        var mapEl = document.getElementById('map');
        if (!mapEl || map) {
            return;
        }

        map = L.map('map').setView([initLat, initLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        marker = L.marker([initLat, initLng], {
            draggable: true
        }).addTo(map);

        marker.on('dragend', function() {
            var position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
            reverseGeocode(position.lat, position.lng);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        setTimeout(function() {
            if (map) {
                map.invalidateSize();
            }
        }, 300);

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            if (map) {
                map.invalidateSize();
            }
        });
    }

    function reverseGeocode(lat, lng) {
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/reverse',
            method: 'GET',
            dataType: 'json',
            data: {
                format: 'json',
                lat: lat,
                lon: lng,
                'accept-language': 'ar'
            },
            success: function(data) {
                if (data && data.display_name) {
                    @this.set('address', data.display_name);
                }
            }
        });
    }

    var searchTimer = null;
    function doSearch(query) {
        if (!query || query.trim().length < 2) {
            $('#search-suggestions').hide().empty();
            return;
        }

        $('.search-loading').removeClass('d-none');
        $('.search-text').addClass('d-none');

        $.ajax({
            url: 'https://nominatim.openstreetmap.org/search',
            method: 'GET',
            dataType: 'json',
            data: {
                format: 'json',
                q: query.trim(),
                limit: 5,
                'accept-language': 'ar'
            },
            success: function(results) {
                $('.search-loading').addClass('d-none');
                $('.search-text').removeClass('d-none');
                var $list = $('#search-suggestions');
                $list.empty();

                if (!results || results.length === 0) {
                    $list.append('<div class="list-group-item text-muted">لا توجد نتائج بحث</div>').show();
                    return;
                }

                results.forEach(function(item) {
                    var $a = $('<a href="#" class="list-group-item list-group-item-action py-2"></a>');
                    $a.text(item.display_name);
                    $a.on('click', function(e) {
                        e.preventDefault();
                        var lat = parseFloat(item.lat);
                        var lon = parseFloat(item.lon);
                        if (map && marker) {
                            map.setView([lat, lon], 14);
                            marker.setLatLng([lat, lon]);
                            updateCoordinates(lat, lon);
                            @this.set('address', item.display_name);
                        }
                        $('#map-search-input').val(item.display_name);
                        $list.hide().empty();
                    });
                    $list.append($a);
                });
                $list.show();
            },
            error: function() {
                $('.search-loading').addClass('d-none');
                $('.search-text').removeClass('d-none');
            }
        });
    }

    $(document).ready(function() {
        initLeafletMap();

        $('#map-search-input').on('input', function() {
            var val = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                doSearch(val);
            }, 400);
        });

        $('#map-search-input').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(searchTimer);
                doSearch($(this).val());
            }
        });

        $('#btn-map-search').on('click', function(e) {
            e.preventDefault();
            clearTimeout(searchTimer);
            doSearch($('#map-search-input').val());
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#map-search-input, #search-suggestions, #btn-map-search').length) {
                $('#search-suggestions').hide();
            }
        });

        // Allow manual entry in latitude & longitude inputs
        $('#latitude, #longitude').on('change', function() {
            var manualLat = parseFloat($('#latitude').val());
            var manualLng = parseFloat($('#longitude').val());
            if (!isNaN(manualLat) && !isNaN(manualLng) && map && marker) {
                marker.setLatLng([manualLat, manualLng]);
                map.setView([manualLat, manualLng], map.getZoom());
                window.livewire.emit('set:latitude-longitude', manualLat, manualLng);
            }
        });
    });
})();
</script>
@endpush