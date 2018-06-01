<?php

class metafad_modules_thesaurus_models_proxy_ThesaurusDetailsProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails');
        if ($term != '') {
            $it->where('title', '%'.$term.'%', 'ILIKE');
        }
        if($proxyParams)
        {
          if($proxyParams->id)
          {
            $thesaurus = $proxyParams->id;
            $it->where('thesaurusdetails_FK_thesaurus_id',$thesaurus);
          }
          if($proxyParams->level)
          {
            $level = $proxyParams->level;
            $it->where('thesaurusdetails_level',(int)$level - 1);
          }
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->thesaurusdetails_id,
                'text' => $ar->thesaurusdetails_value,
            );
        }
        return $result;
    }

    public function findTermById($id)
    {
      $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails')
            ->where('thesaurusdetails_id',$id)->first();
      return $it;
    }
}
