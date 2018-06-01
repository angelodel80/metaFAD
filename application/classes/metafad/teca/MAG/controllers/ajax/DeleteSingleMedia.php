<?php
class metafad_teca_MAG_controllers_ajax_DeleteSingleMedia extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id)
    {
        $result = $this->checkPermissionForBackend('delete');
        if (is_array($result)) {
            return $result;
        }
        
      //Estraggo informazioni su docstru
      $docStruProxy = $this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
      $rootId = $docStruProxy->getRootNodeByDocumentId($id);
      //Cancello da documents
      $docstruId = $rootId->docstru_rootId;
      $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Publication')
            ->where('document_id',$id);
      foreach ($it as $ar) {
        $ar->delete();
      }
      //Cancello da docStru
      $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Docstru')
            ->where('docstru_rootId',$docstruId)
            ->where('docstru_parentId',$docstruId);
      foreach ($it as $ar) {
        $ar->delete();
      }

      return '';
    }
}
