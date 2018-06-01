<?php
class metafad_opac_controllers_ajax_SaveDraft extends metafad_opac_controllers_ajax_Save
{
    public function execute($data)
    {
        return parent::execute($data, true);
    }
}
