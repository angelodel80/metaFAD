<?php
/**
 * This file is part of the GLIZY framework.
 * Copyright (c) 2005-2012 Daniele Ugoletti <daniele.ugoletti@glizy.com>
 *
 * For the full copyright and license information, please view the COPYRIGHT.txt
 * file that was distributed with this source code.
 */


class metafad_teca_MAG_views_components_Input extends org_glizy_components_Input
{
	function render_html()
	{
		$attributes 				= array();
		$attributes['id'] 			= $this->getId();
		$attributes['name'] 		= $this->getOriginalId();
		$attributes['disabled'] 	= $this->getAttribute('disabled') ? 'disabled' : '';
		$attributes['readonly'] 	= $this->getAttribute('readOnly') ? 'readonly' : '';
		$attributes['title'] 		= $this->getAttributeString('title');
		$attributes['placeholder'] 		= $this->getAttributeString('placeholder');
		if ( empty( $attributes['title'] ) )
		{
			$attributes['title'] 		= $this->getAttributeString('label');
		}
		$attributes['class'] 		= $this->getAttribute('cssClass');
		$attributes['class'] 		.= (!empty($attributes['class']) ? ' ' : '').($this->getAttribute('required') ? 'required' : '');

		if ($this->getAttribute('type')=='multiline')
		{
			$attributes['cols'] 		= $this->getAttribute('cols');
			$attributes['rows'] 		= $this->getAttribute('rows');
			$attributes['wrap'] 		= $this->getAttribute('wrap');

			$output  = '<textarea '.$this->_renderAttributes($attributes).'>';
			$output .= $this->encodeOuput($this->_content);
			$output .= '</textarea>';

			$this->addTinyMCE( true );
		}
		else
		{
			$attributes['type'] 		= $this->getAttribute('type');
			$attributes['maxLength'] 	= $this->getAttribute('maxLength');
			$attributes['size'] 		= $this->getAttribute('size');
			$attributes['value'] 		= $this->encodeOuput(is_string($this->_content) ? $this->_content : json_encode($this->_content));

			$output  = '<input style="display:none"'.$this->_renderAttributes($attributes).'/>';
		}

		$label = $this->getAttributeString('label') ? : '';
		if ($label) {
			$cssClassLabel = $this->getAttribute( 'cssClassLabel' );
			$cssClassLabel .= ( $cssClassLabel ? ' ' : '' ).($this->getAttribute('required') ? 'required' : '');
			if ($this->getAttribute('wrapLabel')) {
				$label = org_glizy_helpers_Html::label($this->getAttributeString('label'), $this->getId(), true, $output, array('class' => $cssClassLabel ), false);
				$output = '';
			} else {
				$label = org_glizy_helpers_Html::label($this->getAttributeString('label'), $this->getId(), false, '', array('class' => $cssClassLabel ), false);
			}
		}
		$this->addOutputCode($this->applyItemTemplate($label, $output));
	}

}