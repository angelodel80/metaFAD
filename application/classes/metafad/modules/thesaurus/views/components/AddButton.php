<?php
class metafad_modules_thesaurus_views_components_AddButton extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label',     true,   '',         COMPONENT_TYPE_STRING);
        $this->defineAttribute('routeUrl',    false,    '',     COMPONENT_TYPE_STRING);
        $this->defineAttribute('add', false, 'Nuova scheda', COMPONENT_TYPE_STRING);
        $this->defineAttribute('cssClass', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('buttonId', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('noLink', false, false, COMPONENT_TYPE_STRING);
        $this->defineAttribute('iconClass', false, 'fa-plus', COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $routeUrl = $this->getAttribute('routeUrl');
        $iconClass = $this->getAttribute('iconClass');
        $buttonId = $this->getAttribute('buttonId');

        $output = '<div id="' . $buttonId . '" class="'.$this->getAttribute('cssClass').'">';

        $it = org_glizy_ObjectFactory::createModelIterator($this->getAttribute('recordClassName'));
        foreach ($it as $ar) {
            if(json_decode($ar->getRawData()->document_detail_object)->isTemplate){
                $output .= '<li>'.__Html::renderTag(	'a',
													array(	'href' => __Link::makeURL($routeUrl) . $ar->getId(),
															'title' => glz_encodeOutput( $queryVars['title'] ) ),
													true,
													'Crea scheda da: ' .json_decode($ar->getRawData()->document_detail_object)->templateTitle
													).

                '</li>';

            }
        }

        if($this->getAttribute('noLink'))
        {
          $output .= __Link::makeLink('', array('label' => '<span>'.$this->getAttribute('add').'</span>', 'title' => $this->getAttribute('add'), 'cssClass' => 'btn btn-info btn-flat btn-add', 'icon' => 'plusIcon fa '.$iconClass),array(),'',false).'</div>';
        }
        else
        {
          $output .= __Link::makeLink($routeUrl, array('label' => '<span>'.$this->getAttribute('add').'</span>', 'title' => $this->getAttribute('add'), 'cssClass' => 'btn btn-info btn-flat btn-add', 'icon' => 'plusIcon fa '.$iconClass),array(),'',false).'</div>';
        }
        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $output .= <<<EOD
<script type="text/javascript">
    jQuery(function(){
        var table = jQuery('#$dataGridId').data('dataTable');
        setTimeout(function(){
            jQuery('#{$buttonId}').prependTo("#{$dataGridId}_wrapper .filter-row");
            
        }, 100);
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}
