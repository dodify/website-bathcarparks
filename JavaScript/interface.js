/**
 * Bath Car Parks JavaScript interface file.
 *
 * Handles all JavaScript front end interactions with the user and builds map.
 *
 * @copyright   2014 dodify Ltd.
 * @license     See LICENSE in repository root
 */

var iconBase = "Images/Icons/";
var markers  = [];

/*
 * Handles map creation and events.
 */
$(document).ready(function() {

    // Build map
    var minH = $('#info').height() + 300;
    var broH = $(window).height();
    $('#map').height((minH > broH ? minH : broH));
    var map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(51.380579, -2.360215),
        zoom: 13,
        mapTypeId: google.maps.MapTypeId.ROAD
    });
    map.set('styles', [{ "stylers": [{ "hue": "#5CB1E1" }]}]);
    
    // Add car parks
    for(var i = 0; i < cps.length; i++) {
        var cp = cps[i];
        var marker = new google.maps.Marker({ 
            position: new google.maps.LatLng(
                cp.location.latitude, 
                cp.location.longitude
            ),
            map:   map,
            id:    cp.id,
            title: cp.name,
            icon:  iconBase + cp.icon,
            iconF: cp.icon,
        });
        markers[cp.id] = marker;
        
        // Click event on marker
        google.maps.event.addListener(marker, 'click', function() {
            selectCp(this.id);
            $('html, body').animate(
                { scrollTop: $('#' + this.id).offset().top + 'px' }, 'fast');
        });
    }

    // Set "Go!" buttons if user location is available
    if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            $(".dirlink").each(function() {
                var lId   = $(this).attr('id');
                var tId   = lId.substr(0, lId.length - 3);
                var cLati = pos.coords.latitude;
                var cLong = pos.coords.longitude;
                var pLati = markers[tId].position.B;
                var pLong = markers[tId].position.k;
                $(this).attr('href', 'https://www.google.com/maps/dir/' + 
                    cLati + ',' + cLong + '/' + pLong + ',' + pLati);
            });
        });
    } else {
       $('.directions').remove();
    }
    
    // Click event on car park list
    $('.cp img').click(function() {
        selectCp($(this).closest('tr').attr('id'));
    });

    // Set external links
    $('a[rel="external"]').each(function() {
		$(this).attr('target', '_blank');
	}); 
});

/*
 * Select car park.
 *
 * Opens left hand column on selected car park and changes map icon for easy
 * identification.
 */
function selectCp(id) {
    $('.cp').css('background-color', '#FFFFFF');
    $('#' + id).css('background-color', '#5CB1E1');
    $('.info').hide();
    $('.' + id).fadeIn();
    for(var idt in markers) {
        if(markers[idt].id != id) {
            markers[idt].setIcon(iconBase + markers[idt].iconF);
        }
    }
    markers[id].setIcon(iconBase + 's' + markers[id].iconF);
}