<?php

class metafad_workflow_processes_models_proxy_ProcessesProxy extends GlizyObject
{
    protected $application;

    function __construct()
    {
        $this->application = org_glizy_ObjectValues::get('org.glizy', 'application');
    }

    public function modify($id, $data)
    {
        if ($this->validate($data)) {

            $document = $this->createModel($id, 'metafad.workflow.processes.models.Model');

            foreach ($data as $key => $value) {
                $document->$key = $value;
            }

            try {
                return $document->publish(null, null, true);
            } catch (org_glizy_validators_ValidationException $e) {
                return $e->getErrors();
            }
        } else {
            // TODO
        }
    }

    public function validate($data)
    {
        return true;
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

    public function instanceActivityControll($activityId)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.workflow.processes.models.Model');
        $instanceActivitiesProxy = __ObjectFactory::createObject('metafad.workflow.instanceActivities.models.proxy.InstanceActivitiesProxy');

        $found = false;
        foreach ($it as $ar) {
            $instancesId = $ar->getRawData()->instanceActivities;
            foreach ($instancesId as $id) {
                if ($instanceActivitiesProxy->activityControll($id, $activityId)) {
                    $found = true;
                }
            }
        }

        return $found;
    }

}