<?php
class metafad_modules_thesaurus_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('delete');

        $id = (int)$id;
        $thesaurus = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Thesaurus')
                     ->where('thesaurus_id',$id)->first();
        $forms = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Forms')
                 ->where('thesaurusforms_FK_thesaurus_id',$id);
        $details = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
                   ->where('thesaurusdetails_FK_thesaurus_id',$id);

        metafad_Metafad::logAction('metafad_modules_thesaurus_controllers_Delete: '.$thesaurus->thesaurus_name, 'thesaurus');

        if ($thesaurus !== null){
          $thesaurus->delete();
        }
        foreach ($forms as $f) {
          $f->delete();
        }
        foreach ($details as $d) {
          $d->delete();
        }

        org_glizy_helpers_Navigation::goHere();
    }
}
