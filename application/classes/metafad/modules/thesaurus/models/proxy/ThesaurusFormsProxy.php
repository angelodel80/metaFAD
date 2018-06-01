<?php

class metafad_modules_thesaurus_models_proxy_ThesaurusFormsProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusForms');
        $it->select('thesaurusforms_name')->groupBy('thesaurusforms_name');
        if ($term != '') {
            $it->where('title', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->thesaurusforms_name,
            );
        }

        return $result;
    }

    public function findOrCreate($thesaurusCode, $moduleId, $moduleName, $fieldName, $level)
    {
        $thesaurusForms = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.ThesaurusForms');
        $result = $thesaurusForms->find(array(
                'thesaurus_code' => $thesaurusCode,
                'thesaurusforms_field' => $fieldName,
                'thesaurusforms_moduleId' => $moduleId
        ));

        if (!$result) {
            $thesaurus = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');
            $thesaurus->find(array('thesaurus_code' => $thesaurusCode));

            $forms = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Forms');
            $forms->thesaurusforms_FK_thesaurus_id = $thesaurus->getId();
            $forms->thesaurusforms_moduleId = $moduleId;
            $forms->thesaurusforms_name = $moduleName;
            $forms->thesaurusforms_field = $fieldName;
            $forms->thesaurusforms_level = $level;
            $forms->thesaurusforms_creationDate = new org_glizy_types_DateTime();
            $forms->thesaurusforms_modificationDate = new org_glizy_types_DateTime();
            $forms->save();
        }
    }

    public function deleteForm($moduleId)
    {
        $forms = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Forms');
        $forms->delete(array('thesaurusforms_moduleId' => $moduleId));
    }
}