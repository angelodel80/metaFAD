<?php
class metafad_common_views_components_DataGridAjax extends org_glizy_components_DataGridAjax
{
    private $columns = array();

    function init()
    {
        $this->defineAttribute('cssClass', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('recordClassName', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('query', false, 'all', COMPONENT_TYPE_STRING);
        $this->defineAttribute('queryOperator', false, 'OR', COMPONENT_TYPE_STRING);
        $this->defineAttribute('fullTextSearch', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('setFiltersToQuery', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('JQueryUI', false, true, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('dbDebug', false, false, COMPONENT_TYPE_BOOLEAN);

        // call the superclass for validate the attributes
        parent::init();
    }

    function render_html(){
        $tableClass = $this->getAttribute( "cssClass" );
        $id = $this->getId();
        $ajaxUrl = $this->getAjaxUrl();

        $colSpan = 0;
        $headers = '';
        $aoColumnDefs = array();

        foreach( $this->columns as $column )
        {
            if ( $column['acl'] ) {
                if (!$this->_user->acl($column['acl']['service'], $column['acl']['action'])) {
                    continue;
                }
            }

            $colSpan++;
            $headers .= '<th';
            if ( !$column['visible'] ) $headers .= ' style="display:none;"';
            if ( $column['width'] ) $headers .= ' width="'.$column['width'].'%"';
            $headers .= '>'.$column['headerText'].'</th>';

            $aoColumnDefs[] = array (
                "bSortable" => $column['sortable'],
                "bSearchable" => $column['searchable'],
                "aTargets" => array($colSpan-1),
                "sType" => "html",
                "sClass" => $column['cssClass']
            );
        }

        $aoColumnDefs = json_encode($aoColumnDefs);

        if (!org_glizy_ObjectValues::get('jquery.dataTables', 'add', false))
        {
            org_glizy_ObjectValues::set('jquery.dataTables', 'add', true);
            $staticDir = org_glizy_Paths::get('STATIC_DIR');
            $html = '<script type="text/javascript" src="'.$staticDir.'/jquery/datatables/media/js/jquery.dataTables.min.js"></script>';
            $html .= '<script type=""text/javascript" src="'.$staticDir.'/jquery/datatables/media/js/jquery.dataTables.custom.js"></script>';
        }

        $cookieName = 'DataTables_'.__Config::get('SESSION_PREFIX').$this->getId().$this->_application->getPageId();
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

        $html .= <<<EOD
        <table class="$tableClass" id="$id">
            <thead>
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
        "sDom": "<'row-fluid filter-row clearfix'<'filter-box'f>r><'table-container't><'row-fluid clearfix'<'filter-box select-page-length'l><'filter-box pull-left'i><'filter-box pull-right'p>>",
        "sPaginationType": "custom",
        "oLanguage": {
            "sLengthMenu": "_MENU_ $sLengthMenu",
            "sEmptyTable": "$sEmptyTable",
            "sZeroRecords": "$sZeroRecords",
            "sInfo": "$sInfo",
            "sInfoEmpty": "$sInfoEmpty",
            "sInfoFiltered": "($sInfoFiltered)",
            "sLoadingRecords": "$sLoadingRecords",
            "sProcessing": "$sProcessing",
            "sSearch": "",
            "oPaginate": {
                "sFirst": "$sFirst",
                "sLast": "$sLast",
                "sNext": "$sNext",
                "sPrevious": "$sPrevious"
            }
        },
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "url": sSource,
                "data": aoData,
                "dataType": 'json',
                "cache": false,
                "type": oSettings.sServerMethod,
                "success": function(json) {
                    var index, iDisplayStart;
                    $.each(aoData, function(key, val) {
                        if (val.name == 'iDisplayStart') {
                            index = key;
                            iDisplayStart = val.value;
                        }
                    });
                    if (json.iTotalRecords <= iDisplayStart) {
                        aoData[index].value = 0;
                        oSettings.oInstance.fnPageChange('first', false);
                        oSettings.fnStateSave(oSettings, aoData);
                        oSettings.jqXHR = $.ajax({
                            "url": sSource,
                            "data": aoData,
                            "dataType": 'json',
                            "cache": false,
                            "type": oSettings.sServerMethod,
                            "success": fnCallback
                        });
                    } else {
                        fnCallback(json);
                    }
                }
            });
        },
        "bJQueryUI": $JQueryUI,
        "bServerSide": true,
        "sAjaxSource": "$ajaxUrl",
        "aoColumnDefs": $aoColumnDefs,
        "bStateSave": true,
        "fnStateSave": function (oSettings, oData) {
            localStorage.setItem( "$cookieName", JSON.stringify(oData) );
        },
        "fnStateLoad": function (oSettings) {
            return JSON.parse( localStorage.getItem("$cookieName") );
        }
    } );

    $('<i class="fa fa-search searchIcon"></i>').appendTo("#{$id}_filter > label:first-child");
    \$('#$id').data('dataTable', table);

    $(document).ready( function () {
        $('#dataGrid_filter input').attr('class', 'form-control').attr('placeholder', 'Filtra elenco...').val('');

        var oTable = $('#$id').DataTable();

        function fnResetAllFilters() {
        console.log(oTable.fnSettings());
            var oSettings = oTable.fnSettings();
            for(iCol = 0; iCol < oSettings.aoPreSearchCols.length; iCol++) {
                oSettings.aoPreSearchCols[ iCol ].sSearch = '';
            }
            oSettings.oPreviousSearch.sSearch = '';

            oTable.fnDraw();
        }

        //fnResetAllFilters();
    });

});
// ]]>
</script>
EOD;

        $this->addOutputCode( $html );
    }

    public function addColumn( $column )
    {
        if (preg_match("/\{i18n\:.*\}/i", $column['headerText']))
        {
            $code = preg_replace("/\{i18n\:(.*)\}/i", "$1", $column['headerText']);
            $column['headerText'] = org_glizy_locale_Locale::getPlain($code);
        }

        $this->columns[] = $column;
    }

    function process_ajax()
    {
        $aColumns = array();
        foreach( $this->columns as $column )
        {
            if ( !in_array( $column['columnName'], $aColumns)) {
                $aColumns[] = $column['columnName'];
            }
        }
        $sSearch = __Request::get('sSearch');
        $filters = array();

        $it = org_glizy_ObjectFactory::createModelIterator($this->getAttribute('recordClassName'));

        if ($it->getArType() === 'document') {
            $it->setOptions(array('type' => 'PUBLISHED_DRAFT'));
        }

        if ($this->getAttribute('setFiltersToQuery')) {
            for ( $i=0 ; $i < count($aColumns) ; $i++ ) {
                if (__Request::get('sSearch_'.$i)) {
                    $filters[$aColumns[$i]] =  __Request::get('sSearch_'.$i);
                }  else if ($sSearch != '' && __Request::get('bSearchable_'.$i) == "true" ) {
                    $filters[$aColumns[$i]] = $sSearch;
                }
            }
            $it->load($this->getAttribute('query'), array('filters' => $filters));

        } else  {

            $it->load($this->getAttribute('query'));

            if (method_exists($it, 'showAll')) {
                $it->showAll();
            }

            if ($this->getAttribute('fullTextSearch') && $sSearch) {
                $it->where('fulltext', '%'.$sSearch.'%', 'ILIKE');
            } else {
                for ( $i=0 ; $i < count($aColumns) ; $i++ ) {
                    if (__Request::get('sSearch_'.$i)) {
                        $filters[$aColumns[$i]] = array (
                            'value' => __Request::get('sSearch_'.$i),
                            'condition' => 'LIKE'
                        );
                    }
                    else if ($sSearch != '' && __Request::get('bSearchable_'.$i) == "true" ) {
                        $filters[$aColumns[$i]] = array (
                            'value' => '%'.$sSearch.'%',
                            'condition' => 'LIKE'
                        );
                    }
                }

                if (!empty($filters)) {
                    if ($this->getAttribute('queryOperator') === 'OR') {
                        $it->setOrFilters($filters);
                    } else {
                        $it->setFilters($filters);
                    }
                }
            }
        }

        // Ordering
        if ( __Request::exists('iSortCol_0') ) {
            $iSortingCols = intval( __Request::get( 'iSortingCols' ));
            for ( $i=0 ; $i<$iSortingCols ; $i++ ) {
                if ( __Request::get( 'bSortable_'.intval( __Request::get('iSortCol_'.$i) ) ) == "true" ) {
                    $order = $aColumns[ intval( __Request::get( 'iSortCol_'.$i ) ) ];
                    $order_dir = __Request::get('sSortDir_'.$i);
                    $it->orderBy($order, $order_dir);
                    break;
                }
            }
        }

        // Paging
        if ( __Request::get( 'iDisplayStart', -1 ) != -1 ) {
            $it->limit(array( __Request::get( 'iDisplayStart' ), __Request::get( 'iDisplayLength', -1 ) ));
        }

        $aaData = array();

        if ($this->getAttribute('dbDebug')) {
            org_glizy_dataAccessDoctrine_DataAccess::enableLogging();
        }

        try {
            foreach( $it as $row ) {
                $rowToInsert = array();

                foreach( $this->columns as $column ) {
                    if ( $column['acl'] ) {
                        if (!$this->_user->acl($column['acl']['service'], $column['acl']['action'])) {
                            continue;
                        }
                    }

                    $value = $row->$column['columnName'];
                    if ( $column['renderCell'] ) {
                        if ( !is_object( $column['renderCell'] ) ) {
                            $column['renderCell'] = org_glizy_ObjectFactory::createObject( $column['renderCell'], $this->_application );
                        }

                        if ( is_object($column['renderCell'])) {
                            $value = $column['renderCell']->renderCell( $row->getId(), $value, $row, $column['columnName'] );
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
            org_glizy_dataAccessDoctrine_DataAccess::disableLogging(); die;
        }

        $output = array(
                "sEcho" => intval(__Request::get('sEcho')),
                "iTotalRecords" => $it->count(),
                "iTotalDisplayRecords" => $it->count(),
                "aaData" => $aaData
        );

        return $output;
    }

    public static function compile($compiler, &$node, &$registredNameSpaces, &$counter, $parent='NULL', $idPrefix, $componentClassInfo, $componentId)
    {
        $compiler->_classSource .= '$n'.$counter.' = org_glizy_ObjectFactory::createComponent(\''.$componentClassInfo['classPath'].'\', $application, '.$parent.', \''.$node->nodeName.'\', '.$idPrefix.'\''.$componentId.'\', \''.$componentId.'\', $skipImport)'.GLZ_COMPILER_NEWLINE;

        if ($parent!='NULL')
        {
            $compiler->_classSource .= $parent.'->addChild($n'.$counter.')'.GLZ_COMPILER_NEWLINE;
        }

        if (count($node->attributes))
        {
            // compila  gli attributi
            $compiler->_classSource .= '$attributes = array(';
            foreach ( $node->attributes as $key=>$value )
            {
                if ($key!='id')
                {
                    $compiler->_classSource .= '\''.$key.'\' => \''.addslashes( $node->getAttribute( $key ) ).'\', ';
                }
            }
            $compiler->_classSource .= ')'.GLZ_COMPILER_NEWLINE;
            $compiler->_classSource .= '$n'.$counter.'->setAttributes( $attributes )'.GLZ_COMPILER_NEWLINE;
        }


        foreach ($node->childNodes as $n )
        {
            if ( strpos( $n->nodeName, ":DataGridColumn" ) !== false )
            {
                $params = array();
                $params['checkbox'] = $n->hasAttribute( 'checkbox' ) ? $n->getAttribute( 'checkbox' ) == 'true' : false;
                $params['sortable'] = $n->hasAttribute( 'sortable' ) ? $n->getAttribute( 'sortable' ) == 'true' : true;
                $params['searchable'] = $n->hasAttribute( 'searchable' ) ? $n->getAttribute( 'searchable' ) == 'true' : true;
                $params['visible'] = $n->hasAttribute( 'visible' ) ? $n->getAttribute( 'visible' ) == 'true' : true;
                $params['columnName'] = $n->hasAttribute( 'columnName' ) ? $n->getAttribute( 'columnName' ) : '';
                $params['headerText'] = $n->hasAttribute( 'headerText' ) ? $n->getAttribute( 'headerText' ) : '';
                $params['width'] = $n->hasAttribute( 'width' ) ? $n->getAttribute( 'width' ) : '';
                $params['acl'] = $n->hasAttribute( 'acl' ) ? $n->getAttribute( 'acl' ) : '';
                $params['cssClass'] = $n->hasAttribute( 'cssClass' ) ? $n->getAttribute( 'cssClass' ) : '';
                $params['renderCell'] = $n->hasAttribute( 'renderCell' ) ? $n->getAttribute( 'renderCell' ) : '';
                if ($params['acl']) {
                    list( $service, $action ) = explode( ',', $params['acl'] );
                    $params['acl'] = array('service' => $service, 'action' => $action);
                }
                $compiler->_classSource .= '$n'.$counter.'->addColumn('.var_export($params, true).');';
            }
        }
    }
}
