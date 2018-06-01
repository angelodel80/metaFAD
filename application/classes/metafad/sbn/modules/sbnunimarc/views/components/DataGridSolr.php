<?php

class metafad_sbn_modules_sbnunimarc_views_components_DataGridSolr extends metafad_common_views_components_DataGridSolrBase
{
    function process_ajax()
    {
        $length = $_GET["iDisplayLength"];
        $start = $_GET["iDisplayStart"];

        $sSearch = __Request::get('sSearch');

        $url = __Config::get('metafad.solr.url') . 'select';

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

		$document = org_glizy_objectFactory::createModel($this->getAttribute('recordClassName'));
        $searchFields = $document->getBeAdvancedSearchFields();

        foreach ($searchFields as $field) {
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
                        $q[] = $f[0] . ':[' . sprintf('%04d', $v[0]) . ' TO *]';
                    }

                    if ($v[1]) {
                        $q[] = $f[1] . ':[* TO ' . sprintf('%04d', $v[1]) . ']';
                    }
                } else if($type == 'text'){
                    $q[] = $field . ':' . '*' . str_replace(' ','*',$value) . '*';
                } else {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $q[] = $field . ':' . $this->quoteValue($v);
                        }
                    } else {
                        $q[] = $field . ':' . $this->quoteValue($value);
                    }

                    if ($field == 'livelloDiDescrizione_s') {
                        $typeSet = true;
                    }
                }
            }
        }

        $q[] = 'docType_s:'.$this->getAttribute('docType');

        if ($sSearch) {
            $q[] = '"' . $sSearch . '"';
        }

        $searchQuery = array();
        $searchQuery['indent'] = 'on';
        $searchQuery['wt'] = 'json';
        $searchQuery['sort'] = 'id asc';
        $searchQuery['q'] = implode(' AND ', $q);
        $searchQuery['fq'] = $arrayQuery;
        $searchQuery['start'] = $start;
        $searchQuery['rows'] = $length;
        $searchQuery['json.nl'] = 'map';
        $postBody = self::buildHttpQuery($searchQuery);

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',
            $url,
            'POST',
            $postBody,
            'application/x-www-form-urlencoded');
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();
        $solrResponse = json_decode($request->getResponseBody());

        $aColumns = array();

        $aaData = array();
        try {
            if ($solrResponse->response) {
                foreach ($solrResponse->response->docs as $row) {
                    $rowToInsert = array();

                    foreach ($this->columns as $column) {
                        if ($column['acl']) {
                            if (!$this->_user->acl($column['acl']['service'], $column['acl']['action'])) {
                                continue;
                            }
                        }

                        $value = $row->$column['columnName'];
                        if ($column['renderCell']) {
                            if (!is_object($column['renderCell'])) {
                                $column['renderCell'] = org_glizy_ObjectFactory::createObject($column['renderCell'], $this->_application);
                            }

                            if (is_object($column['renderCell'])) {
                                $value = $column['renderCell']->renderCell($row->document_id_nxs, $row->document_id_nxs, $row);
                            }
                        }

                        if (is_object($value)) {
                            $value = json_encode($value);
                        }
                        $rowToInsert[] = $value;
                    }
                    $aaData[] = $rowToInsert;
                }
            }
        } catch (Exception $e) {
            var_dump($e);
        }

        if ($this->getAttribute('dbDebug')) {
            org_glizy_dataAccessDoctrine_DataAccess::disableLogging();
            die;
        }

        $output = array(
            "sEcho" => intval(__Request::get('sEcho')),
            "iTotalRecords" => $solrResponse->response->numFound,
            "iTotalDisplayRecords" => $solrResponse->response->numFound,
            "aaData" => $aaData
        );
        return $output;
    }

    protected function quoteValue($value)
    {
        return ($value == '*') ? $value : '"' . $value . '"';
    }
}
