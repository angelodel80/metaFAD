<?php
class archivi_views_components_SortButton extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label', true, '', COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $label = $this->getAttribute('label');
        $dataGridId = $this->getAttribute('dataGridAjaxId');
        $id = __Request::get('id');

        $output =<<<EOD
<div id="dataGridAddButton">
    <a id="primaryButton" class="btn btn-info btn-flat btn-add" href="#">Ordinamento</a>
    <a id="reorderButton" class="btn btn-info btn-flat btn-add" href="#">$label</a>
</div>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">Riordina</h5>
      </div>
      <div class="modal-body">
        <select id="order">
            <option value="data-discendente">Per data con ordinamento cronologico discendente</option>
            <option value="data-ascendente">Per data con ordinamento cronologico ascendente</option>
            <option value="segnaturaAttuale">Per segnatura attuale con ordinamento alfabetico naturale</option>
            <option value="segnaturaPrecedente">Per segnature precedenti con ordinamento alfabetico naturale</option>
            <option value="codiceDiClassificazione">Per codice di classificazione con ordinamento alfabetico naturale</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
        <button id="preview-button" type="button" class="btn btn-primary">Visualizza in anteprima</button>
      </div>
    </div>
  </div>
</div>
EOD;
        $output .= <<<EOD
<script type="text/javascript">
    $(function(){
        $('#primaryButton').on('click', function () {
            $('#myModal').modal('show');
        });

        $('#reorderButton').on('click', function () {
            $('#reorderButton').attr('disabled', 'disabled');

            Glizy.startAjaxSteps({
                id: $id,
                type: $('#order').val()
            }, reorderFinished);

            function reorderFinished() {
                $('#reorderButton').removeAttr('disabled');
                var table = $('#$dataGridId').data('dataTable');
                table.fnDraw();
            }
        });

        $('#preview-button').on('click', function () {
            $('#myModal').modal('hide');
            
            var sortMap = {
                'data-discendente' : [3, 'desc'],
                'data-ascendente' : [3, 'asc'],
                'segnaturaAttuale' : [5, 'asc'],
                'segnaturaPrecedente' : [6, 'asc'],
                'codiceDiClassificazione' : [7, 'asc']

            }
            
            var sort = sortMap[ $('#order').val() ];
            
            var table = $('#$dataGridId').data('dataTable');
            table.fnSort( [ sort ] );
        });
    
        setTimeout(function(){
            $('#dataGridAddButton').prependTo("#{$dataGridId}_wrapper .filter-row");
        }, 100);
    });
</script>
EOD;
        $this->addOutputCode($output);
    }
}