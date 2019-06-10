<?php
class archivi_controllers_ajax_ModifyTree extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id, $parentId)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $this->directOutput = true;

        if (!$id || !$parentId) {
            return array('status' => false);
        }

        $arChild = org_glizy_ObjectFactory::createModel('archivi.models.Model');
        $arChild->load($id, 'PUBLISHED_DRAFT');
        $data = $arChild->getRawData();
        $data->__id = $arChild->document_id;
        $data->__model = $arChild->document_type;

        $arParent = org_glizy_ObjectFactory::createModel('archivi.models.Model');
        $arParent->load($parentId, 'PUBLISHED_DRAFT');

        if ($arChild->livelloDiDescrizione == 'documento-allegato' || $arChild->livelloDiDescrizione == 'unita-documentaria') {
            $constraintOK = false;
        } else {
            // verifica se il nodo con id:$id e type:$arChild->livelloDiDescrizione
            // diventando figlio del nodo con id:$parentId e type:$arParent->livelloDiDescrizione
            // non violi vincoli di parentela, altrimenti restituisce errore
            $ar = org_glizy_ObjectFactory::createModelIterator('archivi.models.ArchiveType')
                ->load('checkLevel', array(':childType' => $arChild->livelloDiDescrizione, ':parentType' => $arParent->livelloDiDescrizione))->first();

            $constraintOK = $ar->constraintOK;
        }

        if (!$constraintOK) {
            return array('status' => false);
        }

        $oldParentId = $data->parent->parentId;
        
        $data->parent = (object)array(
            'id' => $parentId,
            'text' => $arParent->_denominazione
        );
        
        $data->root = !$data->parent;

        $archiviProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');
        if ($arChild->getStatus() == 'PUBLISHED') {
            $archiviProxy->save($data, true);
        } else {
            $archiviProxy->saveDraft($data, true);
        }

        // ricalcola gli ordinamenti provvisori dei nodi figli del padre da cui si era partiti (drag)
        // e verso quello in cui si arriva (drop)
        $archiviProxy->setOrdinamentoProvvisorioChildren($oldParentId);
        $archiviProxy->setOrdinamentoProvvisorioChildren($parentId);

        return array('status' => true);
    }
}