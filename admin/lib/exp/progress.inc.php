<?php

class EXP_PROGRESS_CLASS
{
	function render_placeholder($title)
	{
		$this->exportStartTime = time();
		echo 	'<p style="padding: 1em 2em;">'
			. 		$title
			.		'<br /><br />'
			.		'&nbsp;<img src="skins/default/img/ajaxload-16x11.gif " width="16" height="11" alt="" />&nbsp; &nbsp;'
			.		'<span id="progress_info"></span>'
			.		'<span id="evenmore_info"></span>'
			.	'</p>';
		flush();
	}
	
	function progress_time()
	{
		$secondsSinceStart = time() - $this->exportStartTime;
		$minutesSinceStart = intval($secondsSinceStart / 60);
		$secondsSinceStart -= $minutesSinceStart*60;
		return sprintf("%d:%02d", $minutesSinceStart, $secondsSinceStart);
	}
	
	function progress_info($info, $force_update = false)
	{
		// preprend time to $info
		$info = $this->progress_time() . ' - ' . $info;
	
		// write progress info
		$info = strtr($info, array("'"=>''));
		$info = isohtmlspecialchars($info);
		$htmlcode = "<script>\$('#progress_info').text('$info');</script>\n";
		echo $htmlcode;
		
		// make sure, we write at least some KB per second
		$bytesPerSecond = 4*1024;
		$this->bytesWritten = isset($this->bytesWritten) ? $this->bytesWritten : 0;
		$this->bytesWritten += strlen($htmlcode);
		$lastWritten = isset( $this->lastWritten ) ? $this->lastWritten : null;
		if( $lastWritten != time() && $this->bytesWritten < $bytesPerSecond )
		{
			$dummyText = "<!-- just some text to keep the browser awake -->\n";
			echo str_repeat($dummyText, intval( ((int) $bytesPerSecond - (int) $this->bytesWritten) / (int) strlen($dummyText) ));
			$this->bytesWritten = 0;
		}
		$this->lastWritten = time();
		
		flush();
	}
	
	function evenmore_info($info, $force_update = false)
	{
	    $htmlcode = "<script>\$('#progress_info').text( $('#progress_info').text()+'$info' );</script>\n";
	    echo $htmlcode;
	    
	    flush();
	}

	function js_redirect($url, $msg)
	{
	    $url .= isset($_REQUEST['debug']) && $_REQUEST['debug'] ? "&debug=".$_REQUEST['debug'] : '';
		
		$redirect = "<script type=\"text/javascript\"><!--\nwindow.location='".$url."';\n/"."/--></script>\n";
		if( isset($_REQUEST['debug']) && $_REQUEST['debug'] )
			echo isohtmlspecialchars($redirect);
		else
			echo $redirect;
		
		echo '<p style="padding: 0em 2em;">' . isohtmlspecialchars($msg) . '</p>'; // normally, this should not appear, however, it is a good hint if scripting fails for some reasons.
		exit();
	}
};