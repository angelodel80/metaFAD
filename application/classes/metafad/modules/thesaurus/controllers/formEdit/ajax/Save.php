<?php

class metafad_modules_thesaurus_controllers_formEdit_ajax_Save extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    function execute($data){
        $decodeData = json_decode($data);
        $thesaurusId = $decodeData->__id;
        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');

        //Prima di tutto salvo (o carico, se già esistente) il dizionario
        $ar = org_glizy_ObjectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');
        if ($thesaurusId){
            $ar->load($thesaurusId);
        }
        $ar->thesaurus_name = $decodeData->thesaurus_name;
        $ar->thesaurus_code = $decodeData->thesaurus_code;
        if ($thesaurusId) {
            $ar->thesaurus_modificationDate = new org_glizy_types_DateTime();
            $ar->save();
        } else {
            $ar->thesaurus_creationDate = new org_glizy_types_DateTime();
            $ar->thesaurus_modificationDate = new org_glizy_types_DateTime();
            $thesaurusId = $ar->save();
        }

        //Salvataggio schede collegate e relative voci selezionate
        if($decodeData->relatedBoardIccd){
            $oldArForms = org_glizy_ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.Forms');
            $oldArForms->select('thesaurusforms_id')->where('thesaurusforms_FK_thesaurus_id', $thesaurusId);
            foreach($oldArForms as $oldArForm ){
                $arrayOldForms[] = $oldArForm->getRawData()->thesaurusforms_id;
            }
            foreach ($decodeData->relatedBoardIccd as $key => $value) {
                $arrayForms[] = $value->thesaurusFormsId;
            }

            if(is_array($arrayOldForms))
            {
              $diff = array_diff($arrayForms, $arrayOldForms);
              $intersect = array_intersect($arrayForms, $arrayOldForms);

              $formsToDelete = array_diff($arrayOldForms, $intersect);

              foreach($formsToDelete as $formToDelete){
                  $contentproxy->delete($formToDelete, 'metafad.modules.thesaurus.models.Forms');
              }
            }

            foreach ($decodeData->relatedBoardIccd as $key => $value) {
                $thesaurusFormId = $value->thesaurusFormsId;
                $arForms = org_glizy_ObjectFactory::createModel('metafad.modules.thesaurus.models.Forms');
                if ($thesaurusFormId){
                    $arForms->load($thesaurusFormId);
                }
                if($value->boardName && $value->thesaurusName) {
                    $arForms->thesaurusforms_FK_thesaurus_id = $thesaurusId;
                    $arForms->thesaurusforms_name = $value->boardName->text;
                    $arForms->thesaurusforms_field = $value->thesaurusName->id;
                    $arForms->thesaurusforms_level = $value->boardLevel;
                    if(!is_int($value->boardName->id))
                    {
                      $arForms->thesaurusforms_moduleId = $value->boardName->id;
                    }

                    if ($thesaurusFormId) {
                        $arForms->thesaurus_modificationDate = new org_glizy_types_DateTime();
                        $temp = $arForms->save();
                    } else {
                        $arForms->thesaurus_creationDate = new org_glizy_types_DateTime();
                        $arForms->thesaurus_modificationDate = new org_glizy_types_DateTime();
                        $thesaurusFormId = $arForms->save();
                    }
                }
            }
        }

        //Il salvataggio delle voci del dizionario viene fatto inline, quindi
        //non si troverà in questo file

        //parent::execute($data);
        die;
        return;
    }

}
