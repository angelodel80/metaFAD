<?php
class metafad_teca_MAG_controllers_ajax_RefreshLinkedImages extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
        return array('sendOutput' => 'linkedImages', 'sendOutputState' => 'edit', 'sendOutputFormat' => 'html');
    }
}
