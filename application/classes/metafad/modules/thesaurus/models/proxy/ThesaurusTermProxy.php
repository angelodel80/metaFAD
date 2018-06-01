<?php

class metafad_modules_thesaurus_models_proxy_ThesaurusTermProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $dictCode = $proxyParams ? ($proxyParams->dictionaryCode ?: "") : "";
        if (!$dictCode) {
            return array();
        }


        $it = __ObjectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails');
        if ($term != '') {
            $it->where('thesaurusdetails_value', '%' . $term . '%', 'ILIKE');
        }
        $it->where('thesaurus_code', $dictCode);


        $result = array();
        $limit = max(1, $proxyParams->resultLimit ?: 25);
        foreach ($it as $ar) {
            $result[] = array(
                'id' => $ar->thesaurusdetails_key,
                'text' => $ar->thesaurusdetails_value,
            );

            if (--$limit < 1){
                break;
            }
        }
        return $result;
    }

    public function findTermById($id)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.modules.thesaurus.models.ThesaurusDetails')
            ->where('thesaurusdetails_id', $id)->first();
        return $it;
    }
}