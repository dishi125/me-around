function initialize() {

    $('form').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });
    const locationInputs = $(".map-input");

    const autocompletes = [];
    const geocoder = new google.maps.Geocoder;
    for (let i = 0; i < locationInputs.length; i++) {

        const input = locationInputs[i];
        const fieldKey = input.id.replace("-input", "");
        const isEdit = $('#'+fieldKey + "-latitude").val() != '' && $('#'+fieldKey + "-longitude").val() != '';
        //const isEdit = document.getElementById(fieldKey + "-latitude").value != '' && document.getElementById(fieldKey + "-longitude").value != '';

        let defaultRadios = parseInt($('input[name="expose_distance"]').val());
        let defaultPlaceRadios = parseInt($('input[name="expose_distance"]').attr('placeholder'));

        if(!defaultRadios){
            defaultRadios = defaultPlaceRadios || 1;
        }

        const latitude = parseFloat($('#'+fieldKey + "-latitude").val()) || -33.8688;
        const longitude = parseFloat($('#'+fieldKey + "-longitude").val()) || 151.2195;
        let circle = {};

        const map = new google.maps.Map(document.getElementById(fieldKey + '-map'), {
            center: {lat: latitude, lng: longitude},
            zoom: 13
        });

        const marker = new google.maps.Marker({
            map: map,
            position: {lat: latitude, lng: longitude},
            draggable: true
        });

        circle = setMapCircle(map,defaultRadios,marker.getPosition(),'init',circle,marker);
        //_this.circle.bindTo('center', marker, 'position');

        if ($("input[name=expose_distance]").length > 0) {
            document.querySelector("input[name=expose_distance]").addEventListener('keyup', function () {
                //map.debounce();
                let radious = defaultPlaceRadios || 1;
                if (this.value) {
                    radious = this.value;
                }
                setMapCircle(map, radious, marker.getPosition(), 'update', circle, marker);
            });
        }
        marker.setVisible(isEdit);

        const autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.key = fieldKey;
        autocompletes.push({input: input, map: map, marker: marker, autocomplete: autocomplete});

        marker.addListener('dragend', function () {
            let newZoom = getZoomLevel(circle);
            map.setZoom(newZoom);
            map.setCenter(marker.getPosition());
            geocoder.geocode({
                latLng: marker.getPosition()
            }, function (responses) {
                input.value = responses[0].formatted_address;
                filterLocation(responses,autocomplete.key);
            });
        })
    }

    for (let i = 0; i < autocompletes.length; i++) {
        const input = autocompletes[i].input;
        const autocomplete = autocompletes[i].autocomplete;
        const map = autocompletes[i].map;
        const marker = autocompletes[i].marker;

        google.maps.event.addListener(autocomplete, 'place_changed', function () {
            marker.setVisible(false);
            const place = autocomplete.getPlace();


            geocoder.geocode({'placeId': place.place_id}, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    console.log(results);
                    filterLocation(results,autocomplete.key);
                }
            });

            if (!place.geometry) {
                window.alert("No details available for input: '" + place.name + "'");
                input.value = "";
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(13);
            }
            map.setZoom(13);
            map.setCenter(place.geometry.location);
            marker.setPosition(place.geometry.location);
            marker.setVisible(true);

        });
    }
}

function setMapCircle(map,radios,latLng,method,circle,marker){
    if(method == 'init'){
        var sunCircle = {
            strokeColor: "#6777ef",
            strokeOpacity: 1,
            strokeWeight: 2,
        // fillColor: "#c3fc49",
            fillOpacity: 0,
            map: map,
            center: latLng,
            radius: radios * 1000
        };
        circle = new google.maps.Circle(sunCircle);
        circle.bindTo('center', marker, 'position');
    }else{
        circle.setRadius(radios * 1000);
    }

    if(radios > 0){
        let newZoom = getZoomLevel(circle);
        map.setZoom(newZoom);
    }else{
        map.setZoom(15);
    }

    // Retrieve shops within the circle
    var latitude = latLng.lat();
    var longitude = latLng.lng();
    var url = baseUrl + "/admin/shops-in-circle?latitude=" + latitude + "&longitude=" + longitude + "&distance=" + radios;
    // Make an AJAX request to fetch the shops
    /*$.get(url, function (data) {
        // Process the data and display the shops on the map
        displayShopsOnMap(data,map);
    });*/
    if ($("#circle-lat").length > 0){
        $("#circle-lat").val(latitude);
    }
    if ($("#circle-long").length > 0){
        $("#circle-long").val(longitude);
    }
    if ($("#circle-distance").length > 0){
        $("#circle-distance").val(radios);
    }

    return circle;
}

function getZoomLevel(circle){
    zoomLevel = 15;
    if (circle != null){
        let radius = circle.getRadius();
        let scale = radius / 500;
        zoomLevel =(16 - Math.log(scale) / Math.log(2)) - .5;
    }
    console.log(zoomLevel)
    return zoomLevel;
}

function filterLocation(results,key){
    const lat = results[0].geometry.location.lat();
    const lng = results[0].geometry.location.lng();
    var city = region = country = "";

    for(var i=0; i<results[0].address_components.length; i++)
    {
        if (results[0].address_components[i].types[0] == "locality") {
            city = results[0].address_components[i].long_name;
        }
        if (results[0].address_components[i].types[0] == "administrative_area_level_1") {
            region = results[0].address_components[i].long_name;
        }
        if (results[0].address_components[i].types[0] == "country") {
            country = results[0].address_components[i].long_name;
        }
    }
    setLocationCoordinates(key, lat, lng, city, region, country);
}

function setLocationCoordinates(key, lat, lng, city, region, country) {
    $('#'+key+'-latitude').val(lat);
    $('#'+key+'-longitude').val(lng)
    $('#'+key+'-city').val(city)
    $('#'+key+'-state').val(region)
    $('#'+key+'-country').val(country)
}

function displayShopsOnMap(shops,map) {
    // Process the retrieved shops and display them on the map
    for (var i = 0; i < shops.length; i++) {
        var shop = shops[i];

        // Create a marker for each shop and add it to the map
        var shopMarker = new google.maps.Marker({
            position: { lat: shop.latitude, lng: shop.longitude },
            map: map,
            title: shop.main_name,
            icon: {
                url: 'http://maps.google.com/mapfiles/ms/icons/blue.png', // Specify the URL of the marker icon
                scaledSize: new google.maps.Size(32, 32), // Adjust the size of the marker icon
            },
        });

        // You can customize the marker icon, info window, etc. here

        // Add the marker to the global marker array if needed
        // markers.push(shopMarker);
    }
}
