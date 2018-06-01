<?php
/**
 * This file is part of the GLIZY framework.
 * Copyright (c) 2005-2012 Daniele Ugoletti <daniele.ugoletti@glizy.com>
 *
 * For the full copyright and license information, please view the COPYRIGHT.txt
 * file that was distributed with this source code.
 */


class metafad_common_views_components_Container extends org_glizy_components_ComponentContainer
{

	function init()
	{
		$this->defineAttribute('cssClass', 	false, null, 	COMPONENT_TYPE_STRING);
		$this->defineAttribute('cssId', 	false, null, 	COMPONENT_TYPE_STRING);

		parent::init();
	}


	function render_html_onStart()
	{
	    if($this->getAttribute('cssClass') && $this->getAttribute('cssId')){
		    $output = '<div class="'. $this->getAttribute('cssClass') . '" id="'. $this->getAttribute('cssId') . '">';
	    } else if($this->getAttribute('cssClass')){
	        $output = '<div class="'. $this->getAttribute('cssClass') . '">';
	    } else if($this->getAttribute('cssId')){
	        $output = '<div id="'. $this->getAttribute('cssId') . '">';
	    } else{
	        $output = '<div>';
	    }
		$this->addOutputCode($output);
	}

	function render_html_onEnd()
	{
		$output  = '</div>';
		$this->addOutputCode($output);
	}
}