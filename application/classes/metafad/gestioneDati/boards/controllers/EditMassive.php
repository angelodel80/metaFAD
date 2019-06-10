<?php
class metafad_gestioneDati_boards_controllers_EditMassive extends metafad_common_controllers_Command
{
  public function execute($id)
  {
    if (__Session::get($id)) {
      $id = implode('-', __Session::get($id));
    }
    $this->checkPermissionForBackend('edit');

    $c = $this->view->getComponentById('__id');
    $c->setAttribute('value', $id);
    __Session::set('idList', $id);

    $ar = org_glizy_objectFactory::createModelIterator('metafad.gestioneDati.massiveEdit.models.Model')
      ->where('idList', $id)->first();

    if ($ar) {
      $this->view->getComponentById('__groupId')->setAttribute('value', $ar->document_id);
      $this->view->getComponentById('groupName')->setAttribute('value', $ar->groupName);
    }

    $m = $this->view->getComponentById('__model');

      //Nascondo ogni campo, prima di mostrare quelli necessari alla modifica
      //massiva (necessario e cablato in POLOFI, variabile nel config per indicarlo)
    if (__Config::get('metafad.feature.hideFieldMassive')) {
      $fields = $this->getFields($m->getAttribute('value'));
      $validTabs = array("OG", "LC", "TU", "AD");
      $validFields = array(
        "OG", "OGT", "LC", "PVC", "PVCL", "PVL", "PVE", "LDC", "LDCT", "LDCQ",
        "LDCN", "LDCC", "LDCU", "LDCM", "TU", "CDG", "CDGG", "CDGS",
        "NVC", "NVCT", "NVCE", "NVCD", "AD", "ADS", "ADSP", "ADSM"
      );
      foreach ($fields as $f) {
        if (strpos($f, "OGT") !== false) {
          $validFields[] = $f;
        }
      }

      foreach ($fields as $f) {
        if ($this->view->getComponentById($f . '-tab') && !in_array($f, $validTabs)) {
          $this->view->getComponentById($f . '-tab')->setAttribute('visible', false);
        }
      }

      foreach ($fields as $f) {
        if ($this->view->getComponentById($f) && !in_array($f, $validFields)) {
          $this->view->getComponentById($f)->setAttribute('visible', false);
        }
      }
    }

    __Request::set('model', $m->getAttribute('value'));

    $compToHide = array(
      'historyTab',
      'relationsTab',
      'linkShowImages',
      'templateTitle',
      'link-show-images',
      'link-show-sbn',
      'linkedImages'
    );
    foreach ($compToHide as $value) {
      $c = $this->view->getComponentById($value);
      if ($c) {
        $c->setAttribute('visible', false);
        $c->setAttribute('enabled', false);
      }
    }

  }

  public function getFields($model)
  {
    $model = explode(".", $model);
    $moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
    $elements = $moduleService->getElements($model[0]);
    $fieldsArray = array();
    $this->exploreElements($elements, $fieldsArray);
    return $fieldsArray;
  }

  public function exploreElements($elements, &$fieldsArray)
  {
    foreach ($elements as $el) {
      $fieldsArray[] = $el['name'];
      if ($el['children']) {
        $this->exploreElements($el['children'], $fieldsArray);
      }
    }
  }
}
