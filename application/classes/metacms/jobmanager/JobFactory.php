<?php
class metacms_jobmanager_JobFactory extends GlizyObject
{
    public function createJob($name, $params, $description, $type = 'INTERACTIVE')
    {
        $ar = org_glizy_objectFactory::createModel('metacms.jobmanager.models.Job');
        $ar->job_type = $type;
        $ar->job_name = $name;
        $ar->job_params = serialize($params);
        $ar->job_description = $description;
        $ar->job_status = metacms_jobmanager_JobStatus::NOT_STARTED;
        $ar->job_progress = 0;
        $ar->job_creationDate = new org_glizy_types_DateTime();
        $ar->job_modificationDate = new org_glizy_types_DateTime();
        $ar->save();
    }
}