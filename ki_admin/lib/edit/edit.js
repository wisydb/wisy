

var ARROW_DOWN  = '&#x25bc;'; // from http://www.fileformat.info/info/unicode/block/geometric_shapes/utf8test.htm
var ARROW_UP	= '&#x25b2;';
var ARROW_RIGHT = '&#x25ba;';
var ARROW_LEFT	= '&#x25C0;';

var AREA_DOWN = '&darr;';
var AREA_UP   = '&uarr;';

var DUPL_PREFIX = 'Duplikat von ';



var rcv_id_param = 0;
function rcv_id_selection(id)
{
	if( rcv_id_param === 0 ) { alert('Das Einfügeziel exitiert nicht mehr'); return; }
	if( rcv_id_param.hasClass('e_toolbar') ) {
		secondary_insert_from_2(rcv_id_param, id);
	}
	else if( rcv_id_param.hasClass('e_attr') ) {
		attr_input_dblclick_2(rcv_id_param, id);
	}
	
}

/******************************************************************************
Secondary Object Handling
******************************************************************************/

function get_toolbar_ob_(theLink)
{
	var toolbar_ob = $(theLink).parent();
	if( !toolbar_ob.hasClass('e_toolbar') )
	{
		toolbar_ob = toolbar_ob.parent(); 
		if( !toolbar_ob.hasClass('e_toolbar') ) { alert('bad object!'); return; }
	}
	return toolbar_ob;
}

function secondary_show_hide_do_(toolbar_ob, show, animate /*may be left out, predefinition below*/)
{
	animate = typeof animate=='undefined'? true : animate;
	
	var icon_ob = toolbar_ob.find('.e_icon');
	var object_ob = toolbar_ob.next();
	
	if( show )
	{
		object_ob.slideDown(ANIM_DURATION);
		icon_ob.html(ARROW_DOWN);
	}
	else // hide
	{
		if( animate )
			object_ob.slideUp(ANIM_DURATION);
		else
			object_ob.hide();
		icon_ob.html(ARROW_RIGHT);
	}
}

function secondary_show_hide_all_(show)
{
	$('div.e_secondary > div.e_toolbar').each(function() {
		secondary_show_hide_do_($(this), show, true);
	});
}

function secondary_show_hide_click(theLink)
{
	var toolbar_ob = get_toolbar_ob_(theLink);
	var object_ob = toolbar_ob.next();
	
	var last_click = parseInt(toolbar_ob.attr('data-last-click-ms')); if( isNaN(last_click) ) { last_click=0; }
	if( last_click+700 < new Date().getTime() )
	{
		var do_show = object_ob.is(':hidden');
		toolbar_ob.attr('data-dlbclick-show', do_show? 'show' : 'hide'); // as secondary_show_hide_do_() will take some ms, set the state before
		toolbar_ob.attr('data-last-click-ms', new Date().getTime());
		secondary_show_hide_do_(toolbar_ob, do_show);
	}
}

function secondary_show_hide_dblclick(theLink)
{
	var toolbar_ob = get_toolbar_ob_(theLink); // if( toolbar_ob.attr('data-dlbclick-show')==='undefined' ) { return; }
	var do_show = toolbar_ob.attr('data-dlbclick-show');
	secondary_show_hide_all_(do_show=='show'? true : false);
}

function secondary_up_down(theLink, up)
{
	var toolbar_ob = get_toolbar_ob_(theLink);
	var object_ob = toolbar_ob.next();
	
	if( up )
	{
		// move the object above the one above
		var above_ob = toolbar_ob.prev().prev();
		if( above_ob.hasClass('e_toolbar') )
		{
			var slide_ob=false;
			if(object_ob.is(':visible')) {slide_ob=true; object_ob.slideUp(ANIM_DURATION);} 
			toolbar_ob.slideUp(ANIM_DURATION, function(){
				toolbar_ob.detach(); object_ob.detach();
				above_ob.before(toolbar_ob); above_ob.before(object_ob);
				toolbar_ob.slideDown(ANIM_DURATION);  if( slide_ob ) { object_ob.slideDown(ANIM_DURATION); }
			});
		}
	}
	else
	{
		// move the objet below the one below
		var below_ob = object_ob.next().next();
		if( below_ob.hasClass('e_object') )
		{
			var slide_ob=false;
			if(object_ob.is(':visible')) {slide_ob=true; object_ob.slideUp(ANIM_DURATION);} 
			toolbar_ob.slideUp(ANIM_DURATION, function() {
				toolbar_ob.detach(); object_ob.detach();
				below_ob.after(object_ob); below_ob.after(toolbar_ob);
				toolbar_ob.slideDown(ANIM_DURATION);  if( slide_ob ) { object_ob.slideDown(ANIM_DURATION); }
			});
		}
	}
}

function secondary_add(template_id)
{
	var template_ob  = $('div.e_template[data-template-id="'+template_id+'"]');
	var object_ob = template_ob.find('div.e_object');
	var toolbar_ob = object_ob.prev();
	
	var cloned_tb = toolbar_ob.clone();
	var cloned_ob = object_ob.clone();
	cloned_ob.hide();
	
	var anchor_ob = object_ob.parent(); // is now e_template
	anchor_ob.before(cloned_tb);
	anchor_ob.before(cloned_ob);
	secondary_show_hide_do_(cloned_tb, true);
	controls_init(cloned_ob);
}

function secondary_duplicate(theLink)
{
	var toolbar_ob = get_toolbar_ob_(theLink);
	var object_ob = toolbar_ob.next();
	
	var cloned_tb = toolbar_ob.clone();
	var cloned_ob = object_ob.clone();
	cloned_ob.hide();
	var title_ob = cloned_tb.find('.e_title');
	var txt = title_ob.html();
	if( txt.indexOf(DUPL_PREFIX) != 0 )
		txt = DUPL_PREFIX + txt;
	title_ob.html(txt);
	
	// set the ID of the duplicate to -1
	cloned_ob.find('input[data-meta=object_start]').val('-1');
	
	// show the duplicate
	object_ob.after(cloned_ob);
	object_ob.after(cloned_tb);
	secondary_show_hide_do_(cloned_tb, true);
	controls_init(cloned_ob);
}

function secondary_insert_from_1(theLink)
{	
	var primary_table = $('form[action*=edit] input[name=table]').val();
	var url = 'index.php?table=' + primary_table + '&selectobject';
	rcv_id_param = get_toolbar_ob_(theLink);
	popup_width = parseInt($('body').width() * 0.9); if( popup_width < 640 ) popup_width = 640; // use a simelar width so that all columns will fit
	popup(theLink, popup_width, 500, url, 'selectobjecttarget');
}
function secondary_insert_from_2(toolbar_ob, to_insert_id)
{
	var object_ob = toolbar_ob.next();
	var secondary_ob = object_ob.parent(); if( !secondary_ob.hasClass('e_secondary') ) { alert('bad object/if1!'); return; }
	var secondary_hidden_idstr = secondary_ob.find('input[name*=_secondary_start]').attr('name'); // sth. like f_durchfuehrung_secondary_start
	var primary_table = $('form[action*=edit] input[name=table]').val();
	
	var to_insert_id = parseInt(to_insert_id);
	if( isNaN(to_insert_id) ) { alert('bad id'); return; }
	
	$('body').append('<div id="insert_anchor" style="display:none;"></div>');
	anchor_ob = $('#insert_anchor');
	anchor_ob.load('edit.php?getasajax&table=' + primary_table + '&id=' + to_insert_id, function(data) {
		// find the secondary data in the loaded data
		inserted_secondary_ob = anchor_ob.find('input[name=' + secondary_hidden_idstr + ']').parent();
		if( !inserted_secondary_ob.hasClass('e_secondary') ) { alert('bad object!'); return; }
	
		// get the direct descendents of e_secondary 
		inserted_objects_ob = inserted_secondary_ob.find('> .e_object');

		if( inserted_objects_ob.length == 0 ) {
			alert('Das ausgewählte Angebot hat keine Durchführungen, die man einfügen könnte.');
		}
		else {
			inserted_objects_ob.reverse().each(function() {
				var insert_ob = $(this); 
				var id_ob  = insert_ob.find('input[data-meta=object_start]');
				if( id_ob.length == 1 ) {
					id_ob.val('-1');
					object_ob.after(insert_ob);
					object_ob.after(create_toolbar_html('Eingefügte Durchführung'));
					controls_init(insert_ob);
				}
				else {
					alert('Kann die ID der ausgewählten Durchführung nicht zurücksetzen.');
				}
			});
		}
		
		// remove the dummy object from DOM
		anchor_ob.remove();
	});
}

function secondary_delete(theLink)
{
	var toolbar_ob = get_toolbar_ob_(theLink);
	var title_ob = toolbar_ob.find('.e_title');
	
	//if( !confirm('Bereich "'+title_ob.html()+'" löschen?') )	// no real need to ask - for erroneous clicks, one can still use the cancel button
	//	return;
	
	var object_ob = toolbar_ob.next()
	
	toolbar_ob.slideUp(ANIM_DURATION, function(){$(this).remove();})
	object_ob.slideUp(ANIM_DURATION, function(){$(this).remove();})
}

function create_toolbar_html(title_descr)
{
	var html 
	='<div class="e_toolbar">'
	+	'<a href="#" onclick="secondary_show_hide_click(this); return false;" ondblclick="secondary_show_hide_dblclick(this); return false" title="Diese Durchführung ein-/ausblenden; Doppelklick=Alle ein-/ausblenden">'
	+		'<span class="e_icon">' + ARROW_DOWN + '</span> <i><span class="e_title">' + title_descr + '</span>&nbsp;</i> '
	+	'</a>'
	+	'<div style="float: right;">'
	+		'<a href="#" onclick="secondary_duplicate(this); return false;" title="Diese Durchführung duplizieren">&nbsp;&nbsp;+Dupl.&nbsp;&nbsp;</a>'
	+		'<a href="#" onclick="secondary_insert_from_1(this); return false;" title="Durchführung aus anderem Kurs einfügen">&nbsp;+Einfg.&nbsp;&nbsp;</a>'
	+		'<a href="#" onclick="secondary_up_down(this, true ); return false;" title="Durchführung nach oben verschieben">&nbsp;&nbsp;' + AREA_UP   + '&nbsp;</a>'
	+		'<a href="#" onclick="secondary_up_down(this, false); return false;" title="Durchführung nach unten verschieben">&nbsp;' + AREA_DOWN + '&nbsp;&nbsp;</a>'
	+		'<a href="#" onclick="secondary_delete(this); return false" title="Diese Durchführung löschen">&nbsp;&nbsp;&times;&nbsp;&nbsp;</a>'
	+	'</div>'
	+'</div>';
	return html;
}

function secondary_init()
{
	var add_menu_html = '';
	var template_id = 100;
	$('div.e_secondary').each(function()
	{
		var secondary_ob = $(this);
		var template_ob = secondary_ob.find('div.e_template');

		// prepare template
		template_ob.attr('data-template-id', template_id);
		var title = secondary_ob.attr('data-descr');
		add_menu_html += '<a href="#" onclick="secondary_add(\''+template_id+'\'); return false;"> &nbsp;+' + title + '&nbsp; </a>';
		template_id++;
		
		// create the toolbar above each secondary object
		count = 0;
		secondary_ob.find('div.e_object').each(function() 
		{
			var object_ob = $(this);

			object_ob.before(create_toolbar_html(object_ob.attr('data-descr')));
			count++;
			
			if( count > 1 ) {
				secondary_show_hide_do_(object_ob.prev(), false /*hide*/, false /*no animation*/);
			}
		});
	});
	
	// add "insert-field-menu-items" to the main menu bar 
	$('#fheader2 .sml :last-child').before(add_menu_html);
}



/******************************************************************************
Bitfield handling
******************************************************************************/

function bitfield_init(start_ob)
{
	start_ob.find('.e_bitfield .e_bitfield_item').click(function() {
		var clicked_ob = $(this);
		var input_ob = clicked_ob.parent().find('input[type=hidden]');
		var val = input_ob.val();
		var bit = clicked_ob.attr('data-bit');
		if( clicked_ob.is('input') ) {
			if( !clicked_ob.is(':checked') ) {
				val &= ~bit;
			}
			else {
				val |= bit;
			}
		}
		else {
			if( clicked_ob.hasClass('sel') ) {
				clicked_ob.removeClass('sel');
				val &= ~bit;
			}
			else {
				clicked_ob.addClass('sel');
				val |= bit;
			}
		}
		input_ob.val(val);
	});
}



/******************************************************************************
"defhide" handling
******************************************************************************/

function defhide_init(start_ob)
{
	start_ob.find('.e_defhide_more').click(function() {
		var clicked_ob = $(this);
		var parent_ob = clicked_ob.parent();
		var toshow_ob = parent_ob.find('.e_defhide_' + clicked_ob.attr('data-defhide-id'));
		if( toshow_ob.is(':visible') ) {
			clicked_ob.html(ARROW_RIGHT+' &nbsp;')
			toshow_ob.hide();
		}
		else {
			clicked_ob.html(ARROW_LEFT+' &nbsp;')
			toshow_ob.show();
		}
		
		
		//clicked_ob.hide();
		return false;
	});
}



/******************************************************************************
Attribute Handling
******************************************************************************/

var attr_tooltip		= 'Doppelklick=Attribut in neuem Fenster öffnen, Drag\'n\'Drop=Reihenfolge ändern';
var attr_tooltip_input	= 'Eingabe=Attribut hinzufügen, Doppelklick=Auswahl in neuem Fenster';
var attr_tooltip_del	= 'Dieses Attribut löschen';

function attr_recreate_hidden(attr_ob)
{
	var hidden_ob = attr_ob.find('input[type=hidden]'); if( !attr_ob.hasClass('e_attr') ) { alert('bad attr obj in recreate!'); return; }
	var id_list = '';
	attr_ob.find('.e_attritem').each(function() {
		var ob = $(this);
		id_list += id_list==''? '' : ',';
		id_list += ob.attr('data-attrid');
	});
	hidden_ob.val(id_list);
}

function attr_del(attr_ob, id)
{
	// remove the attribute from the DOM
	var item_ob = attr_ob.find('.e_attritem[data-attrid='+id+']');
	item_ob.remove();
	attr_recreate_hidden(attr_ob);
}

function attr_del_click()
{
	var clicked_ob = $(this); 
	var item_ob = clicked_ob.parent().parent(); if( !item_ob.hasClass('e_attritem') ) { alert('bad attritem obj!'); return; }
	var attr_ob = item_ob.parent(); if( !attr_ob.hasClass('e_attr') ) { alert('bad attr obj!'); return; }
	attr_del(attr_ob, item_ob.attr('data-attrid'));
	
	var input_ob = attr_ob.find('input[type=text]');
	input_ob.focus();
	return false;
}

function attr_item_dblclick()
{
	var item_ob = $(this); 
	var attr_ob = item_ob.parent(); if( !attr_ob.hasClass('e_attr') ) { alert('bad attr obj!'); return; }
	var href = "edit.php?table=" + attr_ob.attr('data-table')  + "&id=" + item_ob.attr('data-attrid'); 
	window.open(href, "_blank");
}



function attr_input_dblclick_1()
{
	var input_ob = $(this); 
	var attr_ob = input_ob.parent();

	// open selection window
	var url = 'index.php?table=' + attr_ob.attr('data-table') + '&selectobject';
	popup_width = parseInt($('body').width() * 0.9); if( popup_width < 640 ) popup_width = 640; // use a simelar width so that all columns will fit
	rcv_id_param = attr_ob;
	popup(0, popup_width, 500, url, 'selectobjecttarget');	
	
	// clear input field, ready for the next input
	input_ob.autocomplete('close');
	input_ob.val("");	
}
function attr_input_dblclick_2(attr_ob, id)
{
	var input_ob = attr_ob.find('input[type=text]');

	// get the description from the ID and add the attribute; this also checks if the ID is referencable
	var url = "autocomplete.php?acdata=" + input_ob.attr("data-acdata") + "&term=" + id;
	$.getJSON(url, function(json_data) { 
		if( json_data.length == 0 ) {
			alert('Das ausgewählte Attribut ist nicht referenzierbar.');
		}
		else {
			attr_add(input_ob, json_data[0].value, id, json_data[0].actype);
		}
	});
	
	// add the attribute
	input_ob.focus();
}



function attr_add(input_ob, descr, id, actype)
{
	var attr_ob = input_ob.parent(); if( !attr_ob.hasClass('e_attr') ) { alert('bad attr obj!'); return; }
	var hidden_ob = attr_ob.find('input[type=hidden]');

	// for single attribute selections, remove existing attributes first
	if( attr_ob.attr('data-mattr') == 0 ) {
		attr_ob.find('.e_attritem').remove();
		hidden_ob.val('');
	}
	
	// do we have an actype?
	var actype_class = '';
	if( actype ) {
		actype_class = ' e_attractype' + actype;
	}
	
	// add attribute to DOM
	var html =	'<span class="e_attritem" data-attrid="' + id + '" title="' + attr_tooltip + '"><span class="e_attrinner' + actype_class + '">'
			+		 descr +  '<span class="e_attrdel" title="' + attr_tooltip_del + '">&nbsp;&times;&nbsp;</span>'
			+	'</span></span>'; // should be same as [*] in attr.inc.php
	input_ob.before(html);
	attr_ob.find(".e_attritem[data-attrid="+id+"]").dblclick(attr_item_dblclick);
	attr_ob.find(".e_attritem[data-attrid="+id+"] .e_attrdel").click(attr_del_click);
	
	// add attribute to the hidden formular field
	var id_list = hidden_ob.val();
	if( id_list != '' ) { id_list += ','; }
	id_list += id;
	hidden_ob.val(id_list);
	
	// clear input field, ready for the next input
	input_ob.val("");
}

function attr_ac_selectcallback(event, ui)
{
	var input_ob = $(this);
	attr_add(input_ob, ui.item.value, ui.item.id, ui.item.actype);
	return false; // do not set the value, we've cleared the field as we've added the attribute as a new object
}

function attr_keypress(event)
{
	if( event.keyCode == 8 ) // 8 == backspace
	{
		var input_ob = $(this);
		if( input_ob.val() == '' ) {
			var attr_ob = input_ob.parent(); if( !attr_ob.hasClass('e_attr') ) { alert('bad attr obj!'); return; }
			var last_item_ob = attr_ob.find('.e_attritem').last();
			if( last_item_ob.length ) {
				attr_del(attr_ob, last_item_ob.attr('data-attrid'));
			}
		}
	}
}

function attr_focus()
{
	var attr_ob = $(this);
	if( !attr_ob.hasClass('e_attr') ) {
		attr_ob = attr_ob.parent();
		if( !attr_ob.hasClass('e_attr') ) return;
	}
	
	var input_ob = attr_ob.find('input[type=text]');
	input_ob.focus();
}

function attr_sort_start()
{
	var input_ob = $(this).find('input[type=text]').blur();
	input_ob;
}
function attr_sort_done()
{
	var attr_ob = $(this); if( !attr_ob.hasClass('e_attr') ) { alert('bad attr obj!'); return; }
	attr_recreate_hidden(attr_ob);
	attr_focus();
}

function attr_init(start_ob)
{
	// make all inputfields with the class "acclass" an autocomplete widget; forward "data-acdata" to "accallback.php"
	start_ob.find(".e_attr input[type=text]").each(function() {
			var input_ob = $(this);
			input_ob.autocomplete({
					source:		ac_sourcecallback
				,	theinput:	input_ob
				,	autoFocus:	true
				,	html:		true
				,	select:		attr_ac_selectcallback
			});
			input_ob.keydown(attr_keypress);  // keyup() does not work as val() is already modified and we won't catch the correct backspace key ...
			input_ob.attr('title', attr_tooltip_input);
			input_ob.dblclick(attr_input_dblclick_1);
		}
	);
	
	
	start_ob.find(".e_attritem").dblclick(attr_item_dblclick).attr('title', attr_tooltip);
	start_ob.find(".e_attrdel").click(attr_del_click).attr('title', attr_tooltip_del);
	start_ob.find(".e_attr").click(attr_focus);
	start_ob.find(".e_attr[data-mattr=1]").sortable({
		items: "> .e_attritem", // only sort direct decendents of class e_attritem
		start: attr_sort_start, // just set the focus aways
		update: attr_sort_done, // update the hidden formular field
		opacity:0.8, 
		cursor: 'default', 
		revert: 100, // the number of ms for the animation to put the object to the new postion
		placeholder: 'e_attrdndplaceholder',
	});
	

}



/******************************************************************************
rights/references
******************************************************************************/

function e_rightstoggle()
{
	var ob = $('#e_rights'); 
	if( ob.is(':visible') ) {
		ob.slideUp(ANIM_DURATION);
		$('#e_rightsicon').html(ARROW_RIGHT);
	}
	else {
		e_showref(); // we recalculate the references on each popup opening; this is beacause they may change while a record being edited is open
		ob.slideDown(ANIM_DURATION);
		$('#e_rightsicon').html(ARROW_UP);
	}
	return false;
}

function e_showref()
{
	var ob = $("#e_refcontainer");
	var url = 'edit.php?getreferencesstring&table='+ob.attr('data-table')+'&id='+ob.attr('data-id')+'&rnd='+new Date().getTime();
	ob.load(url);
	return false;
}



/******************************************************************************
main
******************************************************************************/

function controls_init(start_ob)
{
	bitfield_init(start_ob);
	defhide_init(start_ob);
	attr_init(start_ob);
}

$().ready(function()
{
	secondary_init();
	controls_init($('form[action*=edit]'));
	
	// enter bei texteingabefelder nicht zulassen! das ist sehr verwirrend, da die Vorschlaglisten auch Enter zum auswählen verwenden.
	// schneller als man denkt, ist das Formular dann abgeschickt und die Verwirrung komplett, da man nur ein Attribut auswaehlen wollte ...
	$('input[type=text]').keypress(function(e) {	
		if(e.which == 13){
			e.preventDefault();
		}
	});
	
});

