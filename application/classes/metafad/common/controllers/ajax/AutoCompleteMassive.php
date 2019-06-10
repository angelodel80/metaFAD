<?php
class metafad_common_controllers_ajax_AutoCompleteMassive extends metafad_common_controllers_ajax_AutoComplete
{
    function execute($instituteKey, $model, $filters, $fieldName, $term)
    {
        $idList = explode("-", __Session::get('idList'));
        $ids = array();
        foreach ($idList as $i) {
            $ids[] = '"' . $i . '"';
        }
        
        $filters[] = array('type' => 'multiple','name' => 'id','value' => $ids);

        return parent::execute($instituteKey, $model, $filters, $fieldName, $term);
    }
}
