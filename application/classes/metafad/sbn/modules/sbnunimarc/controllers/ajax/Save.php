<?php
class metafad_sbn_modules_sbnunimarc_controllers_ajax_Save extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data, $draft=false)
    {
        $result = $this->checkPermissionForBackend('publish');
        if (is_array($result)) {
            return $result;
        }

		$saveService = org_glizy_objectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.SaveService');
		$id = $saveService->save($data,true);

        $this->directOutput = true;
        return array('set' => array('bid' => $id));
    }
}
