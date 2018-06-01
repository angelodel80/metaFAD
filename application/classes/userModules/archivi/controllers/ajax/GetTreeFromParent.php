<?php
class archivi_controllers_ajax_GetTreeFromParent extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        
        $children = array();

        $arChild = org_glizy_ObjectFactory::createModel('archivi.models.Model');

        foreach($it as $ar) {
            $it2 = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
                ->load('getParent', array(':parent' => $ar->getId(), ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

            $arChild->load($ar->getId(), 'PUBLISHED_DRAFT');

            $pageId = $arChild->pageId ?: "archivi-".strtolower(str_replace("archivi.models.", "", $arChild->document_type));

            $children[] = array(
                'id' => $arChild->getId(),
                'title' => implode(" || ", array_map("trim", array_slice(explode(" || ", $arChild->_denominazione), 1))), //POLODEBUG-481 BE, Punto 2
                'type' => $arChild->livelloDiDescrizione,
                'folder' => $it2->count() ? true : false,
                'lazy' => $it2->count() ? true : false, // lazy è true se il nodo ha figli
                'canAdd' => $arChild->livelloDiDescrizione != 'documento-allegato' && $arChild->livelloDiDescrizione != 'unita-documentaria',
                'canEdit' => $arChild->hasPublishedVersion(),
                'canEditDraft' => $arChild->hasDraftVersion(),
                'routingEdit' => __Routing::makeUrl('archiviMVC', array(
                    'id' => $arChild->getId(),
                    'pageId' => $pageId,
                    'sectionType' => $arChild->livelloDiDescrizione,
                    'action' => 'edit'
                )),
                'routingEditDraft' => __Routing::makeUrl('archiviMVC', array(
                    'id' => $arChild->getId(),
                    'pageId' => $pageId,
                    'sectionType' => $arChild->livelloDiDescrizione,
                    'action' => 'editDraft'
                ))
            );
        }

        $this->directOutput = true;
        return $children;
    }
}
