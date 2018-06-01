<?php
class metafad_gestioneDati_schedeSemplificate_controllers_Delete extends org_glizycms_contents_controllers_moduleEdit_Delete
{
    public function execute($id, $model)
    {
        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);

        $simpleAdminHelper = org_glizy_objectFactory::createObject('metafad.gestioneDati.schedeSemplificate.views.helpers.SimpleAdminHelper');
        $simpleAdminHelper->deleteFiles($id);

        parent::execute($id, $model);
    }
}
