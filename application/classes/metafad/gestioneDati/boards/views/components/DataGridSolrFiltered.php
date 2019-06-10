<?php
class metafad_gestioneDati_boards_views_components_DataGridSolrFiltered extends metafad_common_views_components_DataGridSolr
{
    protected function getSolrResponse()
    {
        $idList = explode("-", __Session::get('idList'));
        $ids = array();
        foreach ($idList as $i) {
            $ids[] = '"' . $i . '"';
        }

        $length = $_GET["iDisplayLength"];
        $start = $_GET["iDisplayStart"];

        if ($pos = strpos($_SERVER['HTTP_REFERER'], 'listDetail')) {
            $idValue = intval(substr_replace($_SERVER['HTTP_REFERER'], '', 0, $pos + 11));
        }

        $sSearch = __Request::get('sSearch');

        $aColumns = array();

        foreach ($this->columns as $column) {
            if (!in_array($column['columnName'], $aColumns)) {
                $aColumns[] = $column['columnName'];
            }
        }

        for ($i = 0; $i < count($aColumns); $i++) {
            if (__Request::get('sSearch_' . $i)) {
                $filters[$aColumns[$i]] = __Request::get('sSearch_' . $i);
            }
        }

        $q = array();
        $searchQuery = array();

        list($searchFields, $modelQuery) = $this->getSearchFields(true);

        if ($searchFields) {
            foreach ($searchFields as $field) {
                if ($decodeValue = json_decode($field)) {
                    $field = $decodeValue->value;
                }
                $value = __Request::get($field);
                if ($value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $q[] = $field . ':' . $v;
                        }
                    } else {
                        $q[] = $field . ':' . $value;
                    }
                }
            }
        }

        if ($sSearch && empty($q)) {
            $q[] = '(' . $sSearch . ')';
        }

        if (!$this->getAttribute('ignoreTypeFilter')) {
            $q[] = $modelQuery;
        }

        if ($this->issetAttribute('type')) {
            $q[] = 'livelloDiDescrizione_s:"' . $this->getAttribute('type') . '"';
        }

        if ($this->issetAttribute('onlyRoots')) {
            $q[] = "-parent_i:*";
        }

        if ($this->getAttribute('hasDigital')) {
            $q[] = "digitale_i:1";
        }

        if (__Request::get('parent')) {
            $q[] = 'parent_i:"' . __Request::get('parent') . '"';
        }

        $curInstituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        if ($this->getAttribute('filterByInstitute') && $curInstituteKey && $curInstituteKey != "*") {
            $q[] = 'instituteKey_s:"' . $curInstituteKey . '"';
        }

        $sortField = 'document_id';

        if (__Request::exists('iSortCol_0')) {
            $iSortingCols = intval(__Request::get('iSortingCols'));
            for ($i = 0; $i < $iSortingCols; $i++) {
                $sortCol = intval(__Request::get('iSortCol_' . $i));
                if (__Request::get('bSortable_' . $sortCol) == "true") {
                    $sortField = $aColumns[$sortCol];
                    break;
                }
            }
        }

        // i campi _t non sono ordinabili
        if (preg_match('/_t$/', $sortField) || $sortField == 'document_id') {
            $sort = 'updated_at_s desc, id asc';
        } else {
            $sort = $sortField . ' ' . __Request::get('sSortDir_0', 'asc') . ", updated_at_s desc";
        }

        $q[] = 'id:(' . implode(' OR ', $ids) . ')';

        $searchQuery['indent'] = 'on';
        $searchQuery['wt'] = 'json';
        $searchQuery['sort'] = $sort;
        $searchQuery['q'] = implode(' AND ', $q);
        $searchQuery['fq'] = (!empty($arrayQuery)) ? : array();
        $searchQuery['start'] = $start;
        $searchQuery['rows'] = $length;
        $searchQuery['json.nl'] = 'map';

        //Multilingua
        if ($this->getAttribute('multiLanguage') && metafad_common_helpers_LanguageHelper::checkLanguage($this->getAttribute('recordClassName'))) {
            $languagePrefix = $this->_application->getEditingLanguage();
            $searchQuery['fq'][] = 'language_s:"' . $languagePrefix . '"';
        }
        
		//Faccette per autocomplete
        list($searchFields) = $this->getSearchFields();
        $facetFields = explode(',', implode(',', $searchFields));
        $searchQuery['facet.field'] = $facetFields;
        $searchQuery['facet'] = 'true';

        $queryString = self::buildHttpQuery($searchQuery);

        $request = org_glizy_objectFactory::createObject(
            'org.glizy.rest.core.RestRequest',
            __Config::get('metafad.solr.url') . 'select?' . $queryString
        );
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();

        if ($this->getAttribute('setLastSearch')) {
            $numFound = json_decode($request->getResponseBody())->response->numFound;
            __Session::set('lastSearch', array('search' => __Config::get('metafad.solr.url') . 'select?' . $queryString, 'numFound' => $numFound));
        }

        if ($request->execute()) {
            $responseBody = json_decode($request->getResponseBody());
            return $responseBody;
        } else {
            return null;
        }
    }
}
