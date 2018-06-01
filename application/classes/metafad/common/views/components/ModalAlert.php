<?php

class metafad_common_views_components_ModalAlert extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('message', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('id', true, 'myModalAlert', COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $message = $this->getAttribute('message');
        if ($message == 'importICCD') {
            $importOutput = '<input id="importFile" name="importFile" title="File excel" class="form-control" type="file" size="90" value="" accept=".csv,.xls,.xlsx">';
            $importOutput .= '<select id="importType" style="margin-top: 5px;" class="form-control">';
            $importOutput .= '<option value="import1">Import (standard MWFAD)</option>';
            $importOutput .= '<option value="import2" selected>Import Gerarchica</option>';
            $importOutput .= '<option value="import3">Import Alfabetica</option>';
            $importOutput .= '</select>';
            $importOutput .= '<input type="checkbox" name="importFile" value="cancellaTutti" id="CancellaTutti"><span class="checkImporter">Cancella i record presenti</span><br>
                              <input type="checkbox" name="importFile" value="sostituisciKeyUguali" id="SostituisciRecord"><span class="checkImporter">Sostituisci i record con la stessa chiave</span><br>';

            $addButton = '<input id="import" class="btn btn-primary js-glizycms-file" type="button" value="Importa" data-action="import">';
        }
        else if ($message == 'massiveImportICCD') {
            $importOutput = '<input id="importFile" name="importFile" title="File zip o rar" class="form-control" type="file" size="90" value="" accept=".zip,.rar">';
            $importOutput .= '<select id="importType" style="margin-top: 5px;" class="form-control">';
            $importOutput .= '<option value="import1">Import (standard MWFAD)</option>';
            $importOutput .= '<option value="import2">Import Gerarchica</option>';
            $importOutput .= '<option value="import3">Import Alfabetica</option>';
            $importOutput .= '<option value="import4" selected>Import automatico</option>';
            $importOutput .= '</select>';
            $importOutput .= '<input type="checkbox" name="importFile" value="cancellaTutti" id="CancellaTutti"><span class="checkImporter">Cancella i record presenti</span><br>
                              <input type="checkbox" name="importFile" value="sostituisciKeyUguali" id="SostituisciRecord"><span class="checkImporter">Sostituisci i record con la stessa chiave</span><br>';

            $addButton = '<input id="importMassive" class="btn btn-primary js-glizycms-importMassive" type="button" value="Importa" data-action="import">';
        }
        else {
            $importOutput = $message;
        }

        $output = '<div class="modal fade" id="' . $this->getAttribute('id') . '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel-'.$this->getAttribute('id').'">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel'.$this->getAttribute('id').'">' . $this->getAttribute('label') . '</h4>
      </div>
      <div class="modal-body">
        ' . $importOutput . '
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Annulla</button>
        ' . $addButton . '
      </div>
    </div>
  </div>
</div>';

        $this->addOutputCode($output);
    }
}
