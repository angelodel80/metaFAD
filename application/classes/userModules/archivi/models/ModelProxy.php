<?php
class archivi_models_ModelProxy extends GlizyObject
{
    /**
     * @param $fieldName
     * @param $model
     * @param $query
     * @param $term
     * @param null|stdClass $proxyParams Se contiene una stdClass alla chiave "indexedSearchCriteria", applica ulteriori criteri di ricerca
     * @return array
     */
    public function findTerm($fieldName, $model, $query, $term, $proxyParams = null)
    {
        $it = $this->searchByTerms($model, $term, $proxyParams);

        $result = $this->createArrayFromIterator($it);

        $ret = $this->sortResultsByText($result);

        return $ret;
    }

    /**
     * @param $it
     * @return array
     */
    private function createArrayFromIterator($it)
    {
        $i = 0;
        $max = min($it->count(), 100);
        $result = array();
        while (++$i <= $max) {
            $ar = $it->current();

            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->_denominazione
            );

            $it->next();
        }
        return $result;
    }

    /**
     * @param $result
     * @return array
     */
    private function sortResultsByText($result)
    {
        uasort($result, function ($a, $b) {
            $nameA = explode("||", $a['text']);
            $nameA = count($nameA) > 1 ? trim($nameA[1]) : trim($nameA[0]);
            $nameB = explode("||", $b['text']);
            $nameB = count($nameB) > 1 ? trim($nameB[1]) : trim($nameB[0]);

            return strcasecmp($nameA, $nameB);
        });

        $ret = array();
        foreach ($result as $record) {
            $ret[] = $record;
        }
        return $ret;
    }

    /**
     * @param $model
     * @param $term
     * @param null|stdClass $proxyParams
     * @return mixed
     */
    private function searchByTerms($model, $term, $proxyParams = null)
    {
        $criteria = $proxyParams && property_exists($proxyParams, "indexedSearchCriteria") ? $proxyParams->indexedSearchCriteria : new stdClass();

        $instKey = metafad_usersAndPermissions_Common::getInstituteKey();

        $shouldFilter = $this->shouldFilterByInstitute($model, $instKey);

        $it = org_glizy_ObjectFactory::createModelIterator($model)
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
            ->where("_denominazione", "%$term%", 'ILIKE');

        foreach ($criteria as $field => $value) {
            $it->where($field, $value, 'ILIKE');
        }

        return $shouldFilter ? $it->where("instituteKey", $instKey) : $it;
    }

    private function shouldFilterByInstitute($model, $instKey){
        $viewAll = array(
            "archivi.models.SchedaBibliografica",
            "archivi.models.ProduttoreConservatore",
            "archivi.models.Enti",
            "archivi.models.Toponimi",
            "archivi.models.Antroponimi"
        );

        return !in_array($model, $viewAll) && $instKey && $instKey != "*";
    }
}