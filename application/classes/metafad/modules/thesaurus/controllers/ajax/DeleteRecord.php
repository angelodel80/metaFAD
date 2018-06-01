<?php
class metafad_modules_thesaurus_controllers_ajax_DeleteRecord extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('delete');
        if (is_array($result)) {
            return $result;
        }

        $id = __Request::get('id');

        $term = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
              ->where('thesaurusdetails_id',$id)->first();

        metafad_Metafad::logAction('metafad_modules_thesaurus_controllers_ajax_DeleteRecord fk:'.$term->thesaurusdetails_FK_thesaurus_id.' term:'.$term->thesaurusdetails_key, 'thesaurus');

        $term->delete();

        return 'delete';
    }
}
