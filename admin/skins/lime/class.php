<?php

require_once('skins/default/class.php');

class SKIN_LIME_CLASS extends SKIN_DEFAULT_CLASS
{
	function __construct($folder)
	{
		parent::__construct('skins/default');
	}
	
	function getCssTags($param)
	{
		$param['colors'] = 'lime';
		return parent::getCssTags($param);
	}
};

