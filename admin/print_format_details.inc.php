<?php


class print_details_class extends print_plugin_class
{
	var $options;			// the options to use
	var $param;				// parameters from the options
	
	function __construct()
	{
		$this->options['fontsize'] = array();
		$this->options['pagebreak'] = array();
	}
	
	function printdo()
	{
		global $site;
	
		require('print_tools.inc.php');
		require_lang('lang/overview');
		$pagebreak = $this->param['pagebreak'];
		
		$site->addScript('print.js');
		$site->pageStart(array('css'=>'print', 'pt'=>$this->param['fontsize']));
			$records = 0;
			$db = new DB_Admin();
			$db->query($this->param['sql']);
			while( $db->next_record() ) {
				if( $records ) {
					if( $pagebreak && $records%$pagebreak==0 ) {
						echo '<br style="page-break-after:always;" />';
					}
					else {
						echo '<br />';
					}
				}
			
				echo preview_do($this->param['table'], $db->f('id'), 0 /*renderLinks*/);
				$records++;
			}
		$site->pageEnd();
	}
}

