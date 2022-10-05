

function log_clickOlderEntries()
{
	$(this).hide();
	$(this).after(AJAX_INDICATOR + ' ' + _ONEMOMENTPLEASE);
	
	var jqRow   = $(this).parent().parent();
	var jqTable = jqRow.parent(); // jqTable is <table> or <tbody> now
	
	$('<div>').load(this.href+'&ajax=1', function(responseText) {
		jqRow.remove();
		jqTable.append(responseText);
		log_initLinks();
	});
	
	return false;
}


function log_clickDetails()
{
	if( $(this).parent().find('.log_moreDiv2').length )
	{
		$(this).parent().find('.log_moreDiv2').toggle();
	}
	else
	{
		$(this).after('<div class="log_moreDiv2">' + AJAX_INDICATOR + '</div>');
		$(this).parent().find('.log_moreDiv2').load(this.href+'&ajax=1', function() {
			log_initLinks();
		});
	}
	return false;
}


function log_initLinks()
{
	// init the "older entries link"
	$('.log_showOlderEntries').click(log_clickOlderEntries).removeClass('log_showOlderEntries');
	
	// init the "show details" links 
	$('.log_dt').click(log_clickDetails).attr('title', 'Details anzeigen/verbergen').removeClass('log_dt');
	
	// set title attributes
	$('td a[href^="edit.php?"]').attr('title', 'Datensatz bearbeiten').attr('target', '_blank').attr('rel', 'noopener noreferrer');
	$('td a[href^="log.php?user="]').attr('title', 'Protokolleintr&auml;ge mit diesem Benutzer suchen');
	$('td a[href^="log.php?table="]').attr('title', 'Protokolleintr&auml;ge mit diesem Suchkriterium suchen');
}



$().ready(function()
{
	log_initLinks();
});

