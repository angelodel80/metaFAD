<?php
class metafad_gestioneDati_boards_views_components_DataGridSolrFiltered extends metafad_common_views_components_DataGridSolr
{
    protected function processSolrResponse($solrResponse)
    {
        $aColumns = array();
        $aaData = array();
        $count = 0;
        $idList = explode("-",__Session::get('idList'));

        $queryString = 'id:(';
        foreach ($idList as $i) {
          $queryString .= '"'.$i.'",';
        }
        $queryString .= rtrim($query,",") . ')';

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',
            __Config::get('metafad.solr.url') . 'select?q='.$queryString.'&fq='.__Request::get('sSearch').'&wt=json');
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();
        $solrResponse = json_decode($request->getResponseBody());

        try {
            foreach ($solrResponse->response->docs as $row) {
                $rowToInsert = array();
                if(!in_array($row->id,$idList))
                {
                  continue;
                }
                else {
                  $count++;
                }
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
                            if($column['columnName'] != 'document_detail_status'){
                                if ($row->doc_store) {
                                    $value = $column['renderCell']->renderCell($row->id, $value, json_decode($row->doc_store[0]), $column['columnName'], $row);
                                }
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

        $output = array(
            "sEcho" => intval(__Request::get('sEcho')),
            "iTotalRecords" => $count,
            "iTotalDisplayRecords" => count($idList),
            "aaData" => $aaData
        );
        return $output;
    }
}
