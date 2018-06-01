<?php
class archivi_views_components_Filter extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label', false, '{i18n:Filter by type}', COMPONENT_TYPE_STRING);
        $this->defineAttribute('dataGridAjaxId', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('fieldNumber', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('recordClassName', true, '', COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $id = $this->getAttribute('id');
        $output  = '<div id="'.$id.'_cont" style="display: inline">';
        $output .= '<label for="'.$id.'">'.$this->getAttribute('label').' ';
        $output .= '<select id="'.$id.'" style="height:34px;">';
        $output .= '<option value="">Mostra Tutto</option>';
        $output .= '<option value="serie">Serie</option>';
        $output .= '<option value="sotto-serie">Sotto-serie</option>';
        $output .= '<option value="fascicolo">Fascicolo</option>';
        $output .= '</select></label>';
        $output .= '</div>';

        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $fieldNumber = $this->getAttribute('fieldNumber');

        $output .= <<<EOD
<script type="text/javascript">
    jQuery(function(){
        var table = jQuery('#$dataGridId').data('dataTable');
        setTimeout(function(){
            jQuery("#{$id}_cont").children().appendTo("#{$dataGridId}_filter");
            var ooSettings = table.fnSettings();
            $("#$id").val(ooSettings.aoPreSearchCols[$fieldNumber].sSearch);
        }, 100);

        jQuery('#$id').change( function () {
            table.fnFilter( $(this).val(), $fieldNumber );
        });
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}