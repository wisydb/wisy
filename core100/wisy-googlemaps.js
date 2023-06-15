
/*****************************************************************************
 * google maps stuff
 *****************************************************************************/



// initialization state
var gm_initDone = 0; // 1=success, 2=error

// objects used
var gm_map;
var gm_allMarkers= new Array;
var gm_markerInView = 0;

function gm_panToNext()
{	
	// function pans the map to the next marker in gm_allMarkers, loops on end
	// this function may only be called if gm_initPan() has succeeded
	
	gm_markerInView = gm_markerInView+1;
	if( gm_markerInView >= gm_allAdr.length ) gm_markerInView = 0;
	
	if( gm_allMarkers[gm_markerInView] )
	{
		gm_map.panTo(gm_allMarkers[gm_markerInView].getPoint())
		gm_allMarkers[gm_markerInView].openInfoWindowHtml(gm_allDescr[gm_markerInView]);
	}
	else
	{
		var geocoder = new GClientGeocoder();
		geocoder.getLatLng
		(
			gm_allAdr[gm_markerInView],
			function(point)
			{
				if( !point )
				{
					alert(gm_allAdr[gm_markerInView] + ' nicht gefunden.');
				}
				else
				{
					gm_map.panTo(point);
					gm_allMarkers[gm_markerInView] = new GMarker(point);
					gm_allMarkers[gm_markerInView].myDescr = gm_allDescr[gm_markerInView];
					GEvent.addListener(gm_allMarkers[gm_markerInView], 'click', function() {
						this.openInfoWindowHtml(this.myDescr);
					});
					gm_map.addOverlay(gm_allMarkers[gm_markerInView]);
					gm_allMarkers[gm_markerInView].openInfoWindowHtml(gm_allDescr[gm_markerInView]);
				}
			}
		);						
	}
}



function gm_initPan(quality)
{
	// init the pan to one of the three resolution qualities in gm_initAdr
	
	// create a async. geocode
	var geocoder = new GClientGeocoder();
	geocoder.getLatLng
	(
		gm_initAdr[quality],
		function(point)
		{
			// async geocoder event:
			if( !point )
			{
				// geocoding failed ...
				if( quality >= 2 || gm_initAdr[quality+1]=='' )
				{
					// ... nothing found at all
					$('#wisy_map2Anchor').hide();
					$('#wisy_map2').hide();
					gm_initDone = 2; // error
					return;
				}
				else if( quality == 0 )
				{
					// ... try again with fallback #1
					window.setTimeout(function() {gm_initPan(1);}, 500);
					return;
				}
				else if( quality == 1 )
				{
					// ... try again with fallback #2
					window.setTimeout(function() {gm_initPan(2);}, 500);
					return;
				}
			}
			else
			{
				// geocoding succeeded!

				// center the map:
				// we move the center a little bit down to avoid a scrolling when the info window opens;
				// the offset is fine for "street view" (zoom 15)
				var center = new GLatLng(point.lat()+0.0016, point.lng()); 
				gm_map.setCenter(center, gm_initZoom[quality]);
				
				// add a marker at the calculated point
				gm_allMarkers[0] = new GMarker(point);
				gm_allMarkers[0].myDescr = gm_allDescr[0];
				GEvent.addListener(gm_allMarkers[0], 'click', function() {
					this.openInfoWindowHtml(this.myDescr);
				});
				gm_map.addOverlay(gm_allMarkers[0]);
			}

			// done - success
			gm_initDone = 1;
		}
	);
}


// maps inititalization

$.fn.initWisyMap = function()
{
	// init the gm_map and all needed objects, this function is called after the
	// page has loaded completely

	if( typeof(GBrowserIsCompatible) == 'undefined' )
	{
		return;
	}
		
	if( !GBrowserIsCompatible() )
	{
		return;
	}
	
	$(window).unload( function () { GUnload(); } );

	gm_map = new GMap2(document.getElementById("wisy_map2"));

	gm_map.addMapType(G_PHYSICAL_MAP);
    gm_map.addControl(new GMenuMapTypeControl());
    gm_map.addControl(new GLargeMapControl());
	
	gm_initPan(0);	
	
	return;
}

function gm_mapHere()
{
	document.write('<div class="wisy_vcard" id="wisy_map2Anchor"><div class="wisy_vcardtitle">Angebotsort und Umgebung</div><div id="wisy_map2"></div></div>');
}

var gm_Initialized = false;
$().ready(function()
{
    if(gm_Initialized) return;
    gm_Initialized = true;

	// init maps
	$("#wisy_map2").initWisyMap();
});