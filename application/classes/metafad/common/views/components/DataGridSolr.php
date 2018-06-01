<?php
class metafad_common_views_components_DataGridSolr extends metafad_common_views_components_DataGridSolrBase
{
    function init()
    {
        // call the superclass for validate the attributes
        $this->defineAttribute('setLastSearch', false, false, COMPONENT_TYPE_BOOLEAN);
        parent::init();
    }

    public function getAjaxUrl()
    {
        $ajaxUrl = parent::getAjaxUrl();

        if ($this->getAttribute('parent')) {
            $ajaxUrl .= '&parent='.$this->getAttribute('parent');
        }

        return $ajaxUrl;
    }

    protected function getSolrResponse()
    {
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
            foreach($searchFields as $field){
                if ($decodeValue = json_decode($field)){
                    $field = $decodeValue->value;
                }
                $value = __Request::get($field);
                if ($value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $q[] = $field . ':'.$v;
                        }
                    } else {
                        $q[] = $field . ':'.$value;
                    }
                }
            }
        }

        if ($sSearch && empty($q)) {
            $q[] = '('.$sSearch.')';
        }

        if (!$this->getAttribute('ignoreTypeFilter')){
            $q[] = $modelQuery;
        }

        if ($this->issetAttribute('type')) {
            $q[] = 'livelloDiDescrizione_s:"'.$this->getAttribute('type').'"';
        }

        if ($this->issetAttribute('onlyRoots')) {
            $q[] = "-parent_i:*";
        }

        if ($this->getAttribute('hasDigital')) {
            $q[] = "digitale_i:1";
        }

        if (__Request::get('parent')) {
            $q[] = 'parent_i:"'.__Request::get('parent').'"';
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

        $searchQuery['indent'] = 'on';
        $searchQuery['wt'] = 'json';
        $searchQuery['sort'] = $sort;
        $searchQuery['q'] = implode(' AND ', $q);
        $searchQuery['fq'] = $arrayQuery;
        $searchQuery['start'] = $start;
        $searchQuery['rows'] = $length;
        $searchQuery['json.nl'] = 'map';

		//Faccette per autocomplete
		list($searchFields) = $this->getSearchFields();
		$facetFields = explode(',',implode(',',$searchFields));
		$searchQuery['facet.field'] = $facetFields;
		$searchQuery['facet'] = 'true';

        $queryString = self::buildHttpQuery($searchQuery);

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',
            __Config::get('metafad.solr.url') . 'select?'.$queryString);
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();

        if($this->getAttribute('setLastSearch'))
        {
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

    protected function processSolrResponse($solrResponse)
    {
        $aColumns = array();
        $aaData = array();
        try {
            foreach ($solrResponse->response->docs as $row) {
                $rowToInsert = array();

                foreach ($this->columns as $column) {
                    if ($column['acl']) {
                        if (!$this->_user->acl($column['acl']['service'], $column['acl']['action'])) {
                            continue;
                        }
                    }

                    $value = (is_string($row->$column['columnName'])) ? htmlspecialchars($row->$column['columnName']) : $row->$column['columnName'];
                    if ($column['renderCell']) {
                        if (!is_object($column['renderCell'])) {
                            $column['renderCell'] = org_glizy_ObjectFactory::createObject($column['renderCell'], $this->_application);
                        }

                        if (is_object($column['renderCell'])) {
                            if ($column['columnName'] != 'document_detail_status'){
                                //if ($row->doc_store) {
                                    $value = $column['renderCell']->renderCell($row->id, $value, json_decode($row->doc_store[0]), $column['columnName'], $row);
                                //}
                            } else{
                                if ($row->doc_store) {
                                    $value = $column['renderCell']->renderCell($row->id, json_decode($row->doc_store[0])->document_detail_status, json_decode($row->doc_store[0]), $column['columnName']);
                                }
                            }
                        }
                    }

                    if (is_object($value)) {
                        $value = json_encode($value);
                    }
                    $rowToInsert[] = $value;
                }
                $aaData[] = $rowToInsert;

            }
        } catch (Exception $e) {
            var_dump($e);
        }

        if ($this->getAttribute('dbDebug')) {
            org_glizy_dataAccessDoctrine_DataAccess::disableLogging();
            die;
        }

        $facets = array();

        if ($solrResponse->facet_counts->facet_fields) {
            $facets = $this->getFacetsValues($solrResponse->facet_counts->facet_fields);
        }

        $output = array(
            "sEcho" => intval(__Request::get('sEcho')),
            "iTotalRecords" => $solrResponse->response->numFound,
            "iTotalDisplayRecords" => $solrResponse->response->numFound,
            "aaData" => $aaData,
			"facets" => $facets
        );

        return $output;
    }

    public function process_ajax()
    {
        $solrResponse = $this->getSolrResponse();
        if (!$solrResponse) {
            $facets = array();

            if ($solrResponse->facet_counts->facet_fields) {
                $facets = $this->getFacetsValues($solrResponse->facet_counts->facet_fields);
            }

            $output = array(
                "sEcho" => intval(__Request::get('sEcho')),
                "iTotalRecords" => 0,
                "iTotalDisplayRecords" => 0,
                "aaData" => '',
				"facets" => $facets
            );
            return $output;
        }

        $output = $this->processSolrResponse($solrResponse);

        return $output;
    }

	//Estraggo faccette per autocomplete
	private function getFacetsValues($facets)
	{
		$facetsArray = array();
		foreach($facets as $k => $v)
		{
			foreach ($v as $val => $count) {
				if($count > 0)
				{
					$facetsArray[$k][] = $val;
				}
			}
		}
		return $facetsArray;
	}

	public function setColumnAttribute($i, $attribute, $text)
	{
	    $this->columns[$i][$attribute] = $text;
	}
}
