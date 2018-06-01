<?php
/**
 * This file is part of the GLIZY framework.
 * Copyright (c) 2005-2012 Daniele Ugoletti <daniele.ugoletti@glizy.com>
 *
 * For the full copyright and license information, please view the COPYRIGHT.txt
 * file that was distributed with this source code.
 */


class archivi_views_components_DeleteButton extends org_glizy_components_HtmlButton
{
	function render()
	{
		$this->applyOutputFilters('pre', $this->_content);
		$output = '';

		$attributes 				= array();
		$attributes['id'] 			= $this->getId();
		// $attributes['name'] 		= $this->getAttribute('name') != '' ? $this->getAttribute('name') : $this->getOriginalId();
		$attributes['name'] 		= $this->getOriginalId();
		$attributes['disabled'] 	= $this->getAttribute('disabled') ? 'disabled' : '';
		$attributes['class'] 		= $this->getAttribute('cssClass');
		$attributes['type'] 		= $this->getAttribute('type');
		$attributes['onclick'] 		= $this->getAttribute('onclick');

        $id = intval(preg_replace('/[^0-9]+/', '', $_SERVER['REQUEST_URI']), 10);
        if(strpos($_SERVER['REQUEST_URI'], 'parentId') || strpos($_SERVER['REQUEST_URI'], '/edit/0/')){
            return;
        }

		$output = '';

            $output .= __Link::makeLinkWithIcon( 'archiviMVCDelete',
                                                            $attributes['class'],
                                                            array(
                                                                'title' => __T('GLZ_RECORD_DELETE'),
                                                                'id' => $id,
                                                                'model' => 'archivi.models.Model',
                                                                'action' => 'delete'  ) );


		$this->addOutputCode($output);
	}
}