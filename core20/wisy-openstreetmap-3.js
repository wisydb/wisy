

/* Generic stuff
 *****************************************************************************/

// global object containing the map object, valid after osm_initialize finishes
var osm_map = false;
 
// these arrays must be set up by the caller 
// for the first element, lat/lng may be set to 0; the javascript part will
// then call the geocoder for them
var osm_mark_dfid = new Array; 
var osm_mark_lat  = new Array; 
var osm_mark_lng  = new Array;
var osm_mark_html = new Array;



// after setting the osm_mark_* arrays, the call should call osm_map_here();
// 
function osm_map_here()
{
	document.write('<div class="wisy_vcard" id="wisy_map2Anchor"><div class="wisy_vcardtitle">Angebotsort und Umgebung</div><div id="wisy_map2"></div></div>');
	
	var document_ready_called = false;
	$(document).ready(function()
	{
		if(document_ready_called) return;
		document_ready_called = true; 
			
		if( osm_mark_lat[0] == 0 && osm_mark_lng[0] == 0 ) {
			// call the external gecoder and wait for response, then initialize the OSM map
			$.getJSON('geocode?geocodedfid='+osm_mark_dfid[0], function(json_data) {
				if( typeof json_data['error'] != 'undefined' ) {
					$('#wisy_map2Anchor').hide();
				}
				else {
					osm_mark_lat[0] = json_data['lat'];
					osm_mark_lng[0] = json_data['lng'];
					osm_initialize();
				}
			});
		}
		else {
			// initialize the OSM map directly
			osm_initialize();
		}
	});
}



/* Implementation using leaflet.js
 *****************************************************************************/
 

function osm_initialize()
{
	// initialize only once
	if( osm_map ) return;

	// create the map
	osm_map = L.map('wisy_map2', {
		zoomControl: false,		// we want the zoom on the right, not the default on the left
		fadeAnimation: false,	// just display after loading, faster
	});
	osm_map.attributionControl.setPrefix(''); // remove leavlet-link, we add attribution to this at another, better, no-js place
	osm_map.addControl(new L.Control.Zoom({position:'topright'}));
	
	// create map layer
	L.tileLayer('http://otile3.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="g7091">OpenStreetMap-Mitwirkende</a>'
	}).addTo(osm_map);	
	
	// create a fine icon
	var ouricon = L.icon({
		iconUrl: 'core20/img/mapmarker.png', 
		iconSize: [21, 25],
		iconAnchor: [10, 25],
		popupAnchor: [0, -18],
	});
	
	// set markers, calulate bounding rectangle (lat=y=breitengrad (-90..90), lng=x=laengengrad(0..180))
	var bounds = new L.LatLngBounds();
	for( var i = 0; i < osm_mark_lat.length; i++ ) {
		var point = new L.LatLng(osm_mark_lat[i], osm_mark_lng[i]);
		bounds.extend(point);
		var marker = L.marker(point, {icon:ouricon}).addTo(osm_map);
		marker.bindPopup(osm_mark_html[i]);
	}
	
	// set initial view
	if( osm_mark_lat.length == 1 ) {
		osm_map.setView([osm_mark_lat[0], osm_mark_lng[0]], 16);
	}
	else {
		osm_map.fitBounds(bounds);
	}
}

