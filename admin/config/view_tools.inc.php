<?php



require_once('functions.inc.php');



function add_sandbox_prefix($url)
{
    if( strpos($_SERVER['HTTP_HOST'], 'sandbox')!==false && strpos($url, 'sandbox')===false )
    {
        preg_match_all('/sandbox[1-99]{0,2}/m', $_SERVER['HTTP_HOST'], $matches, PREG_SET_ORDER, 0);
        // add sandbox[xx]- (if there is already a subdomain - "sub.domain.top")
        // or  sandbox[xx]. (if there is no subdomain - "domain.top")
        if( substr_count($url, '.') >= 2 )
            $url = str_replace('://', '://'.$matches[0][0].'-', $url);
            else
                $url = str_replace('://', '://'.$matches[0][0].'.', $url);
    }
    return $url;
}


$def_domain = defined(INSECURE_HOST)? INSECURE_HOST : $_SERVER['HTTP_HOST'];
$url = regGet('view.domain', $def_domain);

if( strpos($url, '://')===false ) $url = "http://$url";
if( substr($url, -1)!='/' ) $url .= '/';
$url = add_sandbox_prefix($url);

