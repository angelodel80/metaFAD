<?php
class metafad_teca_MAG_views_components_DataGridSolr extends metafad_common_views_components_DataGridSolr
{
    function render_html()
    {
        $tableClass = $this->getAttribute("cssClass");
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
            $headers .= '<th';
            if (!$column['visible']) $headers .= ' style="display:none;"';
            if ($column['width']) $headers .= ' width="' . $column['width'] . '%"';
            $headers .= '>' . $column['headerText'] . '</th>';

            $document = org_glizy_objectFactory::createModel($this->getAttribute('recordClassName'));
            $searchFields = $document->getBeAdvancedSearchFields();

            $advancedFilter = '
            <link rel="stylesheet" type="text/css" media="all" href="./core/classes/org/glizycms/js/jquery/jquery.pnotify/jquery.pnotify.default.css" />
                <div class="simpleSearch col-sm-1"><button class="btn btn-info btn-flat btn-add advancedSearchButton" data-action="advancedSearch"><i class="advancedSearchIcon fa fa-search"></i>Ricerca avanzata</button></div>
                <div class="advancedSearch col-md-10 col-lg-10" style="display:none; padding: 0px;">
                    <button class="btn btn-info btn-flat btn-add simpleSearchButton" data-action="simpleSearch" style="display:none">Ricerca semplice</button>
                    <div id="advancedSearchRepeaterContainer">';

                    if ($searchFields) {
                      $count = 0;
                      foreach ($searchFields as $key => $value) {
                        $advancedFilter .= '
                        <div class="moreElement" id="moreElement_'.$count.'">
                        <div class="col-md-10 col-lg-10" id="search_'.$count.'">
                              <div class="col-md-2 col-lg-2">
                                  <div name="searchKey" class="searchKey" data-value="'.$value.'">'.__T($key).'</div>
                              </div>';
                              if($key == 'update_at')
                              {
                                $advancedFilter .= '<div class="col-md-5 col-lg-5">
                                    <input type="text" name="searchValue" placeholder="&#xf073;" style="font-family:Arial, FontAwesome" class="searchValue form-control" />
                                </div>
                                <div class="col-md-5 col-lg-5">
                                    <input type="text" name="searchValue" placeholder="&#xf073;" style="font-family:Arial, FontAwesome" class="searchValue form-control" />
                                </div>';
                              }
                              else {
                                $advancedFilter .= '<div class="col-md-10 col-lg-10">
                                    <input type="text" name="searchValue" class="searchValue form-control" />
                                </div>';
                              }

                              if($count == count($key)+1)
                              {
                                $advancedFilter .= '
                                <div id="addFilterButton">
                                    <div class="col-md-12 col-lg-12"><input type="button" id="searchButton" name="searchButton" class="btn btn-info btn-flat btn-add" value="Cerca"></div>
                                </div>';
                              }
                            $advancedFilter .= '</div>';

                        $advancedFilter .= '</div>';
                        $count++;
                      }

                    }


                $advancedFilter .= '</div></div>';


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
        $JQueryUI = $this->getAttribute('JQueryUI') ? 'true' : 'false';

        $advancedFilter = str_replace(array("'", "\n", "\r"), array("\'", '', ''), $advancedFilter);

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
    var allValue = new Array();
    var oldAllValue = new Array();
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
        "bLengthChange": true,
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
    } );

        $('<i class="fa fa-search searchIcon"></i>').appendTo("#{$id}_filter > label:first-child");

        $('$advancedFilter').appendTo("#{$id}_wrapper > div.filter-row");

        function select2gen(event) {
            \$searchField = event.data.searchField;
            if($("option:selected", this).attr('data-proxy')){
                var fieldName = $(this).attr('name');
                var model = '';
                var query = '';
                var proxy = $("option:selected", this).attr('data-proxy');
                var proxyParams = $("option:selected", this).attr('data-proxy_params');
                if (proxyParams) {
                    proxyParams = proxyParams.replace(/##/g,'"');
                }
                var getId = '';

                $(\$searchField + " .searchValue").select2({
                      ajax: {
                        url: Glizy.ajaxUrl+"ajax.php?pageId=gestione-dati-archivi&ajaxTarget=Page&action=&controllerName=org.glizycms.contents.controllers.autocomplete.ajax.FindTerm&fieldName=parent&term=&proxy=metafad.gestioneDati.archivi.models.ModelProxy",
                        dataType: 'json',
                        delay: 250,
                        data: function(term, page) {
                                return {
                                    fieldName: fieldName,
                                    model: model,
                                    query: query,
                                    term: term,
                                    proxy: proxy,
                                    proxyParams: proxyParams,
                                    getId: getId
                                };
                            },
                            results: function(data, page ) {
                                return { results: data.result }
                            }
                        },
                      escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                });
            } else {
                $(\$searchField + " .select2-container.searchValue.form-control").remove();
                $(\$searchField + " .searchValue").show();
            }
        }

    \$('#$id').data('dataTable', table);
    allValue.push({"name": "advancedSearch", "value": "false"});

    \$('body').on('click', 'button[data-action=simpleSearch]', function () {
            $('.advancedSearch').hide();
            $('.simpleSearchButton').hide();
            $('.advancedSearchButton').show();
            $('.filter-box').show();
            $('#dataGrid_filter').show();
            $('.searchIcon').show();
            allValue.push({"name": "advancedSearch", "value": "false"});
        });

    \$('body').on('click', 'button[data-action=advancedSearch]', function () {
            $('#dataGrid_filter').hide();
            $('.advancedSearchButton').hide();
            $('.simpleSearchButton').show();
            $('.advancedSearch').show();
            $('.searchIcon').hide();
            allValue.push({"name": "advancedSearch", "value": "true"});
        });

    var count = 0;

    $(document).ready( function () {
        \$('#search_0 .searchKey').on( "change", {"searchField" : "#search_0"}, select2gen );

        $('#dataGrid_filter input').attr('class', 'form-control').attr('placeholder', 'Filtra elenco...').val('');

        var oTable = $('#$id').DataTable();

        function fnResetAllFilters() {
                var oSettings = oTable.fnSettings();
                for(iCol = 0; iCol < oSettings.aoPreSearchCols.length; iCol++) {
                    oSettings.aoPreSearchCols[ iCol ].sSearch = '';
                }
                oSettings.oPreviousSearch.sSearch = '';

                oTable.fnDraw();
        }
        fnResetAllFilters();

        $('#searchButton').click( function() {
            var index = 0;
            if(allValue.length){
                oldAllValue = allValue;
            }
            allValue = new Array();

            \$('.moreElement').each(function(index){
              if($("#" + this.id + " input.searchValue").val())
              {
                console.log({"name": \$("#" + this.id + " div.searchKey").data('value'), "value": \$("#" + this.id + " input.searchValue").val()});
                allValue.push({"name": \$("#" + this.id + " div.searchKey").data('value'), "value": \$("#" + this.id + " input.searchValue").val()});
              }
            });
            oTable.fnDraw();
        });
    });
});

// ]]>
</script>
EOD;

        $this->addOutputCode($html);
    }
}
