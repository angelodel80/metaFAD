<?php
class metafad_modules_thesaurus_controllers_ajax_AddRecord extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }

        $id = __Request::get('id');
        $value = __Request::get('value');
        $key = __Request::get('key');
        $level = __Request::get('level');
        $parent = __Request::get('parent');

        $term = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Details');
        $term->thesaurusdetails_FK_thesaurus_id = $id;
        $term->thesaurusdetails_key = $key;
        $term->thesaurusdetails_value = $value;
        $term->thesaurusdetails_level = $level;
        $term->thesaurusdetails_parent = $parent;
        $term->thesaurusdetails_creationDate = new org_glizy_types_DateTime();
        $term->thesaurusdetails_modificationDate = new org_glizy_types_DateTime();
        if (__Config::get('metafad.thesaurus.filterInstitute')) {
            $term->thesaurusdetails_instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        }
        $term->save();

        return 'add';
    }
}
