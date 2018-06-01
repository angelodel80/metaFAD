<?php
class metafad_modules_thesaurus_controllers_ajax_CheckVC extends metafad_common_controllers_ajax_CommandAjax
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
      if(!$it->thesaurusdetails_key)
      {
        return 'noadd';
      }
      else
      {
        return 'add';
      }
    }
}
