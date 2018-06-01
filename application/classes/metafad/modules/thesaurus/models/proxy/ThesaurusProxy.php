<?php

class metafad_modules_thesaurus_models_proxy_ThesaurusProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusForms')
            ->load('findTerm', array('moduleId' => $proxyParams->module, 'fieldName' => $fieldName,'level' => $proxyParams->level));

        if($proxyParams->parentKey)
        {
          $id = clone $it;
          $thesaurusId = $id->first()->thesaurusdetails_FK_thesaurus_id;
          $idParent = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.Details')
                      ->where('thesaurusdetails_FK_thesaurus_id',$thesaurusId)
                      ->where('thesaurusdetails_key',$proxyParams->parentKey)
                      ->first()->thesaurusdetails_id;
          $it->where('thesaurusdetails_parent',$idParent);
        }

        if ($term != '') {
            $it->where('thesaurusdetails_value', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            if ($ar->thesaurusdetails_key == $ar->thesaurusdetails_value) {
                $text = $ar->thesaurusdetails_key;
            } else {
                $text = $ar->thesaurusdetails_key.' - '.$ar->thesaurusdetails_value;
            }

            $result[] = array(
                'id' => $ar->thesaurusdetails_key,
                'text' => $text
            );
        }

        return $result;
    }

    public function findOrCreate($name, $code)
    {
        $thesaurus = org_glizy_objectFactory::createModel('metafad.modules.thesaurus.models.Thesaurus');

        if (!$thesaurus->find(array('thesaurus_code' => $code))) {
            $thesaurus->thesaurus_name = $name;
            $thesaurus->thesaurus_code = $code;
            $thesaurus->thesaurus_creationDate = new org_glizy_types_DateTime();
            $thesaurus->thesaurus_modificationDate = new org_glizy_types_DateTime();
            $thesaurus->save();
        }
    }
}
