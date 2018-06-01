<?php
class metafad_tei_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model, $recurse = false)
    { 
        $this->checkPermissionForBackend('delete');

        $moduleProxy = __ObjectFactory::createObject('metafad.tei.models.proxy.ModuleProxy');

        $it = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        
        if ($it->count() == 0){
            $moduleProxy->delete($id);
        } else if ($recurse) {
            $moduleProxy->delete($id, $recurse);
        } else {
            $this->logAndMessage('Il manoscritto selezionato contiene schede figlie, non è possibile cancellarlo senza prima aver cancellato tutti gli elementi subordinati', '', GLZ_LOG_ERROR);
        }

        org_glizy_helpers_Navigation::goHere();
    }
}
