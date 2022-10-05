<?php

/*=============================================================================
a simple table wrapping given cells to a number of columns
===============================================================================

file:	
	coltable.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

usage:
	$ob = new COLTABLE_CLASS;
	echo $ob->tableStart()
		echo $ob->cellStart();
			echo 'first cell content';
		echo $ob->cellStart();
			echo 'second cell content';
		...
	echo $ob->tableEnd();

=============================================================================*/



class COLTABLE_CLASS
{
	var $cols;
	var $addSpacingCell;
	var $cells;
	var $cellEnded;
	
	function tableStart($cols = 3,
						$addSpacingCell = 1, 
						$param = 'cellpadding="0" cellspacing="0" border="0"')
	{
		$this->cols				= $cols;
		$this->addSpacingCell	= $addSpacingCell;
		$this->cells			= 0;
		$this->cellEnded		= 1;
		
		return $param? "<table $param><tr>" : "<table><tr>";
	}
	
	function cellStart($param = '')
	{
		$ret = '';
		
		if( !isset( $this->cellEnded ) || !$this->cellEnded ) {
			$ret .= $this->cellEnd();
		}
	
		if( isset( $this->cells ) && isset( $this->cols ) 
		    && $this->cells >= $this->cols ) {
			$ret .= '</tr><tr>';
			$this->cells = 0;
		}
	
		$ret .= '<td';
		
		if( $param ) {
			$ret .= " $param";
		}
	
		$ret .= '>';
	
		$this->cellEnded = 0;
	
		return $ret;
	}
	
	function cellEnd()
	{
		$ret = '</td>';
		if( isset( $this->addSpacingCell ) && $this->addSpacingCell ) {
			$ret .= '<td>&nbsp;</td>';
		}
		
		$this->cells++;
		$this->cellEnded = 1;		
		
		return $ret;
	}

	function tableEnd()
	{
		$ret = '';

		if( !isset( $this->cellEnded ) || !$this->cellEnded ) {
			$ret .= $this->cellEnd();
		}
		
		while( $this->cells < $this->cols ) {
			$ret .= $this->cellStart();
				$ret .= '&nbsp;';
			$ret .= $this->cellEnd();
		}
		
		$ret .= "</tr></table>";
		
		return $ret;
	}
}