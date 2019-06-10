<?php
class archivi_controllers_ToggleFEVisibility extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('edit');

        $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
        $jobFactory->createJob(
            'archivi_services_VisibilityService',
            array(
                'id' => $id,
                'model' => __Request::get('model')
            ),
            'Cambio visibilità',
            'SYSTEM'
        );

        return array('url' => $this->changePage('link', array('pageId' => 'metafad.modules.importerreport')));
    }
}