<?php
class metafad_teca_MAG_controllers_ajax_SetRelationType extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($type)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
        __Session::set('relationType',$type);
    }
}
