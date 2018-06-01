<?php
class metafad_tei_controllers_ajax_CreateNodeFromParent extends metafad_tei_controllers_ajax_SaveDraft
{
    // crea un nodo figlio di $parentId di tipo $typeId
    public function execute($parentId, $typeId)
    {
        $arParent = org_glizy_ObjectFactory::createModel('metafad.tei.models.Model');
        $arParent->load($parentId, 'PUBLISHED_DRAFT');

        $ar = org_glizy_ObjectFactory::createModel('metafad.tei.models.TeiType');
        $ar->find(array('tei_type_key' => $typeId));

        $data = array(
            '__id' => '',
            '__model' => $ar->tei_type_model,
            'pageId' => $ar->tei_type_pageId,
            'sectionType' => $typeId,
            'parent' => array(
                'id' => $parentId,
                'text' => $arParent->getTitle()
            )
        );

        $result = parent::execute(json_encode($data));

        if ($result['errors']) {
            return $result;
        }

        $routing = __Routing::makeUrl('archiviMVC', array(
            'id' => $result['set']['__id'],
            'pageId' =>  $ar->tei_type_pageId,
            'sectionType' => $typeId,
            'action' => 'editDraft'
        ));

        return $routing;
    }
}
