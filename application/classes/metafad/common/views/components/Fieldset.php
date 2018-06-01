<?php
class metafad_common_views_components_Fieldset extends org_glizy_components_Fieldset
{
	function render_html_onStart()
	{
		$attributes 		 	= array();
		$attributes['id']		= $this->getOriginalId();
		$attributes['class'] 	= $this->getAttribute('cssClass');
		$output = '<fieldset '.$this->_renderAttributes($attributes).'>';
		if ( !is_null( $this->getAttribute( 'label' ) ) )
		{
			$required = false;
            $data = $this->getAttribute('data');
            if ($data) {
                if (preg_match('/minRec=(\d+)|repeatMin=(\d+)/', $data, $m)) {
                    $required = $m[1] > 0 || $m[2] > 0;
                }
            }
            // TODO usare classe per css
			if (!$data) {
				$output .= '<div class="border-legend"></div>';
			}			
			$output .= '<'.$this->getAttribute( 'legendTag' ).' '.($required ? '' : 'style="font-weight:normal"').'>'.( $this->getAttribute( 'addExtraSpan' ) ? '<span>' : '' ).$this->getAttribute( 'label' ).( $this->getAttribute( 'addExtraSpan' ) ? '</span>' : '' ).'</'.$this->getAttribute( 'legendTag' ).'>';
        }
		$this->addOutputCode($output);
	}
}