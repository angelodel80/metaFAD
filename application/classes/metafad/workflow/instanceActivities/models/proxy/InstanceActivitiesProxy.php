<?php

class metafad_workflow_instanceActivities_models_proxy_InstanceActivitiesProxy extends GlizyObject
{
    protected $application;

    function __construct()
    {
        $this->application = org_glizy_ObjectValues::get('org.glizy', 'application');
    }

    public function modify($id, $data, $forceNew = false)
    {
        if ($this->validate($data)) {

            $document = $this->createModel($id, 'metafad.workflow.instanceActivities.models.Model');

            foreach ($data as $key => $value) {
                if($key == 'activity'){
                    $value->title = $value->text;
                    unset($value->text);
                }
                if($key == 'notes'){
                    if($document->notes){
                        $notes = $document->notes;
                    }
                    else{
                        $notes = array();
                    }
                    $document->notes = array_merge($notes, $value);
                }
                else{
                    $document->$key = $value;
                }
            }
            

            try {
                return $document->publish(null, null, $forceNew);
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

    public function activityControll($instanceActivities, $activityId)
    {
        $document = $this->createModel($instanceActivities, 'metafad.workflow.instanceActivities.models.Model');

        $activitiesId = $document->getRawData()->activity;

        if ($activitiesId->id == $activityId) {
            return true;
        }

        return false;
    }
    
    public function addNote($instanceActivityId, $note)
    {
        $document = $this->createModel($instanceActivityId, 'metafad.workflow.instanceActivities.models.Model');

        if($document->notes){
            $notes = $document->notes;
        }
        else{
            $notes = array();
        }
        $notes[] = $note;
        $document->notes = $notes;
        try {
            return $document->publish(null, null);
        } catch (org_glizy_validators_ValidationException $e) {
            return $e->getErrors();
        }
    }
}