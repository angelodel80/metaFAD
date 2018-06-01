<?php
class archivi_views_components_AddButton extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
		$this->defineAttribute('type', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('label', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('routeUrl', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('recordClassName', true, '', COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $types = explode(',', $this->getAttribute('type'));
        $labels = explode(',', $this->getAttribute('label'));
        $routeUrl = $this->getAttribute('routeUrl');
        $output = '<div id="dataGridAddButton"><div class="btn-group btn-right-accessory">'.
                  '<a class="btn btn-info btn-flat btn-add dropdown-toggle" data-toggle="dropdown" href="#">'.
                  '<i class="fa fa-caret-down"></i> '.
                  '</a>'.
                  '<ul class="dropdown-menu forced-left-position">';

        foreach ($types as $i => $type) {
            $output .= '<li>'.__Link::makeLink($routeUrl, array('sectionType' => $type, 'id' => 0, 'label' => 'Crea scheda: '.$labels[$i])).'</li>';
        }

        foreach ($types as $i => $types) {
            $output .= '<li>'.__Link::makeLink($routeUrl. 'Template', array('sectionType' => $type, 'templateID' => 0, 'id' => 0, 'label' => 'Crea template: '.$labels[$i])).'</li>';
        }

        $output .= '</ul>'.
                  '</div>'.
                  '<a id="primaryButton" class="btn btn-info btn-flat btn-add" href="#">Aggiungi scheda</a></div>';

        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $output .= <<<EOD
<script type="text/javascript">
    jQuery(function(){
        jQuery('#primaryButton').click( function () {
            jQuery('.btn-right-accessory .dropdown-menu').toggle();
        });
        jQuery('.btn.btn-info.btn-flat.btn-add.dropdown-toggle').click( function () {
            jQuery('.btn-right-accessory .dropdown-menu').toggle();
        });
        var table = jQuery('#$dataGridId').data('dataTable');
        setTimeout(function(){
            jQuery('#dataGridAddButton').prependTo("#{$dataGridId}_wrapper .filter-row");
            
        }, 100);
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}