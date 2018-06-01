<?php
class metafad_tei_controllers_ajax_ModifyTree extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id, $parentId)
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
        
        $this->directOutput = true;

        if (!$id || !$parentId) {
            return array('status' => false);
        }

        $arChild = org_glizy_ObjectFactory::createModel('metafad.tei.models.Model');
        $arChild->load($id);

        $arParent = org_glizy_ObjectFactory::createModel('metafad.tei.models.Model');
        $arParent->load($parentId);

        $ar = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.TeiType')
            ->load('checkLevel', array(':childType' => $arChild->sectionType, ':parentType' => $arParent->sectionType))->first();

        // verifica se il nodo con id:$id e type:$arChild->sectionType
        // diventando figlio del nodo con id:$parentId e type:$arParent->sectionType
        // non violi vincoli di parentela, altrimenti restituisce errore
        if (!$ar->constraintOK) {
            return array('status' => false);
        }

        $arChild->parent = array(
            'id' => $parentId,
            'text' => $arParent->getTitle()
        );
        $arChild->save();

        return array('status' => true);
    }
}