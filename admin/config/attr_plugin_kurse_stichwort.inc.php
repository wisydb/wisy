<?php


/*****************************************************************************
 * Create the popup window
 *****************************************************************************/



require_once('functions.inc.php');



function attr_plugin_render_popup($id)
{
	global $site;
	global $db;
	
	$site->title = "Verwandte Stichwörter und Themen";
	$site->pageStart(array('popfit'=>1));
	form_tag('attr_form', 'print.php', '', '', 'get');
	
		$site->skin->submenuStart();
			echo $site->title;
		$site->skin->submenuBreak();
			echo '&nbsp;';
		$site->skin->submenuEnd();
		
		$site->skin->workspaceStart();
			
			
			$db->query("SELECT stichwort, algorithmus, scope_note FROM stichwoerter WHERE id=".intval($id));
			if( !$db->next_record() )
			{
				echo "Stichwort <b>#$id</b> nicht gefunden.";
			}
			else
			{
				$stichwort = $db->fs('stichwort');
				$algorithmus = $db->fs('algorithmus');
				$scope_note = $db->fs('scope_note');
				if( $algorithmus == '' )
				{
					echo "Keine mit <b>".isohtmlspecialchars($stichwort)."</b> verwandten Stichwörter oder Themen gefunden.";
				}
				else
				{
					echo "Mit <b>".isohtmlspecialchars($stichwort)."</b> verwandte Stichwörter oder Themen:<br /><br />";
					
					echo $algorithmus; // is already HTML!
				}
				
				if( $scope_note != '' )
				{
					echo "<hr /><b>Scope Note</b>:<br /><br />";
					echo isohtmlspecialchars($scope_note);
				}
			}
			
			
			
		$site->skin->workspaceEnd();
		
		$site->skin->buttonsStart();
			form_button('cancel', htmlconstant('_OK'), 'window.close();return false;');
		$site->skin->buttonsEnd();
	
	echo '</form>';
	$site->pageEnd();
}



if( $_REQUEST['module'] == 'attr_plugin_kurse_stichwort' )
	attr_plugin_render_popup(intval($_REQUEST['id']));




/*****************************************************************************
 * The pluginfunction, add little icons to the attributes
 *****************************************************************************/




$pluginfunc = 'attr_plugin_kurse_stichwort';
function attr_plugin_kurse_stichwort(&$in)
{
	switch( $in['cmd'] )
	{
		case 'renderAfterName':
			$id = intval($in['id']);
			
			global $db;
			$db->query("SELECT algorithmus, scope_note FROM stichwoerter WHERE id=".intval($id));
			if( $db->next_record() )
			{
				$algorithmus = $db->fs('algorithmus');
				$scope_note = $db->fs('scope_note');
				if( $algorithmus != '' || $scope_note != '' )
				{
					$url = 'module.php?module=attr_plugin_kurse_stichwort&id='.$id;
					echo '<a href="'.isohtmlspecialchars($url).'" title="Verwandte Stichwörter und Themen anzeigen" target="kurst_stichw" onclick="return popup(this,500,420);">&nbsp;?&nbsp;</a>';
				}
			}
			break;
	}
}

