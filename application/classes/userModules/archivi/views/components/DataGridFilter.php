<?php
class archivi_views_components_DataGridFilter extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('dataGridAjaxId', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('fieldNumber', true, '1', COMPONENT_TYPE_STRING);
        $this->defineAttribute('parent', false, null, COMPONENT_TYPE_INTEGER);

        parent::init();
    }

    function render()
    {
        $id = $this->getAttribute('id');
        $output  = '<div id="'.$id.'_cont" style="display: inline">';
        $output .= '<label for="'.$id.'">'.$this->getAttribute('label').' ';
        $output .= '<div class="select">';
        $output .= '<select id="'.$id.'">';
        if (!$this->getAttribute('parent')) {
            $output .= '<option value="" selected>Record radice</option>';
        }
        $output .= '<option value="*" selected>Tutti</option>';

        $archiveTypeOrder = 0;
            
        if ($this->getAttribute('parent')) {
            $ar = __ObjectFactory::createModel('archivi.models.Model');
            $ar->load($this->getAttribute('parent'));
            
            $arArchiveType = __ObjectFactory::createModel('archivi.models.ArchiveType');
            $arArchiveType->find(array('archive_type_key' => $ar->livelloDiDescrizione));
            $archiveTypeOrder = $arArchiveType->archive_type_order;
        }

        $it = __ObjectFactory::createModelIterator('archivi.models.ArchiveType')
            ->where('archive_type_order >= '.$archiveTypeOrder)
            ->orderBy('archive_type_order');
        
        foreach ($it as $ar) {
            $output .= '<option value="'.$ar->archive_type_key.'">'.$ar->archive_type_name.'</option>';
        }

        $output .= '</select></div></label>';
        $output .= '</div>';

        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $fieldNumber = $this->getAttribute('fieldNumber');

        $output .= <<<EOD
<script type="text/javascript">
    $(function(){
        setTimeout(function(){
            var table = $('#$dataGridId').data('dataTable');
            $("#{$id}_cont").children().appendTo("#{$dataGridId}_filter");
            var ooSettings = table.fnSettings();
            $("#$id").val(ooSettings.aoPreSearchCols[$fieldNumber].sSearch);
        }, 100);

        $('#$id').change( function () {
            var table = $('#$dataGridId').data('dataTable');
            table.fnFilter( $(this).val(), $fieldNumber );
        });
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}