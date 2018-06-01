<?php
class metafad_modules_thesaurus_controllers_ajax_GetModule extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
      $id = __Request::get('id');
      $ar = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusForms')
            ->where('thesaurusforms_id',(int)$id)->first();
      return $ar->thesaurusforms_moduleId;
    }
}
