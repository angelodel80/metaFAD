<?php
class metafad_common_views_components_DataGridSolrBase extends metafad_common_views_components_DataGridAjax
{
    protected $columns = array();
    protected $array_id = array();

    function init()
    {
        $this->defineAttribute('filterByInstitute', false, true, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('ignoreTypeFilter', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('enableSorting', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('keyAndLabel', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('autocompleteController', false, false, COMPONENT_TYPE_STRING);
        $this->defineAttribute('multiLanguage', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('massive', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('hasDigital', false, false, COMPONENT_TYPE_BOOLEAN);

        // call the superclass for validate the attributes
        parent::init();
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

    function getAdvancedFilter()
    {
        list($searchFields) = $this->getSearchFields();

        if (empty($searchFields)) {
            return '';
        }

        $advancedFilter =<<<EOD
<div class="simpleSearch"><button class="btn btn-info btn-flat btn-add advancedSearchButton" data-action="advancedSearch"><i class="advancedSearchIcon fa fa-search"></i>Ricerca avanzata</button></div>
    <div class="advancedSearch col-md-7 col-lg-7">
        <button class="btn btn-info btn-flat btn-add simpleSearchButton" data-action="simpleSearch" style="display:none">Ricerca semplice</button>
        <button class="btn btn-info btn-flat btn-add clearSearchButton" data-action="clearSearch" style="display:none">Pulisci ricerca</button>
        <div id="advancedSearchRepeaterContainer" class="">
            <div class="moreElement" id="moreElement_0" data-id="0">
                <div class="col-md-10 col-lg-10" id="search_0">
                    <div class="col-md-6 col-lg-6 select">
                        <select name="searchKey" class="searchKey form-control" label="Chiave">
EOD;

        if ($searchFields) {
            foreach ($searchFields as $label => $field) {
                if (is_array($field)) {
                    $type = $field['type'];
                    $label = $field['label'];
                    $fieldName = $field['field'];
                } else {
                    $type = 'autocomplete';
                    $fieldName = $field;
                }

                $label = $this->getAttribute('keyAndLabel') ? __T($label) : $label;

                $attributes = array(
                    'data-type' => $type,
                    'value' => $fieldName
                );
                
                if ($type == 'select') {
                    $attributes['data-options'] = str_replace('"', '##', json_encode($field['options']));
                }

                $advancedFilter .= org_glizy_helpers_Html::renderTag('option', $attributes, false, $label);
            }
        }
        $advancedFilter .=<<<EOD
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-6 searchValueContainer">
                        <input type="text" name="searchValue" class="searchValue form-control"/>
                    </div>
                </div>
            </div>  
            <div id="addFilterButton">
                <div class="col-md-7 col-lg-8"><a id="addFilter" name="addFilter" class="fa fa-plus btn btn-info btn-flat btn-add addFilter"><span>Aggiungi filtro</span></a></div>
                <div class="col-md-3 col-lg-2"><input type="button" id="searchButton" name="searchButton" class="btn btn-info btn-flat btn-add" value="Cerca"></div>
            </div>
        </div>
    </div>
</div>
EOD;
        return $advancedFilter;
    }

    function render_html()
    {
        $tableClass = $this->getAttribute("cssClass");
        $sortEnabled = $this->getAttribute("enableSorting");

        $id = $this->getId();
        $ajaxUrl = $this->getAjaxUrl();

        $colSpan = 0;
        $headers = '';
        $aoColumnDefs = array();

        foreach ($this->columns as $column) {
            if ($column['acl']) {
                if (!$this->_user->acl($column['acl']['service'], $column['acl']['action'])) {
                    continue;
                }
            }

            $colSpan++;

            $attributes = array();

            if (!$column['visible']) {
                $attributes['style'] = 'display:none;';
            }

            if ($column['width']) {
                $attributes['width'] = $column['width'];
            }

            $content = $column['headerText'];
            if ($column['checkbox']) {
                $content .= ' <input type="checkbox" class="filterSearchCheckbox" name="' . $column['columnName'] . '">';
            }

            if(!$selector)
            {
                if ($column['checkboxSelectAll'] && __Config::get('metafad.selectAll')) {
                    $selector = ' <a class="select-items js-result-select-all" name="' . $column['columnName'] . '"><i class="fa fa-square-o text-dark-gray"></i> Seleziona Tutto</a>';
                }
                if ($column['checkboxSelectPage'] && __Config::get('metafad.selectAll')) {
                    $selector .= ' <a class="select-items js-result-select-page" name="' . $column['columnName'] . '"><i class="fa fa-square-o text-dark-gray"></i> Seleziona pagina</a>';
                }
            }

            $headers .= org_glizy_helpers_Html::renderTag('th', $attributes, true, $content);

            //POLODEBUG-461, si vogliono disattivare i controlli di ordinamento
            $aoColumnDefs[] = array(
                "bSortable" => $sortEnabled && ($column['sortable'] || $column['orderable']), //<-- Son sinonimi
                "bSearchable" => $column['searchable'],
                "aTargets" => array($colSpan - 1),
                "sType" => "html",
                "sClass" => $column['cssClass']
            );
        }

        $aoColumnDefs = json_encode($aoColumnDefs);

        $advancedFilter = $this->getAdvancedFilter();

        if (!org_glizy_ObjectValues::get('jquery.dataTables', 'add', false)) {
            org_glizy_ObjectValues::set('jquery.dataTables', 'add', true);
            $staticDir = org_glizy_Paths::get('STATIC_DIR');
            $html = '<script type="text/javascript" src="' . $staticDir . '/jquery/datatables/media/js/jquery.dataTables.min.js"></script>';
            $html .= '<script type=""text/javascript" src="' . $staticDir . '/jquery/datatables/media/js/jquery.dataTables.custom.js"></script>';
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
        $aLengthMenu = (__Config::get('datagrid.lengthMenu')) ?:'[10, 25, 50, 75, 100]';
        $JQueryUI = $this->getAttribute('JQueryUI') ? 'true' : 'false';
        $recordClassName = $this->getAttribute('recordClassName');
        $autocompleteController = $this->getAttribute('autocompleteController');
        $selectAllController = 'metafad.common.controllers.ajax.SelectAll';

        $massive = $this->getAttribute('massive');
        $hasDigital = $this->getAttribute('hasDigital');
        $parent = $this->getAttribute('parent');

        $filterInstitute = ''; 
        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        if ($this->getAttribute('filterByInstitute') && $instituteKey && $instituteKey != "*") {
            $filterInstitute = $instituteKey;
        }

        $advancedFilter = str_replace(array("'", "\n", "\r"), array("\'", '', ''), $advancedFilter);

        $html .= <<<EOD
        <table class="$tableClass" id="$id">
            <thead>
                <tr>
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
    var allValue = ((localStorage['$recordClassName-lastAdvancedSearch']) ? JSON.parse(localStorage['$recordClassName-lastAdvancedSearch']) : new Array());
    var oldAllValue = new Array();
    var table = \$('#$id').dataTable( {
        "sDom": "<'row-fluid filter-row clearfix'<'filter-box'f>r><'table-container't><'row-fluid clearfix'<'select-record-section'><'filter-box select-page-length'l><'filter-box pull-left'i><'filter-box pull-right'p>>",
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
        "bLengthChange": true,
        "aLengthMenu": $aLengthMenu,
        "bJQueryUI": $JQueryUI,
        "bServerSide": true,
        "sAjaxSource": "$ajaxUrl",
        "fnServerParams": function ( aoData ) {
            oldAllValue.forEach(function(oldSearchParam){
                aoData.splice(aoData.indexOf(oldSearchParam, 1));
            });
            allValue.forEach(function(searchParam){
                aoData.push(searchParam);
            });
        },
        "aoColumnDefs": $aoColumnDefs,
        "bStateSave": true,
        "fnStateSave": function (oSettings, oData) {
            localStorage.setItem( "$cookieName", JSON.stringify(oData) );
        },
        "fnStateLoad": function (oSettings) {
            return JSON.parse( localStorage.getItem("$cookieName") );
        },
        "fnFormatNumber": function ( iIn ) {
            return iIn;
        }
    });

    $('<i class="fa fa-search searchIcon"></i>').appendTo("#{$id}_filter > label:first-child");

    $('$advancedFilter').appendTo("#{$id}_wrapper > div.filter-row");

    $(document).ready( function () {
        $('#$id').data('dataTable', table);

        $('.select-record-section').html('$selector');

        $('#$id').on('draw',function(){
            var tocheck = false;
            $('.selectionflag').each(function(){
                if($(this).prop('checked'))
                {
                    tocheck = true;
                }
            });
            if(tocheck)
            { 
                $('.js-result-select-page').data('checked','checked');
                $('.js-result-select-page').children('.fa').removeClass('fa-square-o').addClass('fa-check-square');
            }
            else
            {
                $('.js-result-select-page').data('checked',false);
                $('.js-result-select-page').children('.fa').addClass('fa-square-o').removeClass('fa-check-square');
            }
        }); 

        allValue.push({"name": "advancedSearch", "value": "false"});

        $('body').on('click', 'button[data-action=simpleSearch]', function () {
            fnResetAllFilters();

            $("div[id^='search_'] .searchValue").val("");
            $('.trashButton').trigger("click");
            $('.advancedSearch #searchButton').trigger("click");

            $('.advancedSearch').hide();
            $('.simpleSearchButton').hide();
            $('.advancedSearchButton').show();
            $('.filter-box').show();
            $('#{$id}_filter').show();
            $('.searchIcon').show();
            allValue.push({"name": "advancedSearch", "value": "false"});

            $('#{$id}_filter select').each(function() {
                $(this).trigger('change');
            });
        });

        $('body').on('click', 'button[data-action=clearSearch]', function() {
            $("div[id^='search_'] .searchValue").val("");
            $('.trashButton').trigger("click");
            $('.advancedSearch #searchButton').trigger("click");
            allValue.push({"name": "advancedSearch", "value": "true"});
        });

        $('body').on('click', 'button[data-action=advancedSearch]', function () {
            fnResetAllFilters();

            $('#{$id}_filter input').val("");
            $('#{$id}_filter').hide();
            $('.advancedSearchButton').hide();
            $('.simpleSearchButton').show();
            $('.clearSearchButton').show();
            $('.advancedSearch').show();
            $('.searchIcon').hide();
            allValue.push({"name": "advancedSearch", "value": "true"});

            $('select[name="searchKey"]').trigger('change');
        });
        
        function checkSelector(checked, cssclass ) 
        {
            if(!checked)
            {
                $(cssclass).data('checked',true);
                $(cssclass).children('.fa').removeClass('fa-square-o').addClass('fa-check-square');
            }
            else
            {
                $(cssclass).data('checked',false);
                $(cssclass).children('.fa').addClass('fa-square-o').removeClass('fa-check-square');
            }
        }

        $('body').on('click', '.js-result-select-all', function (e) {
            e.stopPropagation();
            e.preventDefault();
            var checked = $(this).data('checked');
            checkSelector(checked,'.js-result-select-all');
            checkSelector(checked,'.js-result-select-page');
            
            var checked = $(this).data('checked');
            if (checked) {
                $.ajax({
                url: Glizy.ajaxUrl+'&controllerName=$selectAllController',
                data: {
                    instituteKey: '$filterInstitute',
                    model: '$recordClassName',
                    filters: getAllValues('#advancedSearchRepeaterContainer div.moreElement', null),
                    massive: '$massive',
                    hasDigital : '$hasDigital',
                },
                success: function( data ) {
                    arrayLength = data.length;
                    for (var i = 0; i < arrayLength; i++) {
                        ids.push(data[i]);
                    }

                    $('.selectionflag').prop('checked', 'checked');
                    if ($('#__selectedIds').length > 0) 
                    {
                        $('#__selectedIds').val(ids.join());
                    }
                    if ($('#ids').length > 0) 
                    {
                        $('#ids').val(ids.join());
                    }
                },
            });
            }
            else {
                $('.selectionflag').prop('checked',false);
                $('#__selectedIds').val('');
                $('#ids').val('');
                ids = [];
            }
            
        });

        $('body').on('click', '.js-result-select-page', function (e) {
            e.stopPropagation();
            e.preventDefault();
            var checked = $(this).data('checked');
            var checked = $(this).data('checked');
            checkSelector(checked,'.js-result-select-page');
            
            var checked = $(this).data('checked');

            if (checked) {
                $('.selectionflag').prop('checked', 'checked');
            }
            else {
                $('.selectionflag').prop('checked',false);
            }
            $('.selectionflag').trigger('change');
        });

        function addTrashButton(count)
        {
            var trashButton = '<div class="trashButtonDiv"><a id="trashButton_' + count + '" name="trashButton" class="trashButton fa fa-trash btn btn-info btn-flat btn-add"></div>';
            $(trashButton).appendTo("#moreElement_" + count);
            $("#trashButton_" + count).on("click", function() {
                var id = (this.id).replace("trashButton", "");
                $("#moreElement" + id).remove();
                
                if ($('#advancedSearchRepeaterContainer div.moreElement').length == 1) {
                    $('#advancedSearchRepeaterContainer div.trashButtonDiv').remove();
                }
            })
        }

        $('#addFilter').click( function() {
            var count = $('#advancedSearchRepeaterContainer div.moreElement:last').data('id');
            var count1 = count + 1;

            if ($('#advancedSearchRepeaterContainer div.moreElement').length == 1) {
                addTrashButton(count);
            }

            var moreElement = '<div class="moreElement" id="moreElement_' + count1 + '" data-id="' + count1 + '"></div>';
            $(moreElement).appendTo('#advancedSearchRepeaterContainer');
            
            var elementCloned = $('#search_' + count).clone();
            elementCloned.appendTo('#moreElement_' + count1 ).attr('id', 'search_' + count1);
            
            $("#search_" + count1 + " .searchValue").val("");
            
            addTrashButton(count1);
            
            $("#addFilterButton").insertAfter('#moreElement_' + count1);

            $("#search_" + count1 + " .searchKey").trigger('change');
            
            count++;
        })

        $('body').on('change','select[name="searchKey"]', function() {
            var container = $(this).closest('.moreElement');
            var inputContainer = container.find('.searchValueContainer');
            var type = $(this).find('option:selected').data('type');
 
            if (type == 'autocomplete') {
                inputContainer.replaceWith(`
                    <div class="col-md-6 col-lg-6 searchValueContainer">
                        <input type="text" name="searchValue" class="searchValue form-control"/>
                    <div>
                `);

                var fieldName = container.find('select.searchKey').val();

                container.find('input.searchValue').autocomplete({
                    minLength: 0,
                    source: function( request, response ) {
                        $.ajax({
                            url: Glizy.ajaxUrl+'&controllerName=$autocompleteController',
                            data: {
                                instituteKey: '$filterInstitute',
                                model: '$recordClassName',
                                filters: getAllValues('#advancedSearchRepeaterContainer div.moreElement', fieldName),
                                fieldName: fieldName,
                                term: request.term,
                                hasDigital: '$hasDigital',
                                parent: '$parent',
                            },
                            success: function( data ) {
                                if (data.length == 0) {
                                    data = ['$sEmptyTable'];
                                }
                                response( data );
                            },
                        });
                    }
                });
            }

            if (type == 'select') {
                inputContainer.replaceWith(`
                    <div class="col-md-6 col-lg-6 searchValueContainer select">
                        <select name="searchValue" class="searchValue form-control"/>
                    </div>
                `);
                
                var select = container.find('select.searchValue');

                var options = $(this).find(':selected').data('options').replace(/##/g,'"');
                var options = JSON.parse(options);
                
                $.each(options, function(k, v) {
                    select.append('<option value="'+k+'">'+v+'</option>');
                });
            }

            if (type == 'checkbox') {
                inputContainer.replaceWith(`
                    <div class="col-md-6 col-lg-6 searchValueContainer">
                        <input type="checkbox" name="searchValue" class="searchValue"/>
                    </div>
                `);
            }

            if (type == 'text') {
                inputContainer.replaceWith(`
                    <div class="col-md-6 col-lg-6 searchValueContainer">
                        <input name="searchValue" class="searchValue form-control"/>
                    </div>
                `);
            }

            if (type == 'date' || type == 'dateCentury') {
                inputContainer.replaceWith(`
                    <div class="col-md-6 col-lg-6 searchValueContainer">
                        <div class="col-md-6 col-lg-6">
                            <label>da</label>
                            <input type="text" name="searchValue" class="searchValue form-control" />
                        </div>
                        <div class="col-md-6 col-lg-6">
                            <label>a</label>
                            <input type="text" name="searchValue" class="searchValue form-control" />
                        </div>
                    </div>
                `);
            }
        });

        $('body').on('click', 'input[name="searchValue"]',function(){
            var container = $(this).closest('.moreElement');
            var type = container.find('option:selected').data('type');

            if (type == 'autocomplete') {
                $(this).autocomplete('search','');
            }
        });

        $('#{$id}_filter input').attr('class', 'form-control').attr('placeholder', 'Filtra elenco...').val('');

        var oTable = $('#$id').DataTable();

        
        $('#{$id}_filter input').val(oTable.fnSettings().oPreviousSearch.sSearch);
        function fnResetAllFilters() {
            var oSettings = oTable.fnSettings();
            for(iCol = 0; iCol < oSettings.aoPreSearchCols.length; iCol++) {
                oSettings.aoPreSearchCols[ iCol ].sSearch = '';
            }
            oSettings.oPreviousSearch.sSearch = '';

            oTable.fnDraw();
        }

        $('#searchButton').click( search );
        $('.filterSearchCheckbox').change( search );

        function getAllValues(selector, fieldNameToExclude) {
            var allValues = [];

            $(selector).each(function(index) {
                var type = $(this).find('option:selected').data('type');
                var name = $(this).find('select.searchKey').val();
                
                if (fieldNameToExclude && name == fieldNameToExclude) {
                    return;
                }
                
                var value = '';

                if (type == 'date' || type == 'dateCentury') {
                    var input = $(this).find('input.searchValue');
                    value = [$(input[0]).val(), $(input[1]).val()];
                } else if (type == 'checkbox') {
                    value = $(this).find('input.searchValue').is(':checked') ? 1 : 0;
                } else if (type == 'select') {
                    value = $(this).find('select.searchValue').val();
                } else {
                    value = $(this).find('input.searchValue').val();
                }

                if (value) {
                    allValues.push({"type": type, "name": name, "value": value});
                }
            });

            return allValues;
        }

        function search() {
            var index = 0;
                        
            if (allValue.length){
                oldAllValue = allValue;
            }

            allValue = getAllValues('#advancedSearchRepeaterContainer div.moreElement');
            $('.filterSearchCheckbox').each(function() {
                allValue.push({"name": this.name, "value": this.checked ? 1 : 0});
            });

            localStorage['$recordClassName-lastAdvancedSearch'] = JSON.stringify(allValue);

            oTable.fnDraw();
        }

        if(localStorage['$recordClassName-lastAdvancedSearch'])
        {
            $('.advancedSearchButton').trigger('click');
            var lastSearchValue = JSON.parse(localStorage['$recordClassName-lastAdvancedSearch']);
            $.each(lastSearchValue, function(k, v) {
                $('select[name="searchKey"]').last().val(v.name);
                $('input[name="searchValue"]').last().val(v.value);
                $('#addFilter').trigger('click');
            });
            $('.trashButton').last().trigger("click");
        }
    });
});

// ]]>
</script>
EOD;

        $this->addOutputCode($html);
    }

    public function addColumn($column)
    {
        if (preg_match("/\{i18n\:.*\}/i", $column['headerText'])) {
            $code = preg_replace("/\{i18n\:(.*)\}/i", "$1", $column['headerText']);
            $column['headerText'] = org_glizy_locale_Locale::getPlain($code);
        }

        $this->columns[] = $column;
    }

    protected function getSearchFields($includeCheckbox=false)
    {
        $labels = array();
        $recordClassName = $this->getAttribute('recordClassName');
        if (strpos(',', $recordClassName) !== false) {
            $document = org_glizy_objectFactory::createModel($recordClassName);
            $searchFields = $document->getBeAdvancedSearchFields();
            $query = 'document_type_t:"' . $this->getAttribute('recordClassName') . '"';
        } else {
            $searchFields = array();
            foreach (explode(',', $recordClassName) as $model) {
                $document = org_glizy_objectFactory::createModel($model);
                $recordSeachFields = $document->getBeAdvancedSearchFields();
                foreach($recordSeachFields as $k => $v)
                {
                    if(is_array($v))
                    {
                        $label = $v['label'];
                    }
                    else
                    {
                        $label = $v;
                    }
                    if(!in_array($l,$labels))
                    {
                        $labels[] = $label;
                        $searchFields[$k] = $v;
                    }
                }
                $modelQuery[] = 'document_type_t:"' . $model . '"';
            }
            $query = '('.implode(' OR ', $modelQuery).')';
        }

        if ($includeCheckbox) {
            foreach ($this->columns as $column) {
                if ($column['checkbox']) {
                    $searchFields[$column['columnName']] = $column['columnName'];
                }
            }
        }

        return array($searchFields, $query);
    }
}
