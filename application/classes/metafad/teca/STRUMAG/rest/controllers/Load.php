<?php
class metafad_teca_STRUMAG_rest_controllers_Load extends org_glizy_rest_core_CommandRest
{
    function execute($id)
    {
        $this->checkPermissionForBackend();
        
        if ($id) {
            $ar = __objectFactory::createModel('metafad.teca.STRUMAG.models.Model');
            if ($ar->load($id)) {
                $vo = __objectFactory::createObject('metafad_teca_STRUMAG_models_vo_STRUMAGVO', $ar);
                return $vo;
            } else {
                return array(
                    'http-status' => 404,
                    'message' => 'Not found'
                );
            }
        } else {
            return array(
                'http-status' => 400,
                'message' => 'Invalid request: missing required parameters'
            );
        }
    }
}
