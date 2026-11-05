<div wire:ignore>
<div class="col-xl-12">

                <div class="card custom-card border-box-1">
                    <div class="card-header">
                        <div class="card-title">
                            @lang('admin::home.Branch Data')
                        </div>
                    </div>
                    <div class="card-body">
<div class="row gy-3 mt-2">
<div class="col-xl-12">
                                                    <label for="branch-menu"
                                                           class="form-label text-black ">@lang('admin::home.Branch Select')</label>

                                                    <div wire:ignore>
                                                        <select class="form-control"
                                                                wire:model.defer="branch_id"
                                                                wire:change="changeBranch()"
                                                                name="branch_id">
                                                            <!-- <option value="">@lang('marketplace::home.Select') @lang('marketplace::home.Branch Name')</option> -->
                                                            @foreach($listBranches as $branch)
                                                                <option
                                                                    value="{{ $branch->id }}">{{ $branch->title }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @error('branch_id') <span
                                                        class="d-inline-block text-danger">{{ $message }}</span> @enderror

<div class="row">
                                            <div class="col-xl-4" hidden>
                                                <label for="branch-country"
                                                       class="form-label text-black ">@lang('marketplace::home.Country') <span class="text-danger">*</span></label>

                                                <div wire:ignore>
                                                    <select class="form-control selectCountryBrn"
                                                            data-trigger
                                                            name="country_id"
                                                            id="branch-country-add">
                                                        <option value="">@lang('marketplace::home.Select')</option>
                                                        @foreach($counties as $country)
                                                            <option
                                                                value="{{ $country  }}">{{ $country->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('country_id') <span
                                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                                            </div>

                                            <div class="col-xl-4" hidden>
                                                <div>
                                                    <label for="branch-city" class="form-label text-black">@lang('company::home.Region') <span class="text-danger">*</span></label>
                                                    <select class="form-control selectCityBrn"
                                                            wire:model="region_id"
                                                            id="branch-city">
                                                        <option value="">@lang('company::home.select')</option>
                                                        @foreach($regions as $region)
                                                            <option value="{{ $region->id }}">{{ $region->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('region_id') <span
                                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                                            </div>

                                            <div class="col-xl-4">
                                                <div>
                                                    <label for="branch-city" class="form-label text-black">@lang('marketplace::home.City') <span class="text-danger">*</span></label>
                                                    <select class="form-control selectCityBrn"
                                                            wire:model="city_id"
                                                            id="branch-city">
                                                        <option value="">@lang('marketplace::home.Select')</option>
                                                        @foreach($cities as $city)
                                                            <option value="{{ $city->id }}">{{ $city->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('city_id') <span
                                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            
                                            <div class="col-xl-4">
                                                <div>
                                                    <label for="package_id"
                                                           class="form-label text-black">@lang('marketplace::home.Select Package')</label>
                                                    <select class="form-control"
                                                            wire:model.lazy="package_id"
                                                            wire:model="package_id"
                                                            wire:change="updatedPackageId()"
                                                            id="package_id">
                                                        <option value="">@lang('marketplace::home.Select')</option>
                                                        @foreach($packages as $package)
                                                            <option
                                                            value="{{ $package->id }}">{{ $package->title."    (".$package->commission.")" }}
                                                            
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('package_id') <span
                                                    class="d-inline-block text-danger">{{ $message }}</span> @enderror
                                            </div>

                                        </div>
                                        </div>
                                        </div>
                                        <div class="row gy-3 mt-2">
                                            <div class="col-md-12">
                                            <div class="form-group">

<table class="table">
    <thead>
    <th>@lang('admin::home.servicer type') </th>
    <th>@lang('admin::home.commission percentage')</th>
    <th>@lang('admin::home.discount percentage')</th>
    <th>@lang('admin::home.status')</th>

    </thead>

    <tbody>
    @foreach($package_type_services as $index =>$package_type_service)
        <tr>
            <input wire:model.defer="package_type_services.{{$index}}.type_service_id" class="form-control"  type="hidden" />
            <td>
            <input value="@lang('admin::home.'.($package_type_services[$index]['type_service']['name']))"  class="form-control" disabled />
                            </td>

                            <td>
                            <input wire:model.defer="package_type_services.{{$index}}.commission" disabled class="form-control"  />
</td>
                            <td>
                            <input wire:model.defer="package_type_services.{{$index}}.discount_rate"  class="form-control"  />
           </td>
                            <td>
           
            <div class="custom-toggle-switch d-flex align-items-center mb-4 mt-3">
            <input id="{{'active'.$index}}" 
                       type="checkbox" {{ $package_type_services[$index]['status'] == "active"  ? 'checked' : '' }} />
                <label for="{{'active'.$index}}" class="label-success"
                 wire:click="updateStatusRow('{{ $index }}')"  wire:ignore.self></label>
            </div>
                    </td>
            

        </tr>

    @endforeach


    </tbody>
</table>
</div>
                                                </div>
                                                </div>
                                                <div class="row gy-3 mt-3">
                <div class="col-xl-12  text-end mt-4">
                <button wire:click='updateBranchData' class="btn btn-success">
                                <span class="indicator-label" wire:loading.class="d-none">
                                    @lang('marketplace::home.Save')
                                </span>
                <span class="d-none" wire:loading.class.remove="d-none">
                                    @lang('marketplace::home.Wait') <span
                        class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
            </button>
                </div>
            </div>
</div>
                    </div>
</div>
<div class="col-xl-12">
                <div class="card custom-card border-box-1">
                    <div class="card-header">
                        <div class="card-title">
                            @lang('admin::home.Branch Map')
                        </div>
                    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="input-group">
                <input id="pac-input-create" type="text" class="form-control" placeholder="ابحث عن مدينة أو موقع على الخريطة (اضغط Enter للبحث)...">
                <button class="btn btn-primary" type="button" id="btn-search-map" onclick="searchLocationOnMap()">
                    <i class="ri-search-line me-1"></i> بحث
                </button>
            </div>
            <div id="search-status-msg" class="text-muted fs-12 mt-1" style="display: none;"></div>
        </div>
        <div id="map" style="height: 600px; width: 100%; border-radius: 6px;"></div>
    </div>
</div>
</div>
    @push('js')
    <script>
        $(document).on('change', '.selectCountryBrn', function () {
            $('.selectCityBrn').empty();
            window.livewire.emit('updateCountryBrn', $(this).val());
        });


        $(document).on('change', '.selectCityBrn', function () {
            $('.selectDistrictBrn').empty();
            window.livewire.emit('updateCity', $(this).val());
        });

        $(document).on('change', '.selectDistrictBrn', function () {
            window.livewire.emit('updateDistrictBrn', $(this).val());
        });


        Livewire.on('renderCities', cities => {
            let element = $('.selectCityBrn');
            element.append(`<option value="">select</option>`);
            for (let i = 0; i < cities.length; i++) {
                element.append(`<option value="${cities[i].id}">${cities[i]?.title?.en}</option>`);
            }
             Livewire.emit('renderSelect');
        });

        Livewire.on('renderDistricts', districts => {
            console.log(districts);

            for (let i = 0; i < districts.length; i++) {
                $('.selectDistrictBrn').append(`<option value="${districts[i].id}">${districts[i]?.title?.en}</option>`);
            }
            Livewire.emit('renderSelect');
        });
    </script>
    <script type="text/javascript">

        $("#pac-input-create").focusin(function () {
            $(this).val('');
        })

        function initAutocomplete() {
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 24.774265, lng: 46.738586},
                zoom: 13,
                mapTypeId: 'roadmap',
                scrollwheel: true,
            });
            // move pin and current location
            infoWindow = new google.maps.InfoWindow;
            geocoder = new google.maps.Geocoder();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    map.setCenter(pos);
                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(pos),
                        map: map,
                        title: 'موقعك الحالي',
                        draggable: true
                    });
                    markers.push(marker);
                    marker.addListener('click', function () {
                        geocodeLatLng(geocoder, map, infoWindow, marker);
                    });
                    // to get current position address on load
                    google.maps.event.trigger(marker, 'click');
                }, function () {
                    handleLocationError(true, infoWindow, map.getCenter());
                });
            } else {
                // Browser doesn't support Geolocation
                handleLocationError(false, infoWindow, map.getCenter());
            }
            var geocoder = new google.maps.Geocoder();
            console.log(geocoder);

            google.maps.event.addListener(map, 'click', function (event) {
                SelectedLatLng = event.latLng;
                geocoder.geocode({
                    'latLng': event.latLng
                }, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            deleteMarkers();
                            addMarkerRunTime(event.latLng);
                            SelectedLocation = results[0].formatted_address;
                            // console.log(results[0].formatted_address);
                            splitLatLng(String(event.latLng));
                            $("#pac-input-create").val(results[0].formatted_address);
                        @this.set('address', results[0].formatted_address)
                            ;
                        }
                    }
                });
            });

            function geocodeLatLng(geocoder, map, infowindow, markerCurrent) {
                var latlng = {lat: markerCurrent.position.lat(), lng: markerCurrent.position.lng()};
                /* $('#branch-latLng').val("("+markerCurrent.position.lat() +","+markerCurrent.position.lng()+")");*/
                $('#latitude').val(markerCurrent.position.lat());
                $('#longitude').val(markerCurrent.position.lng());
                geocoder.geocode({'location': latlng}, function (results, status) {
                    if (status === 'OK') {
                        if (results[0]) {
                            map.setZoom(8);
                            var marker = new google.maps.Marker({
                                position: latlng,
                                map: map
                            });
                            markers.push(marker);
                            infowindow.setContent(results[0].formatted_address);
                            SelectedLocation = results[0].formatted_address;
                            $("#pac-input-create").val(results[0].formatted_address);
                        @this.set('address', results[0].formatted_address)
                            ;
                            infowindow.open(map, marker);
                        } else {
                            window.alert('No results found');
                        }
                    } else {
                        window.alert('Geocoder failed due to: ' + status);
                    }
                });
                SelectedLatLng = (markerCurrent.position.lat(), markerCurrent.position.lng());
            }

            function addMarkerRunTime(location) {
                var marker = new google.maps.Marker({
                    position: location,
                    map: map
                });
                markers.push(marker);
            }

            function setMapOnAll(map) {
                for (var i = 0; i < markers.length; i++) {
                    markers[i].setMap(map);
                }
            }

            function clearMarkers() {
                setMapOnAll(null);
            }

            function deleteMarkers() {
                clearMarkers();
                markers = [];
            }

            // Create the search box and link it to the UI element.
            var input = document.getElementById('pac-input-create');
            $("#pac-input-create").val("أبحث هنا ");
            var searchBox = new google.maps.places.SearchBox(input);



            map.controls[google.maps.ControlPosition.TOP_RIGHT].push(input);
            // Bias the SearchBox results towards current map's viewport.
            map.addListener('bounds_changed', function () {
                searchBox.setBounds(map.getBounds());
            });
            var markers = [];
            // Listen for the event fired when the user selects a prediction and retrieve
            // more details for that place.
            searchBox.addListener('places_changed', function () {
                var places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach(function (marker) {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                var bounds = new google.maps.LatLngBounds();
                places.forEach(function (place) {
                    if (!place.geometry) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    var icon = {
                        url: place.icon,
                        size: new google.maps.Size(100, 100),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25)
                    };
                    // Create a marker for each place.
                    markers.push(new google.maps.Marker({
                        map: map,
                        icon: icon,
                        title: place.name,
                        position: place.geometry.location
                    }));
                    $('#latitude').val(place.geometry.location.lat());
                    $('#longitude').val(place.geometry.location.lng());
                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                    window.livewire.emit('set:latitude-longitude', place.geometry.location.lat(), place.geometry.location.lng())
                });
                map.fitBounds(bounds);
            });
        }

        function handleLocationError(browserHasGeolocation, infoWindow, pos) {
            infoWindow.setPosition(pos);
            infoWindow.setContent(browserHasGeolocation ?
                'Error: The Geolocation service failed.' :
                'Error: Your browser doesn\'t support geolocation.');
            infoWindow.open(map);
        }

        function splitLatLng(latLng) {
            var newString = latLng.substring(0, latLng.length - 1);
            var newString2 = newString.substring(1);
            var trainindIdArray = newString2.split(',');
            var lat = trainindIdArray[0];
            var Lng = trainindIdArray[1];
            $("#latitude").val(lat);
            $("#longitude").val(Lng);

            window.livewire.emit('set:latitude-longitude', lat, Lng)
        }

    </script>



        <script type="text/javascript">
            let map, activeInfoWindow, markers = [], searchMarker = null;
            let isBranchMapInitialized = false;

            /* ----------------------------- Initialize Map ----------------------------- */
            function initMap() {
                if (isBranchMapInitialized) return;
                const mapEl = document.getElementById("map");
                if (!mapEl) return;
                isBranchMapInitialized = true;

                map = new google.maps.Map(mapEl, {
                    center: {
                        lat: 24.774265,
                        lng: 46.738586,
                    },
                    zoom: 8,
                    scrollwheel: true,
                });

                map.addListener("click", function(event) {
                    mapClicked(event);
                });

                initMarkers();
                setupSearchBox();
            }

            /* --------------------------- Setup Search Box --------------------------- */
            const instantLocations = {
                'اليمن': { lat: 15.3694, lng: 44.1910, zoom: 7 },
                'yemen': { lat: 15.3694, lng: 44.1910, zoom: 7 },
                'صنعاء': { lat: 15.3694, lng: 44.1910, zoom: 12 },
                'sanaa': { lat: 15.3694, lng: 44.1910, zoom: 12 },
                'عدن': { lat: 12.7855, lng: 45.0187, zoom: 12 },
                'aden': { lat: 12.7855, lng: 45.0187, zoom: 12 },
                'تعز': { lat: 13.5789, lng: 44.0177, zoom: 12 },
                'taiz': { lat: 13.5789, lng: 44.0177, zoom: 12 },
                'الحديدة': { lat: 14.7978, lng: 42.9545, zoom: 12 },
                'hodeidah': { lat: 14.7978, lng: 42.9545, zoom: 12 },
                'حضرموت': { lat: 15.9348, lng: 48.7891, zoom: 8 },
                'المكلا': { lat: 14.5425, lng: 49.1242, zoom: 12 },
                'إب': { lat: 13.9759, lng: 44.1816, zoom: 12 },
                'ibb': { lat: 13.9759, lng: 44.1816, zoom: 12 },
                'ذمار': { lat: 14.5427, lng: 44.4051, zoom: 12 },
                'السعودية': { lat: 23.8859, lng: 45.0792, zoom: 6 },
                'saudi': { lat: 23.8859, lng: 45.0792, zoom: 6 },
                'الرياض': { lat: 24.7136, lng: 46.6753, zoom: 12 },
                'riyadh': { lat: 24.7136, lng: 46.6753, zoom: 12 },
                'جدة': { lat: 21.5433, lng: 39.1728, zoom: 12 },
                'jeddah': { lat: 21.5433, lng: 39.1728, zoom: 12 },
                'مكة': { lat: 21.3891, lng: 39.8579, zoom: 12 },
                'المدينة': { lat: 24.5247, lng: 39.5692, zoom: 12 },
                'الدمام': { lat: 26.4207, lng: 50.0888, zoom: 12 },
                'الخبر': { lat: 26.2172, lng: 50.1971, zoom: 12 },
                'مصر': { lat: 26.8206, lng: 30.8025, zoom: 6 },
                'القاهرة': { lat: 30.0444, lng: 31.2357, zoom: 12 },
                'الإمارات': { lat: 23.4241, lng: 53.8478, zoom: 7 },
                'دبي': { lat: 25.2048, lng: 55.2708, zoom: 12 },
                'أبوظبي': { lat: 24.4539, lng: 54.3773, zoom: 12 }
            };

            function setupSearchBox() {
                const input = document.getElementById('pac-input-create');
                if (!input) return;

                // Listen to Enter key
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchLocationOnMap();
                    }
                });
            }

            /* ------------------- Fallback & Direct Search Function ------------------- */
            function searchLocationOnMap() {
                const input = document.getElementById('pac-input-create');
                const rawQuery = input ? input.value.trim() : '';
                if (!rawQuery) return;

                const query = rawQuery.toLowerCase();
                const statusEl = document.getElementById('search-status-msg');
                if (statusEl) {
                    statusEl.style.display = 'block';
                    statusEl.textContent = 'جاري البحث عن: ' + rawQuery + '...';
                }

                // 1. Instant dictionary match (instant, works offline without waiting for network/QUIC)
                if (instantLocations[query]) {
                    const loc = instantLocations[query];
                    moveToLocation(loc.lat, loc.lng, rawQuery, loc.zoom || 12);
                    if (statusEl) {
                        statusEl.textContent = 'تم تحديد: ' + rawQuery;
                        setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
                    }
                    return;
                }

                // Check for partial dictionary match
                for (const key in instantLocations) {
                    if (query.includes(key) || key.includes(query)) {
                        const loc = instantLocations[key];
                        moveToLocation(loc.lat, loc.lng, rawQuery, loc.zoom || 12);
                        if (statusEl) {
                            statusEl.textContent = 'تم تحديد: ' + rawQuery;
                            setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
                        }
                        return;
                    }
                }

                // 2. Fallback to OpenStreetMap Nominatim with 5s timeout
                fallbackNominatimSearch(rawQuery, statusEl);
            }

            function fallbackNominatimSearch(query, statusEl) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 6000);

                fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query), {
                    signal: controller.signal
                })
                    .then(response => response.json())
                    .then(data => {
                        clearTimeout(timeoutId);
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            moveToLocation(lat, lng, data[0].display_name, 13);
                            if (statusEl) {
                                statusEl.textContent = 'تم العثور على: ' + data[0].display_name;
                                setTimeout(() => { statusEl.style.display = 'none'; }, 4000);
                            }
                        } else {
                            if (statusEl) statusEl.textContent = 'لم يتم العثور على نتائج للموقع المدخل.';
                        }
                    })
                    .catch(err => {
                        clearTimeout(timeoutId);
                        console.error('Search error:', err);
                        if (statusEl) {
                            statusEl.textContent = 'تعذر الاتصال بخدمة البحث بسبب مشاكل في شبكة الإنترنت.';
                        }
                    });
            }

            function moveToLocation(lat, lng, label, zoomLevel) {
                if (!map) return;
                const pos = { lat: lat, lng: lng };
                try {
                    map.setCenter(pos);
                    map.setZoom(zoomLevel || 13);

                    if (searchMarker) {
                        searchMarker.setMap(null);
                    }

                    searchMarker = new google.maps.Marker({
                        position: pos,
                        map: map,
                        animation: google.maps.Animation.DROP,
                        title: label || 'الموقع المختار'
                    });

                    const info = new google.maps.InfoWindow({
                        content: '<b>' + (label || 'الموقع المختار') + '</b><br><small>' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</small>'
                    });
                    info.open(map, searchMarker);
                } catch (e) {
                    console.error('Move error:', e);
                }
            }
            /* --------------------------- Initialize Markers --------------------------- */
            function initMarkers() {
                const result = <?php echo json_encode($branches); ?>;
                var initialMarkers = Object.keys(result).map((key) => [result[key]]);

                console.log(initialMarkers, "initialMarkers");

                for (let index = 0; index < initialMarkers.length; index++) {
                    console.log(initialMarkers.length, "length");
                    const markerData = initialMarkers[index][0];
                    const icon = {
                        url: "{{ asset('images/map/restaurant.png') }}",
                        scaledSize: new google.maps.Size(50, 50),
                        origin: new google.maps.Point(0,0),
                        anchor: new google.maps.Point(0, 0)
                    };
                    console.log(icon, "icccc");
                    const marker = new google.maps.Marker({
                        position: markerData.position,
                        draggable: markerData.draggable,
                        title: "Branch Details",
                        icon: icon,
                        map
                    });
                    markers.push(marker);

                    const infowindow = new google.maps.InfoWindow({
                        content: `<b>Name : <a href="<?php echo url('/'); ?>/admin/merchants/${markerData.merchant_id}" style="color: #0a53be">${markerData.name}</a></b><br><b>Restaurant Name : <a href="<?php echo url('/'); ?>/admin/merchants/${markerData.merchant_id}" style="color: #0a53be">${markerData.restaurant_name}</a></b><br><b>${markerData.position.lat}, ${markerData.position.lng}</b>`,
                    });
                    marker.addListener("click", (event) => {
                        if(activeInfoWindow) {
                            activeInfoWindow.close();
                        }
                        infowindow.open({
                            anchor: marker,
                            shouldFocus: false,
                            map
                        });
                        activeInfoWindow = infowindow;
                        markerClicked(marker, index);
                    });

                    marker.addListener("dragend", (event) => {
                        markerDragEnd(event, index);
                    });
                }
            }

            /* Initialize map when Livewire has loaded */
            document.addEventListener('livewire:load', function () {
                initMap();
            });

            /* ------------------------- Handle Map Click Event ------------------------- */
            function mapClicked(event) {
                console.log(map);
                console.log(event.latLng.lat(), event.latLng.lng());
            }

            /* ------------------------ Handle Marker Click Event ----------------------- */
            function markerClicked(marker, index) {
                console.log(map);
                console.log(marker.position.lat());
                console.log(marker.position.lng());
            }

            /* ----------------------- Handle Marker DragEnd Event ---------------------- */
            function markerDragEnd(event, index) {
                console.log(map);
                console.log(event.latLng.lat());
                console.log(event.latLng.lng());
            }
        </script>
        <!-- <script src="https://maps.googleapis.com/maps/api/js?key={{config('services.google_maps.api_key')}}&callback=initMap"></script> -->
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCIJH6jbULC8MVaAX2LyxIQEBwWPjjeIF0&libraries=places&callback=initMap"></script>
    @endpush
</div>


