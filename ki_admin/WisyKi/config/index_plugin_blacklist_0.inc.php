<?php

require_once('index_plugin_multiedit.inc.php');
$multiedit = new MULTIEDIT_PLUGIN_CLASS;
if (isset($_REQUEST['table']))
    $_SESSION['g_session_index_sql'][$_REQUEST['table']] = $_REQUEST['id'];
// if (isset($_REQUEST['ok']) && $_REQUEST['ok'] == 'OK')
//     {
//         require_once($_SERVER['DOCUMENT_ROOT'] . '/ki_admin/WisyKi/functions.js');
        
//         echo "<script> javascript:escopopupclose(" . $_SESSION['g_session_index_sql']['blacklist'] . ", 'kurse');return false;</script>";
//         // header('Location: \ki_admin\edit.php?table=kurse&id=' . $_SESSION['g_session_index_sql']['blacklist']);
//         exit();
//     }
$multiedit->main('kompetenz_blacklist');
