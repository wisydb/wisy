


function imp_initOptionsPage()
{
	$('td a[href^="edit.php?"]').attr('title', 'Datensatz ansehen').attr('target', '_blank').attr('rel', 'noopener noreferrer');
	
	$('td img[id^="impa"]')
		//.css('cursor', 'pointer')
		//.click(function() {alert(1);})
		.attr('title', "Pfeil: Datensatz wird importiert\nGleichheitsz.: Datensaetze sind identisch\nUngleich: Datensatz wird uebersprungen\nX: Datensatz wird geloescht");
}



function imp_initFilesPage()
{
	$('form[name="uploadform"] input[type="file"]').change(function() {
			$(this).after(AJAX_INDICATOR + ' ' + _ONEMOMENTPLEASE);
			$(this).hide();
			
			$('form[name="uploadform"]').submit();
	});
}



$().ready(function() {

	imp_initFilesPage();
	imp_initOptionsPage();
	
});


