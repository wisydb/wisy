<?php



// deprecated
if( isset( $_COOKIE['oldeditor'] ) && $_COOKIE['oldeditor'] )
{
    require_once('deprecated_edit.php'); // we should keep the old editor for a longer time - as a fallback
}
else
// /deprecated
{
    require_once('functions.inc.php');
    $ob = new EDIT_RENDERER_CLASS;
    $ob->handle_request();
}


