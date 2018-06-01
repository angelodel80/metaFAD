<?php
class metafad_workflow_processes_controllers_ajax_SaveClose extends metafad_workflow_processes_controllers_ajax_Save
{
    function execute($data)
    {
        $result = parent::execute($data);

        if ($result['errors']) {
            return $result;
        }

        return array('url' => 'processi-definizione-processi');
    }
}