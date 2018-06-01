<?php
abstract class metacms_jobmanager_service_JobService extends GlizyObject
{
    protected $jobId;
    protected $params;
    protected $status;
    protected $progress;
    protected $description;
    protected $message;
    protected $modified = false;

	public function __construct($jobId)
	{
        $this->jobId = $jobId;
        $ar = org_glizy_objectFactory::createModel('metacms.jobmanager.models.Job');
        $ar->load($this->jobId);
        $this->params = unserialize($ar->job_params);
        $this->description = $ar->job_description;
        $this->message = $ar->job_message;
        $this->status = $ar->job_status;
        $this->progress = $ar->job_progress;
    }

    abstract public function run();

    protected function updateModified($old, $new)
    {
        if (!$this->modified) {
            $this->modified = $old != $new;
        }
    }

    protected function updateStatus($status)
    {
        $this->updateModified($this->status, $status);
        $this->status = $status;
        $this->save();
    }

    protected function updateProgress($progress)
    {
        $this->updateModified($this->progress, $progress);
        $this->progress = $progress;
        $this->save();
    }

    protected function finish($message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        $this->updateStatus(metacms_jobmanager_JobStatus::COMPLETED);
        $this->updateProgress(100);
    }

    protected function setParams($params)
    {
        $this->updateModified($this->progress, $progress);
        $this->params = $params;
    }

    protected function setDescription($description)
    {
        $this->updateModified($this->description, $description);
        $this->description = $description;
    }

    protected function setMessage($message)
    {
        $this->updateModified($this->message, $message);
        $this->message = $message;
        $this->save();
    }

    // salva lo stato del job nel db
    protected function save()
    {
        $ar = org_glizy_objectFactory::createModel('metacms.jobmanager.models.Job');
        $ar->load($this->jobId);
        $ar->job_params = serialize($this->params);
        $ar->job_description = $this->description;
        $ar->job_message = $this->message;
        $ar->job_status = $this->status;
        $ar->job_progress = $this->progress;
        if ($this->modified) {
            $ar->job_modificationDate = new org_glizy_types_DateTime();
        }
        $ar->save();
    }
}
