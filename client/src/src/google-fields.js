(function($) {

  window.googleFieldsInit = () => {
    console.log('google init')
  }

  function loadPlaceData() {

    $('[data-goldfinch-google-place-field]')

  }

  $(document).ready(() => {

    $('[data-goldfinch-google-place-field]').each((i, e) => {

      let v = $(e).find('[data-goldfinch-place="data"]');

      if (v.length && v.val()) {
        let json = JSON.parse($(e).find('[data-goldfinch-place="data"]').val());

        let preview = $(e).find('[data-goldfinch-place="preview"]');

        let content = '';

        console.log(json)

        preview.html(content)
      }
    })

  });

  $.entwine('ss', function($) {
      $('[data-goldfinch-google-place-field]').entwine({
          onmatch: function() {

            console.log('place entwine')

            var address = $(this).find('[data-goldfinch-place="address"]')[0];
            var data = $(this).find('[data-goldfinch-place="data"]')[0];

            const options = {
              componentRestrictions: { country: 'nz' },
              fields: ['address_components', 'geometry', 'name'],
              strictBounds: false,
            };

            const autocomplete = new google.maps.places.Autocomplete(address, options);

            autocomplete.addListener('place_changed', () => {

                const place = autocomplete.getPlace();
                console.log('place', place)

                data.value = JSON.stringify(place)
            });

          }
      });
      $('[data-goldfinch-google-map-field]').entwine({
        onmatch: function() {

          console.log('map entwine')

          var latitude = $(this).find('[data-goldfinch-map="latitude"]')[0];
          var longitude = $(this).find('[data-goldfinch-map="longitude"]')[0];
          var zoomField = $(this).find('[data-goldfinch-map="zoom"]')[0];
          var frame = $(this).find('[data-goldfinch-map="frame"]')[0];

          let lat = parseFloat(latitude.value);
          let lng = parseFloat(longitude.value);
          let zoom = parseFloat(zoomField.value);

           // defaults
          if (!zoom) zoom = 2
          if (!lat) lat = 0.30391468217020234;
          if (!lng) lng = 71.78142348820073;

          console.log('init vals', lat, lng, zoom)

          let map = new google.maps.Map(frame, {
            center: { lat: lat, lng: lng },
            zoom: zoom,
            gestureHandling: "cooperative",
            fullscreenControl: false,
            mapTypeControl: false,
            clickableIcons: false,
            rotateControl: false,
            scaleControl: false,
            streetViewControl: false,
            zoomControl: false,
          });

          var marker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map
          });

          google.maps.event.addListener(map, 'zoom_changed', (event) => {
            zoomField.value = map.getZoom()
          });

          google.maps.event.addListener(map, 'click', (event) => {
            marker.setPosition(event.latLng)
            map.panTo(event.latLng);

            latitude.value = event.latLng.lat()
            longitude.value = event.latLng.lng()
            zoomField.value = map.getZoom()
          });

        }
    });
  });
}(jQuery));
