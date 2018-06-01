<?php
class metafad_modules_thesaurus_controllers_ajax_SaveInput extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {   
        $result = $this->checkPermissionForBackend('editDraft');
        if (is_array($result)) {
            return $result;
        }
        
      $id = __Request::get('id');
      $value = __Request::get('val');
      $type = __Request::get('type');

      $proxy = org_glizy_objectFactory::createObject('metafad_modules_thesaurus_models_proxy_ThesaurusDetailsProxy');
      $term = $proxy->findTermById($id);

      if($type == 'value')
      {
        $term->thesaurusdetails_value = $value;
      }
      else if($type == 'key')
      {
        $term->thesaurusdetails_key = $value;
      }
      else if($type == 'level')
      {
        $term->thesaurusdetails_level = $value;
      }
      else if($type == 'parent')
      {
        $term->thesaurusdetails_parent = $value;
      }
      
      $term->thesaurusdetails_modificationDate = new org_glizy_types_DateTime();

      $term->save();

      return $type;
    }
}
