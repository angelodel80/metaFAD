<?php
class metafad_common_views_components_ModalConfirm extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('label',     true,   '',         COMPONENT_TYPE_STRING);
        $this->defineAttribute('message',    false,    '',     COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $output = '<div class="modal fade" id="myModalConfirm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">' . $this->getAttribute('label') . '</h4>
      </div>
      <div class="modal-body">
        ' . $this->getAttribute('message') . '
      </div>
      <div class="modal-footer">
        <button type="button" class="annulla btn btn-default" data-dismiss="modal" >Annulla</button>
        <button type="button" class="ok btn btn-primary" data-dismiss="modal">Ok</button>
      </div>
    </div>
  </div>
</div>';
        
        $this->addOutputCode($output);
    }
}