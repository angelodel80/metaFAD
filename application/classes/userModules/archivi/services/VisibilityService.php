<?php
ini_set('memory_limit','2048M');
ini_set('max_execution_time', 0);

class archivi_services_VisibilityService extends metacms_jobmanager_service_JobService
{
    /* @var $visHelper metafad_common_helpers_VisibilityHelper */
    protected $visHelper;

    /* @var $arcProxy archivi_models_proxy_ArchiviProxy */
    protected $arcProxy;

    protected $numElementsModified;
    protected $numElementsToModify;

    public function run()
    {
        try {
            $this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);
            $this->arcProxy = __ObjectFactory::createModel("archivi.models.proxy.ArchiviProxy");
            $this->visHelper = __ObjectFactory::createObject('metafad_common_helpers_VisibilityHelper');

            $this->numElementsModified = 0;
            $this->numElementsToModify = 1 + $this->countAllChildren($this->params['id']);
            $this->toggleVisibilityToHierarchy($this->params['id'], $this->params['model'], $this->params['visibility']);
            $this->finish('Task completato');
        } catch (Error $e) {
            $this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
        }
    }


    /**
     * @return metafad_common_helpers_VisibilityHelper|object
     */
    public function getHelper()
    {
        return $this->visHelper;
    }

    /**
     * @param $id
     * @param $model
     */
    public function toggleVisibilityToHierarchy($id, $model, $visibility = null)
    {
        $this->visHelper = $this->visHelper ?: __ObjectFactory::createObject('metafad_common_helpers_VisibilityHelper');

        if ($id) {
            $ar = __ObjectFactory::createModel($model);
            $ar->load($id);

            $arData = $ar->getRawData();
            $arData->__id = $ar->document_id;
            $arData->__model = $ar->document_type;

            $this->toggleVisibility($ar, $arData, $visibility);

            $this->uniformChildrenVisibility($arData, $visibility);
        }
    }

    /**
     * @param $figlio
     * @param $padre
     */
    private function changeVisibility($figlio, $padre, $visibility = null)
    {
        $child = $figlio->getRawData();
        $child->__id = $child->document_id;
        $child->__model = $child->document_type;
        if ($visibility !== null) {
            $child->visibility = $visibility;
        } else if (!$this->visHelper->compareVisibilities($child->visibility, $padre->visibility)) {
            $child->visibility = $this->visHelper->toggleVisibility($child->visibility, $child->__model);
        }

        $oldFlag = $this->arcProxy->getUpdateVisibility();
        $this->arcProxy->setUpdateVisibility(false);
        $this->arcProxy->save($child);
        $this->arcProxy->setUpdateVisibility($oldFlag);

        $this->uniformChildrenVisibility($figlio, $visibility);
    }

    /**
     * @param $ar
     * @param $arData
     */
    private function toggleVisibility($ar, $arData, $visibility = null)
    {
        $arData->__id = $ar->document_id;
        $arData->__model = $ar->document_type;
        $arData->visibility = ($visibility !== null) ? $visibility : $this->visHelper->toggleVisibility($arData->visibility, $arData->__model);

        $oldFlag = $this->arcProxy->getUpdateVisibility();
        $this->arcProxy->setUpdateVisibility(false);
        $this->arcProxy->save($arData);
        $this->arcProxy->setUpdateVisibility($oldFlag);

        $this->numElementsModified++;

        $progress = round($this->numElementsModified / $this->numElementsToModify * 100);
        $this->updateProgress($progress);
    }

    public function countAllChildren($id)
    {
        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        $sum = 0;
        foreach ($it as $node) {
            $sum += 1+$this->countAllChildren($node->getId());
        }

        return $sum;
    }

    public function getAllSubIDs($root, $container = array())
    {
        if (in_array($root->__id, $container) || !$root->__id) {
            return array_unique($container);
        }

        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $root->__id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        foreach ($it as $node) {
            $data = $node->getRawData();
            $data->__id = $node->document_id;
            $data->__model = $node->document_type;
            $container = array_merge($container, $this->getAllSubIDs($data));
        }

        return array_unique($container);
    }

    /**
     * @param $padre
     */
    public function uniformChildrenVisibility($padre, $visibility = null)
    {
        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $padre->__id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        foreach ($it as $son) {
            $this->changeVisibility($son, $padre, $visibility);
        }
    }
}
