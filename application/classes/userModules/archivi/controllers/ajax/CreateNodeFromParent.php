<?php
class archivi_controllers_ajax_CreateNodeFromParent extends archivi_controllers_ajax_SaveDraft
{
    // crea un nodo figlio di $parentId di tipo $typeId
    public function execute($parentId, $typeId)
    {
        $archiviProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');

        $arParent = org_glizy_ObjectFactory::createModel('archivi.models.Model');
        $arParent->load($parentId, 'PUBLISHED_DRAFT');

        $ar = org_glizy_ObjectFactory::createModel('archivi.models.ArchiveType');
        $ar->find(array('archive_type_key' => $typeId));

        $data = array(
            '__id' => '',
            '__model' => $ar->archive_type_model,
            'pageId' => $ar->archive_type_pageId,
            'livelloDiDescrizione' => $typeId,
            'parent' => array(
                'id' => $parentId,
                'text' => $arParent->_denominazione
            ),
            '_denominazione' => $archiviProxy->extractTitleFromStdClass(new stdClass())
        );

        // POLODEBUG-219 - ereditarietà del primo soggetto produttore
        if (!empty($arParent->produttori[0])) {
            $data['produttori'] = array($arParent->produttori[0]);
        }

        $result = parent::execute(json_encode($data));

        if ($result['errors']) {
            return $result;
        }

        $routing = __Routing::makeUrl('archiviMVC', array(
            'id' => $result['set']['__id'],
            'pageId' =>  $ar->archive_type_pageId,
            'sectionType' => $typeId,
            'action' => 'editDraft'
        ));

        return $routing;
    }
}
