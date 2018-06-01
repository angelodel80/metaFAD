<?php
class metafad_tei_controllers_ajax_GetTreeFromParent extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
        $it = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        $children = array();

        $arChild = org_glizy_ObjectFactory::createModel('metafad.tei.models.Model');

        foreach($it as $ar) {
            $it2 = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Model')
                ->load('getParent', array(':parent' => $ar->getId(), ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

            $arChild->load($ar->getId(), 'PUBLISHED_DRAFT');

            $children[] = array(
                'id' => $ar->getId(),
                'title' => $ar->getTitle(),
                'type' => $ar->getTeiType(),
                'folder' => $it2->count() ? true : false,
                'lazy' => $it2->count() ? true : false, // lazy è true se il nodo ha figli
                'canAdd' => $arChild->canAdd(),
                'canEdit' => $arChild->hasPublishedVersion(),
                'canEditDraft' => $arChild->hasDraftVersion(),
                'routingEdit' => __Routing::makeUrl('archiviMVC', array(
                    'id' => $arChild->getId(),
                    'pageId' => $arChild->pageId,
                    'sectionType' => $arChild->getTeiType(),
                    'action' => 'edit'
                )),
                'routingEditDraft' => __Routing::makeUrl('archiviMVC', array(
                    'id' => $arChild->getId(),
                    'pageId' => $arChild->pageId,
                    'sectionType' => $arChild->getTeiType(),
                    'action' => 'editDraft'
                ))
            );
        }

        $this->directOutput = true;
        return $children;
    }
}
