<?php

class metafad_modules_iccd_models_proxy_IccdFormProxy extends GlizyObject
{
  public function findTerm($fieldName, $model, $query, $term, $proxyParams)
  {
    $modules = org_glizy_Modules::getModules();
        //Seleziono il model su cui filtrare la ricerca delle schede collegabili
        //Se proxy params non è settato seleziono tutti i moduli
    if (!$proxyParams->iccdModuleType) {
      $modelsList = array();
      foreach ($modules as $key => $value) {
        if ($value->isICCDModule && !$value->isAuthority) {
          $modelsList[] = $value->model;
        }
      }
    } else {
      foreach ($modules as $key => $value) {
        if ($value->iccdModuleType === $proxyParams->iccdModuleType) {
          $iccdFormModelName = $value->model;
          break;
        }
      }
    }

    $result = array();
    if ($modelsList) {
      foreach ($modelsList as $model) {
        $this->getValues($model, $term, $result);
      }
    } else if (!$iccdFormModelName && $term) {
      $result[] = array(
        'id' => $term,
        'text' => $term
      );
      return $result;
    } elseif (!$iccdFormModelName) {
      return '';
    } else {
      $this->getValues($iccdFormModelName, $term, $result);
    }

    return $result;
  }

  public function getValues($model, $term, &$result)
  {
    $it = org_glizy_objectFactory::createModelIterator($model)
      ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
      ->limit(0, 50);

    foreach ($it as $ar) {
        //NCTR NCTN NCTS RVEL
      $RVEL = null;
      if ($ar->RV[0]->RVE[0]->RVEL) {
        $RVEL = '-' . $ar->RV[0]->RVE[0]->RVEL;
      }
        //Unisco i valori che vanno a creare il campo RSEC
      $text = $ar->NCTR . $ar->NCTN . $ar->NCTS . $RVEL;

      if ($term != '') {
        if (strpos($text, $term) !== false) {
          $result[] = array(
            'id' => $ar->getId(),
            'text' => $text
          );
        }
      } else {
        $result[] = array(
          'id' => $ar->getId(),
          'text' => $text
        );
      }
    }
  }
}
