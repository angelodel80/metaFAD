<?php
class metafad_modules_importerReport_controllers_ajax_UpdateReport extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
        return array('sendOutput' => 'report', 'sendOutputState' => 'index', 'sendOutputFormat' => 'html' );
    }
}
