<?php
class metafad_gestioneDati_boards_views_components_AddRecord extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label',     true,   '',         COMPONENT_TYPE_STRING);
        $this->defineAttribute('routeUrl',    false,    '',     COMPONENT_TYPE_STRING);
        $this->defineAttribute('add', false, 'Nuova scheda', COMPONENT_TYPE_STRING);
        $this->defineAttribute('dropdown', false, true, COMPONENT_TYPE_BOOLEAN);


        parent::init();
    }

    function render()
    {
        $routeUrl = $this->getAttribute('routeUrl');
        $output = '<div id="dataGridAddRow">
                    <table class="table table-bordered table-striped dataTable"><tbody>
                    <tr aria-controls="dataGrid2" class="odd">
                      <td class="col-lg-3 col-md-3 col-sm-3">
                        <input id="js-new-value" name="iccd_theasaurus_value" class="form-control thesaurus_value" type="text" />
                      </td>
                      <td class="col-lg-3 col-md-3 col-sm-3">
                        <input id="js-new-key" name="iccd_theasaurus_key" class="form-control" type="text"/>
                      </td>
                      <td id="js-new-level" class="buttons-level col-lg-2 col-md-2 col-sm-2">
                        <span class="level selected"><input type="button" data-id="0" style="border:none; padding: 0px; background:none; box-shadow: none;" value="1" onclick="clickLevelNew($(this));"></span>
                        <span class="level"><input type="button" data-id="0" style="border:none; padding: 0px; background:none; box-shadow: none;" value="2" onclick="clickLevelNew($(this));"></span>
                        <span class="level"><input type="button" data-id="0" style="border:none; padding: 0px; background:none; box-shadow: none;" value="3" onclick="clickLevelNew($(this));"></span>
                        <span class="level"><input type="button" data-id="0" style="border:none; padding: 0px; background:none; box-shadow: none;" value="4" onclick="clickLevelNew($(this));"></span>
                        <span class="level"><input type="button" data-id="0" style="border:none; padding: 0px; background:none; box-shadow: none;" value="5" onclick="clickLevelNew($(this));"></span>
                      </td>
                      <td class="col-lg-4 col-md-4 col-sm-4">
                        <div class="thesaurusParent" id="thesaurusParent-0" data-key="0" data-val=""></div>
                      </td>
                      <td class="actions">
                        <a href="#" class="js-add-row" data-id="0" title="Aggiungi">
                          <i class="btn btn-info btn-flat fa fa-plus"></i>
                        </a>
                      </td>
                    </tr>
                    </tbody></table>
                  </div>';
        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $output .= <<<EOD
<script type="text/javascript">
    jQuery(function(){
        var table = jQuery('#$dataGridId').data('dataTable');
        setTimeout(function(){
            jQuery('#dataGridAddRow').insertBefore("#{$dataGridId}");
            
        }, 100);
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}
