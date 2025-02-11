(function($) {
    var geolocatedCenter = null;
    var myLocationControlEl = null;
    var mlm_marker = null;
    var map = null;

    // Custom "My Location" control.
    function MyLocationControl(controlDiv, mapInstance) {
        var controlUI = document.createElement('div');
        controlUI.className = 'my-location-control';
        controlUI.title = 'My location';
        controlDiv.appendChild(controlUI);

        myLocationControlEl = controlUI;

        var controlImg = document.createElement('img');
        // Simple location icon (SVG)
        controlImg.src = 'data:image/svg+xml;utf8,' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">' +
            '<path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 ' +
            '4-1.79 4-4-1.79-4-4-4zm0-6C6.48 2 2 6.48 2 12s4.48 10 ' +
            '10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s' +
            '3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/></svg>';
        controlUI.appendChild(controlImg);

        // Click: try to geolocate user
        controlUI.addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    mapInstance.setCenter(pos);
                    mapInstance.setZoom(18);
                    controlUI.classList.add('active');
                    geolocatedCenter = pos;
                }, function() {
                    alert("Geolocation service failed.");
                });
            } else {
                alert("Your browser doesn't support geolocation.");
            }
        });
    }

    // Tab switching for parent categories
    function openMlmTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("mlm_tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("mlm_tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    // Expose globally (so inline HTML can call openMlmTab)
    window.openMlmTab = openMlmTab;

    // Initialize the map.
    function initMap() {
        var initialCenter = new google.maps.LatLng(51, 0);
        var mapOptions = {
            center: initialCenter,
            zoom: 3,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            gestureHandling: 'greedy'
        };

        map = new google.maps.Map(document.getElementById("mlm_map_canvas"), mapOptions);
        var crosshair = document.getElementById('mlm_crosshair');

        // Setup Google Places Autocomplete
        var input = document.getElementById('mlm_map_search_input');
        var autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.bindTo('bounds', map);
        autocomplete.addListener('place_changed', function() {
            $(".my-location-control").removeClass("active");
            geolocatedCenter = null;

            var place = autocomplete.getPlace();
            if (!place.geometry) {
                alert("No details available for input: '" + place.name + "'");
                return;
            }
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }
        });

        // Clear search functionality
        $('#clear_search').on('click', function(e) {
            e.preventDefault();
            $('#mlm_map_search_input').val('');
            map.setCenter(initialCenter);
            map.setZoom(3);
            map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
            crosshair.classList.remove('confirmed');
            $('#confirm_location').prop('disabled', true).removeClass('enabled');
            $(".my-location-control").removeClass("active");
            geolocatedCenter = null;
            $('.mlm_map_instructions').show();
            $('.mlm_extra_fields').hide();
        });

        // Add custom "My Location" control on the bottom-right
        var locationControlDiv = document.createElement('div');
        MyLocationControl(locationControlDiv, map);
        map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(locationControlDiv);

        // Zoom change behavior
        map.addListener('zoom_changed', function() {
            var zoomLevel = map.getZoom();
            if (zoomLevel >= 18) {
                map.setMapTypeId(google.maps.MapTypeId.HYBRID);
                map.setTilt(0);
                crosshair.classList.add('confirmed');
                $('#confirm_location').prop('disabled', false).addClass('enabled');
            } else {
                map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
                crosshair.classList.remove('confirmed');
                $('#confirm_location').prop('disabled', true).removeClass('enabled');
                if ($('.mlm_extra_fields').is(':visible')) {
                    $('.mlm_extra_fields').hide();
                    $('.mlm_map_instructions').show();
                }
            }
        });

        // If user moves the map center after geolocation, remove the "active" state
        map.addListener('idle', function() {
            if (geolocatedCenter !== null) {
                var currentCenter = map.getCenter();
                if (Math.abs(currentCenter.lat() - geolocatedCenter.lat()) > 0.0001 ||
                    Math.abs(currentCenter.lng() - geolocatedCenter.lng()) > 0.0001) {
                    $(".my-location-control").removeClass("active");
                    geolocatedCenter = null;
                }
            }
        });

        // Confirm Location button
        $('#confirm_location').on('click', function(e) {
            e.preventDefault();
            var center = map.getCenter();
            var geocoder = new google.maps.Geocoder();

            geocoder.geocode({ 'location': center }, function(results, status) {
                if (status === google.maps.GeocoderStatus.OK && results[0]) {
                    $('#location_address').val(results[0].formatted_address);
                }
            });
            $('.mlm_map_instructions').hide();
            $('.mlm_extra_fields').show();
        });
    }

    // Document ready: check if Google Maps API is loaded, then init the map
    $(document).ready(function() {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initMap();
        } else {
            console.error('Google Maps API not loaded.');
        }
    });

    // ========== Handle Location Type Selection via AJAX ==========
    // When a location type radio button is clicked,
    // 1) set the marker icon,
    // 2) fetch dynamic custom fields from the server,
    // 3) show/hide the spinner.
    $(document).on("click", ".mlm_form_type", function() {
        var that = $(this);

        // Update hidden icon URL field with the chosen location type icon
        var iconurl = that.closest('.cat_row').find('img').attr('src');
        $('#mlm_form_icon').val(iconurl);

        var typeicon = {
            url: iconurl,
            scaledSize: new google.maps.Size(32, 37)
        };

        // If marker exists, update it; otherwise, create a new marker
        if (mlm_marker) {
            mlm_marker.setIcon(typeicon);
            mlm_marker.setPosition(map.getCenter());
        } else {
            mlm_marker = new google.maps.Marker({
                position: map.getCenter(),
                map: map,
                icon: typeicon
            });
        }

        // Prepare data for AJAX request
        var data = {
            action: "mlm_get_term_fields",
            type_id: that.val(),
            location_child_id: that.attr('data-locationchildid'),
            security: mlm_ajax_object.nonce
        };

        // Show spinner for this .cat_row
        that.closest('.cat_row').find('span > img').show();

        // AJAX: get custom fields for this location type
        $.post(mlm_ajax_object.ajax_url, data, function(response) {
            if (response.success) {
                // Insert the returned HTML into .mlm_form_type_fields
                $('.mlm_form_type_fields').html(response.data);
            } else {
                $('.mlm_form_type_fields').html('<p>Error loading fields.</p>');
            }
            // Hide spinner after AJAX completes
            that.closest('.cat_row').find('span > img').hide();
        });
    });

})(jQuery);
