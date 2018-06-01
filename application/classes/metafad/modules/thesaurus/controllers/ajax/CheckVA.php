<?php
class metafad_modules_thesaurus_controllers_ajax_CheckVA extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $value = __Request::get('value');
        $field = __Request::get('field');
        $proxyParams = __Request::get('proxyParams');
        $proxyParams = json_decode($proxyParams);

        $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusForms')
          ->load('findTerm', array('moduleId' => $proxyParams->module, 'fieldName' => $field))
          ->where('thesaurusdetails_key',$value)->first();

        if(!$it->thesaurusdetails_key) {
            $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusForms')
                ->load('findTerm', array('moduleId' => $proxyParams->module, 'fieldName' => $field))->first();
            $arDetails = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Details');
            $thesaurus_id = (int)$it->thesaurusdetails_FK_thesaurus_id;
            $arDetails->thesaurusdetails_FK_thesaurus_id = $thesaurus_id;
            $arDetails->thesaurusdetails_level = 1;
            $arDetails->thesaurusdetails_key = $value;
            $arDetails->thesaurusdetails_value = $value;
            $arDetails->thesaurusdetails_creationDate = new org_glizy_types_DateTime();
            $arDetails->thesaurusdetails_modificationDate = new org_glizy_types_DateTime();
            $arDetails->save();
        }
        return 'add';
    }
}
