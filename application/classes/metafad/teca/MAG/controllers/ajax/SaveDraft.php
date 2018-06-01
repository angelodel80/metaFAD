<?php
class metafad_teca_MAG_controllers_ajax_SaveDraft extends metafad_common_controllers_ajax_CommandAjax
{
  public function execute($data)
  {
    $result = $this->checkPermissionAndInstitute('editDraft', $data);
    if (is_array($result)) {
      return $result;
    }

    $magProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.MagProxy');
    $objData = json_decode($data);
    $isNew = $objData->__id == '';
    $result = $magProxy->save($objData, true);

    if ($result['set']) {
      metafad_gestioneDati_boards_Common::logAction($isNew, __Request::get('pageId'), 'editDraft', $result['set']['document'], $objData->__id, 'scheda MAG');
    }

    $this->directOutput = true;
    return $result;
  }

}
