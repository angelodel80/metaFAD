<?php
class archivi_views_components_DataGridSolr extends metafad_common_views_components_DataGridSolr
{
    protected function quoteValue($value)
    {
        return ($value == '*') ? $value : '"'.$value.'"';
    }

    protected function getSolrResponse()
    {
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

        $typeSet = false;
        $q = array();

        for ($i = 0; $i < count($aColumns); $i++) {
            if (__Request::get('sSearch_' . $i)) {
                $q[] = $aColumns[$i].':'.__Request::get('sSearch_' . $i);;

                if ($aColumns[$i] == 'livelloDiDescrizione_s') {
                    $typeSet = true;
                }
            }
        }

        list($searchFields, $modelQuery) = $this->getSearchFields(true);

        if ($searchFields) {
            foreach($searchFields as $field){
                if (is_array($field)) {
                    $type = $field['type'];
                    $label = $field['label'];
                    $field = $field['field'];
                }

                $value = __Request::get($field);
                if ($value) {
                    $this->setAttribute('onlyRoots', false);

                    if ($type == 'date' || $type == 'dateCentury') {
                        $f = explode(',', $field);
                        $v = explode(',', $value);

                        if ($type = 'dateCentury') {
                            $romanService = __ObjectFactory::createObject('metafad.common.helpers.RomanService');
                            $v[0] = $romanService->romanToInteger($v[0]);
                            $v[1] = $romanService->romanToInteger($v[1]);
                        }

                        if ($v[0]) {
                            $q[] = $f[0] . ':['.sprintf('%04d', $v[0]).' TO *]';
                        }

                        if ($v[1]) {
                            $q[] = $f[1] . ':[* TO '.sprintf('%04d', $v[1]).']';
                        }
                    } else {
                        if (is_array($value)) {
                            foreach ($value as $v) {
                                $q[] = $field . ':' . $this->quoteValue($v);
                            }
                        } else {
                            $q[] = $field . ':'. $this->quoteValue($value);
                        }

                        if ($field == 'livelloDiDescrizione_s') {
                            $typeSet = true;
                        }
                    }
                }
            }
        }

        if ($sSearch) {
            $q[] = '('.$sSearch.'*)';
        }
        
        if (!$typeSet) {
            if (!$this->getAttribute('ignoreTypeFilter') ) {
                $q[] = $modelQuery;
            }
            
            if ($this->issetAttribute('type')) {
                $q[] = 'livelloDiDescrizione_s:'.$this->quoteValue($this->getAttribute('type'));
            }
            
            if ($this->getAttribute('onlyRoots')) {
                $q[] = "-parent_i:*";
            }

            if (__Request::get('parent')) {
                $q[] = 'parent_i:'.__Request::get('parent').'';
            }
        } else {
            if (__Request::get('parent')) {
                $q[] = 'parents_is:'.__Request::get('parent').'';
            }
        }

        if ($this->getAttribute('hasDigital')) {
            $q[] = "digitale_i:1";
        }

        $curInstituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        if ($this->getAttribute('filterByInstitute') && $curInstituteKey && $curInstituteKey != "*") {
            $q[] = 'instituteKey_s:"'.$curInstituteKey.'"';
        }

        $sortField = 'document_id';
        
        if ( __Request::exists('iSortCol_0') ) {
            $iSortingCols = intval( __Request::get( 'iSortingCols' ));
            for ( $i=0 ; $i<$iSortingCols ; $i++ ) {
                $sortCol = intval( __Request::get('iSortCol_'.$i));
                if ( __Request::get( 'bSortable_'.$sortCol ) == "true" ) {
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
        
        $searchQuery = array(
            'indent' => 'on',
            'wt' => 'json', 
            'sort' => $sort, 
            'q' => implode(' AND ', $q), 
            'fq' =>  $arrayQuery, 
            'start' =>  __Request::get('iDisplayStart'), 
            'rows' =>  __Request::get('iDisplayLength'),
            'json.nl' =>  'map', 
        );

        $queryString = self::buildHttpQuery($searchQuery);

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',
            __Config::get('metafad.solr.url') . 'select?'.$queryString);
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();

        if ($this->getAttribute('setLastSearch')) {
            $numFound = json_decode($request->getResponseBody())->response->numFound;
            __Session::set('lastSearch',array('search'=>__Config::get('metafad.solr.url').'select?'.$queryString,'numFound'=>$numFound));
        }

        if ($request->execute()) {
			$responseBody = json_decode($request->getResponseBody());
            return $responseBody;
        } else {
            return null;
        }
    }
}