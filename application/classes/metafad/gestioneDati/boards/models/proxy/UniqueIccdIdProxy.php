<?php
class metafad_gestioneDati_boards_models_proxy_UniqueIccdIdProxy extends GlizyObject
{
  public function createUniqueIccdId($data)
  {
    $model = (property_exists($data,'__model')) ? $data->__model : $data->_className;
    $type = str_replace(array("Scheda",".models.Model"),array("",""),$model);
    $type = preg_replace('/[0-9]+/', '', $type);
    if($type === 'AUT' || $type === 'BIB')
    {
      $field = $type.'H';
      $uniqueIccdId = (__Config::get("metafad.iccd.uniqueIccdId.useESC") ? $data->ESC : "").$data->$field;
    }
    else
    {
      $RVEL = ($data->RV[0]->RVE[0]->RVEL != '') ? '-'.$data->RV[0]->RVE[0]->RVEL : '';
      $uniqueIccdId = $data->NCTR.$data->NCTN.$data->NCTS.$RVEL;
    }
    return $uniqueIccdId;
  }

  public function checkUnique($data, $uniqueIccdId, $reset = false)
  {
      //Ho ordinato in maniera decrescente per document_id per i casi di sporcizia (record con UniqueICCDId doppioni)
      $ar = org_glizy_ObjectFactory::createModelIterator($data->__model)
          ->where('uniqueIccdId',$uniqueIccdId)
          ->orderBy("document_id", "DESC")->first();

      if ($ar) {
          $data->__id = $ar->getId();

          if ($reset) {
              $values = $ar->getValues(false, false, false, false);
              // azzera tutti i campi nell'oggetto data che erano settati nel record appena caricato ma non in data
              foreach ($values as $k => $v) {
                  if (!property_exists($data, $k) && $k != 'instituteKey' && $k != 'isValid') {
                      $data->$k = '';
                  }
              }
          }
      }

      $data->uniqueIccdId = $uniqueIccdId;

      return $data;
    }
}
