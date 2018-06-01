<?php
class metafad_usersAndPermissions_roles_views_components_AddButton extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label',     true,   '',         COMPONENT_TYPE_STRING);
        $this->defineAttribute('routeUrl',    false,    '',     COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $routeUrl = $this->getAttribute('routeUrl');
        $output = '<div id="dataGridAddButton"><div class="btn-group btn-right-accessory">'.
            '</div>'.__Link::makeLink($routeUrl, array('label' => " Aggiungi ruolo", 'cssClass' => 'btn btn-info btn-flat btn-add', 'icon' => 'plusIcon fa fa-plus')).'</div>';
        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $output .= <<<EOD
<script type="text/javascript">
    jQuery(function(){
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