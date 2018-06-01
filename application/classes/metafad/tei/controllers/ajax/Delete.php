<?php
class metafad_tei_controllers_ajax_Delete extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id)
    {
        $result = $this->checkPermissionForBackend('delete');
        if (is_array($result)) {
            return $result;
        }
        
        $ar = __ObjectFactory::createModel('metafad.tei.models.Model');
        $ar->load($id, 'PUBLISHED_DRAFT');
        
        if ($ar->root == 'false') {
            $ar->load($ar->parent['id'], 'PUBLISHED_DRAFT');
        }

        $url = __Routing::makeUrl('archiviMVC', array(
            'id' => $ar->getId(),
            'pageId' => $ar->pageId,
            'sectionType' => $ar->sectionType,
            'action' => 'edit'.($ar->getStatus() == 'DRAFT' ? "Draft" : "")
        ));

        $proxy = __ObjectFactory::createObject('metafad.tei.models.proxy.ModuleProxy');
        $proxy->delete($id);

        $this->directOutput = true;
        return array('url' => $url);
    }
}
