(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.StoreLocatorFunction = {
    attach: function (context, settings) {

      $('.marker-link', context).once('store-locator').each(function () {
        $(this).click(function (event) {
          event.preventDefault();
          var lat = parseFloat($(this).attr('data-lat'));
          var lng = parseFloat($(this).attr('data-lng'));

          panToMarker(lat, lng);
        });
      });

      $('.toggle-buttons-wrapper input').once().click(function (event) {
        var className = $(this).val().replace(/_/g, '-');
        $('.views-exposed-form #form-item-title, .views-exposed-form .geolocation-views-filter-geocoder').val('');
        $('.views-exposed-form .form-item-geolocation-geocoder-google-geocoding-api, .views-exposed-form .form-item-title').hide();
        $('.views-exposed-form .form-item-' + className).show();
        $('.views-exposed-form input[name="field_location_proximity-lat"]').val('');
        $('.views-exposed-form input[name="field_location_proximity-lng"]').val('');

        //rimuovo classe 'active-X' (active-posizione)
        //per aggiungere quella attuale dinamica        
        $(".views-exposed-form").removeClass(function (index, className) {
          return (className.match(/(^|\s)active-\S+/g) || []).join(' ');
        });
        $(".views-exposed-form").addClass("active-" + className);

        //è il tab aggiunto da stl_add_toggler
        //qua parte la geolocalizzazione da browser
        if (className === 'posizione') {
          getGeolocationBrowser();
        }
      });
    }
  };

  /**/
  function getGeolocationBrowser() {

    function success(position) {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;

      $('input[name="field_location_proximity-lat"]').val(latitude);
      $('input[name="field_location_proximity-lng"]').val(longitude);

      $('.store-locator .form-submit').attr('disabled', false);
      $('.store-locator .form-submit').trigger('click');

    }

    function error() {
      alert('Impossibile determinare la tua posizione.');
      $('.store-locator .form-submit').attr('disabled', true);
    }

    if (!navigator.geolocation) {
      alert('Il tuo browser non supporta la geolocalizzazione, riprova con Google Chrome o Firefox.');
    } else {
      navigator.geolocation.getCurrentPosition(success, error);
    }

  }

})(jQuery, Drupal, drupalSettings);

/*
 * Centra la mappa su una coordinata e setta lo zoom
 * */
function panToMarker(lat, lng)
{
  var position = new google.maps.LatLng({lat: lat, lng: lng});
  console.log(Drupal.geolocation.maps[0].mapMarkers[0]);
  Drupal.geolocation.maps[0].googleMap.panTo(position);
  Drupal.geolocation.maps[0].googleMap.setZoom(17.5);
}

(function ($, window, undefined) {

  var checkSubmitEnabled = function (el) {
    var disabledField = !($(el).val().length > 0);
    $('#edit-submit-vista-store').attr('disabled', disabledField);
    $('.store-locator .form-submit').attr('disabled', disabledField);
  };

  $(document).on('click', '.toggle-map-button', function (e) {
    e.preventDefault();
    e.stopPropagation();

    $('body').toggleClass('map-is-shown');
  });
  $('#edit-submit-vista-store').attr('disabled', true);
  $(document).on('keyup', '#form-item-geolocation-geocoder-google-geocoding-api, #form-item-title', function (e) {
    checkSubmitEnabled(this);
  });

  $(document).on('click', '#edit-toggler-title', function (e) {
    checkSubmitEnabled('#form-item-title');
  });
  $(document).on('click', '#edit-toggler-geolocation-geocoder-google-geocoding-api', function (e) {
    checkSubmitEnabled('#form-item-geolocation-geocoder-google-geocoding-api');
  })

})(jQuery, window);