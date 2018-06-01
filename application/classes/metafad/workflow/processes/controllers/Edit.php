<?php

class metafad_workflow_processes_controllers_Edit extends org_glizycms_contents_controllers_moduleEdit_Edit
{
    public function execute($id)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'));

            if ($data['instanceActivities']) {
                foreach ($data['instanceActivities'] as $instanceActivitiesId) {
                    $activity = $contentProxy->loadContent($instanceActivitiesId, $c->getAttribute('value'));
                        if($activity['deadlineDate']){
                            $activity['deadlineDate'] = date('d/m/Y', strtotime($activity['deadlineDate']));
                        }
                    $activity['activity']->text = $activity['activity']->title;
                   // $activity->deadlineDate = $activity->deadlineDate->format('d/m/Y');
                    $instanceActivitiesData[] = $activity;
                }
            }
            $data['__id'] = $id;
            $data['activities'] = $instanceActivitiesData;
            $this->view->setData($data);
        }
    }
}