<?php
class metafad_modules_thesaurus_controllers_ajax_AddTerm extends metafad_common_controllers_ajax_CommandAjax
{
    function execute($fieldName, $model, $query, $term, $proxy, $proxyParams, $getId)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        $proxyParams = json_decode($proxyParams);

        $thesaurus = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');
        $thesaurusDetail = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.ThesaurusDetails');

        $existTerm = $thesaurusDetail->find(array('thesaurusdetails_key' => $term,'thesaurus_code' => $proxyParams->code));
        $result = $thesaurus->find(array('thesaurus_code' => $proxyParams->code));

        if ($result && !$existTerm) {
            $arDetails = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Details');
            $arDetails->thesaurusdetails_FK_thesaurus_id = $thesaurus->getId();
            $arDetails->thesaurusdetails_level = $proxyParams->level;
            $arDetails->thesaurusdetails_key = $term;
            $arDetails->thesaurusdetails_value = $term;
            $arDetails->thesaurusdetails_creationDate = new org_glizy_types_DateTime();
            $arDetails->thesaurusdetails_modificationDate = new org_glizy_types_DateTime();
            $arDetails->save();
        }
    }
}
