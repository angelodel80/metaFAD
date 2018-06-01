<?php
class metafad_teca_MAG_controllers_ajax_CheckMag extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
      $it = org_glizy_objectFactory::createModelIterator('metafad.teca.MAG.models.Relations')
              ->where('mag_relation_stru_id',$id)
              ->where('mag_relation_parent',0)
              ->first();
      if($it)
      {
        $parent = org_glizy_objectFactory::createModel('metafad.teca.MAG.models.Model');
        $parent->load($it->mag_relation_FK_document_id, 'PUBLISHED_DRAFT');
        return $parent->BIB_dc_identifier_index;

      }
      else
      {
        return null;
      }
    }
}
