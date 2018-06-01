<?php

class metafad_sbn_modules_sbnunimarc_views_components_KardexGrid extends org_glizy_components_Component
{
    private $columns = array();

    function init()
    {
        $this->defineAttribute('kardexParam', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('cssClass', false, '', COMPONENT_TYPE_STRING);
        // call the superclass for validate the attributes
        parent::init();
    }

    function render_html()
    {
        $tableClass = $this->getAttribute("cssClass");
        $id = $this->getId();
        $ajaxUrl = $this->getAjaxUrl();

        $colSpan = 0;
        $headers = '';
        $aoColumnDefs = array();

        foreach ($this->columns as $column) {
            $colSpan++;
            $headers .= '<th';
            if (!$column['visible']) $headers .= ' style="display:none;"';
            if ($column['width']) $headers .= ' width="' . $column['width'] . '%"';
            $headers .= '>' . $column['headerText'] . '</th>';

            $aoColumnDefs[] = array(
                "bSortable" => $column['sortable'],
                "bSearchable" => $column['searchable'],
                "aTargets" => array($colSpan - 1),
                "sType" => "html",
                "sClass" => $column['cssClass']
            );
        }

        $aoColumnDefs = json_encode($aoColumnDefs);

        if (!org_glizy_ObjectValues::get('jquery.dataTables', 'add', false)) {
            org_glizy_ObjectValues::set('jquery.dataTables', 'add', true);
            $staticDir = org_glizy_Paths::get('STATIC_DIR');
            $html = '<script type="text/javascript" src="' . $staticDir . '/jquery/datatables/media/js/jquery.dataTables.min.js"></script>';
            $html .= '<script type=""text/javascript" src="' . $staticDir . '/jquery/datatables/media/js/jquery.dataTables.bootstrap.js"></script>';
        }

        $cookieName = 'DataTables_' . __Config::get('SESSION_PREFIX') . $this->getId() . $this->_application->getPageId();
        $sLengthMenu = __T('records per page');
        $sEmptyTable = __T('No record found');
        $sZeroRecords = __T('No record found with current filters');
        $sInfo = __T('Showing _START_ to _END_ of _TOTAL_ entries');
        $sInfoEmpty = __T('Showing 0 to 0 of 0 entries');
        $sInfoFiltered = __T('filtered from _MAX_ total entries');
        $sLoadingRecords = __T('Loading...');
        $sLoadingFromServer = __T('Loading data from server');
        $sProcessing = __T('Processing...');
        $Search = __T('Search');
        $sFirst = __T('First');
        $sLast = __T('Last');
        $sNext = __T('Next');
        $sPrevious = __T('Previous');
        $JQueryUI = $this->getAttribute('JQueryUI') ? 'true' : 'false';

        $kardexParam = (__Session::get('kardexUrl')) ? __Session::get('kardexUrl') : $this->getAttribute('kardexParam');
        $html .= <<<EOD
        <table class="table table-bordered table-striped dataTable" id="$id">
            <thead>
            <tr>
                <th colspan="$colSpan" ><span style="float:right; font-weight: 600; cursor: pointer;" class="CloseGrid"><i class="fa fa-close" style="margin-right: 3px"></i>Chiudi</span></th>
            </tr>
                <tr >
                    $headers
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="$colSpan" style="text-align: center" class="dataTables_empty">$sLoadingFromServer</td>
                </tr>
            </tbody>
        </table>
<script type="text/javascript">

// <![CDATA[
\$( function(){
    var table = \$('#$id').dataTable( {
        "sDom": "<'row-fluid filter-row clearfix'<'filter-box'l><'filter-box'>r><'table-container't><'row-fluid clearfix'<'filter-box pull-left'i><'filter-box pull-right'p>>",
        "sPaginationType": "bootstrap",
        "bPaginate": false,
        "bRetrieve": true,
        "bLengthChange" : false, //thought this line could hide the LengthMenu
        "bSort" : false,
        "oLanguage": {
            "sLengthMenu": "_MENU_ $sLengthMenu",
            "sEmptyTable": "Nessun fascicolo trovato",
            "sZeroRecords": "$sZeroRecords",
            "sInfo": "$sInfo",
            "sInfoEmpty": "$sInfoEmpty",
            "sInfoFiltered": "($sInfoFiltered)",
            "sLoadingRecords": "$sLoadingRecords",
            "sProcessing": "$sProcessing",
            "sSearch": "{$Search}:",
            "oPaginate": {
                "sFirst": "$sFirst",
                "sLast": "$sLast",
                "sNext": "$sNext",
                "sPrevious": "$sPrevious"
            }
        },
        "bJQueryUI": $JQueryUI,
        "bServerSide": false,
        "sAjaxSource": "$ajaxUrl",
        "aoColumnDefs": $aoColumnDefs,
        "bStateSave": true,
        "fnStateSave": function (oSettings, oData) {
            $('#$id input[data-type]').each(function() {
                $('#editForm').data('instance').createField(this);
                if ($(this).data('value')) {
                    var data = $(this).data('value').replace(/##/g,'"');
                    data = JSON.parse(data);
                    data.noCallBack = true;
                    $(this).data('instance').setValue(data);
                }
            });
            localStorage.setItem( "$cookieName", JSON.stringify(oData) );
        },
        "fnStateLoad": function (oSettings) {
            return JSON.parse( localStorage.getItem("$cookieName") );
        },
        "fnServerParams": function ( aoData ) {
            aoData.push( { "name": "kardexParam", "value": "$kardexParam" } );
        }
    } );

    \$('#$id').data('dataTable', table);
});
// ]]>
</script>
EOD;

        $this->addOutputCode($html);
    }


    public function getAjaxUrl()
    {
        return parent::getAjaxUrl() . __Request::get('action', 'Index');
    }

    public function addColumn($column)
    {
        if (preg_match("/\{i18n\:.*\}/i", $column['headerText'])) {
            $code = preg_replace("/\{i18n\:(.*)\}/i", "$1", $column['headerText']);
            $column['headerText'] = org_glizy_locale_Locale::getPlain($code);
        }

        $this->columns[] = $column;
    }

    function process_ajax()
    {
        $url = __Request::get('kardexParam');
        if (!$url) {
            $output = array(
                "sEcho" => intval(__Request::get('sEcho')),
                "iTotalRecords" => 0,
                "iTotalDisplayRecords" => 0,
                "aaData" => array()
            );

            return $output;
        }

        $kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
        $data = $kardexService->getData($url);
        
        if ($data) {
            $data = json_decode($data);
        }

        $aColumns = array();

        foreach ($this->columns as $column) {
            if (!in_array($column['columnName'], $aColumns)) {
                $aColumns[] = $column['columnName'];
            }
        }

        $aColumns = array();
        $count = 0;

        $aaData = array();
        try {
            if (isset($data->kardexType->inventario[0]->fascicolo) ) {
                foreach ($data->kardexType->inventario[0]->fascicolo as $row) {
                    $rowToInsert = array();
                    foreach ($this->columns as $column) {
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
                    $find = false;
                    foreach ($rowToInsert as $val) {
                        if (isset($val)) {
                            $find = true;
                        }
                    }
                    if ($find) {
                        $count++;
                        $aaData[] = $rowToInsert;
                    }
                }
            } else{
                $output = array(
                    "sEcho" => intval(__Request::get('sEcho')),
                    "iTotalRecords" => 0,
                    "iTotalDisplayRecords" => 0,
                    "aaData" => array()
                );

                return $output;
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
            "iTotalDisplayRecords" => $count,
            "aaData" => $aaData
        );

        return $output;
    }

    public static function compile($compiler, &$node, &$registredNameSpaces, &$counter, $parent = 'NULL', $idPrefix, $componentClassInfo, $componentId)
    {
        $compiler->_classSource .= '$n' . $counter . ' = org_glizy_ObjectFactory::createComponent(\'' . $componentClassInfo['classPath'] . '\', $application, ' . $parent . ', \'' . $node->nodeName . '\', ' . $idPrefix . '\'' . $componentId . '\', \'' . $componentId . '\', $skipImport)' . GLZ_COMPILER_NEWLINE;

        if ($parent != 'NULL') {
            $compiler->_classSource .= $parent . '->addChild($n' . $counter . ')' . GLZ_COMPILER_NEWLINE;
        }

        if (count($node->attributes)) {
            // compila  gli attributi
            $compiler->_classSource .= '$attributes = array(';
            foreach ($node->attributes as $key => $value) {
                if ($key != 'id') {
                    $compiler->_classSource .= '\'' . $key . '\' => \'' . addslashes($node->getAttribute($key)) . '\', ';
                }
            }
            $compiler->_classSource .= ')' . GLZ_COMPILER_NEWLINE;
            $compiler->_classSource .= '$n' . $counter . '->setAttributes( $attributes )' . GLZ_COMPILER_NEWLINE;
        }


        foreach ($node->childNodes as $n) {
            if (strpos($n->nodeName, ":DataGridColumn") !== false) {
                $params = array();
                $params['sortable'] = $n->hasAttribute('sortable') ? $n->getAttribute('sortable') == 'true' : true;
                $params['searchable'] = $n->hasAttribute('searchable') ? $n->getAttribute('searchable') == 'true' : true;
                $params['visible'] = $n->hasAttribute('visible') ? $n->getAttribute('visible') == 'true' : true;
                $params['columnName'] = $n->hasAttribute('columnName') ? $n->getAttribute('columnName') : '';
                $params['headerText'] = $n->hasAttribute('headerText') ? $n->getAttribute('headerText') : '';
                $params['width'] = $n->hasAttribute('width') ? $n->getAttribute('width') : '';
                $params['acl'] = $n->hasAttribute('acl') ? $n->getAttribute('acl') : '';
                $params['cssClass'] = $n->hasAttribute('cssClass') ? $n->getAttribute('cssClass') : '';
                $params['renderCell'] = $n->hasAttribute('renderCell') ? $n->getAttribute('renderCell') : '';
                if ($params['acl']) {
                    list($service, $action) = explode(',', $params['acl']);
                    $params['acl'] = array('service' => $service, 'action' => $action);
                }
                $compiler->_classSource .= '$n' . $counter . '->addColumn(' . var_export($params, true) . ');';
            }
        }
    }

    protected function buildHttpQuery($searchQuery)
    {
        $temp = array_merge($searchQuery, array());
        $url = "";
        unset($temp['url']);
        unset($temp['action']);
        foreach ($searchQuery as $k => $v) {
            if (is_array($v)) {
                if ($k == 'facet.field' || $k == 'fq') {
                    foreach ($v as $v1) {
                        $url .= $k . '=' . $v1 . '&';
                    }
                    unset($temp[$k]);
                } else {
                    $temp[$k] = implode($v, ',');
                }
            }
        }

        return $url . http_build_query($temp);
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }
}
