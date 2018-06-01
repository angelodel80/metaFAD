<?php
class metafad_modules_iccd_controllers_ajax_AddTerm extends metafad_common_controllers_ajax_CommandAjax
{
    function execute($fieldName, $model, $query, $term, $proxy, $proxyParams, $getId)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
        
        $proxyParams = json_decode($proxyParams);

        $ar = org_glizy_objectFactory::createModel('metafad.modules.iccd.models.ICCDThesaurus');

        $result = $ar->find(array(
            'iccd_theasaurs_code' => $proxyParams->code,
            'iccd_theasaurs_level' => $proxyParams->level,
            'iccd_theasaurs_key' => $term,
            'iccd_theasaurs_value' => $term,
        ));

        if (!$result) {
            $ar->iccd_theasaurs_code = $proxyParams->code;
            $ar->iccd_theasaurs_level = $proxyParams->level;
            $ar->iccd_theasaurs_key = $term;
            $ar->iccd_theasaurs_value = $term;
            $ar->save();
        }
    }
}