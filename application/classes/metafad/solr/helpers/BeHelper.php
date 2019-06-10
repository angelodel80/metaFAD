<?php
class metafad_solr_helpers_BeHelper extends GlizyObject
{
    public function mapping($solrRAModel,$data)
    {
      $doc = new stdClass();
      $model = $data->__model;
      //Mapping campi di ricerca
      $mappingFields = json_decode($solrRAModel['beMapping']);
      $mappingHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.MappingHelper');
      foreach ($mappingFields as $key => $value) {
          //3 possibili tipi di chiave:
          //1 - sub:Chiave, ovvero appiattire il gruppo
          //2 - chiave.subChiave, ovvero indica il percorso per ottenere il valore (solo per F)
          //3 - semplice chiave campo
          $label = str_replace(array(":", '(', ')'), '_', strtolower($key));
          $label = str_replace(' ', '_', $label);
          $labeltxt = str_replace('__', '_', $label) . '_ss_lower';
          $label = str_replace('__', '_', $label) . '_ss';
          $doc->$label = array();

          //Caso particolare tipo di scheda (label)
          if ($key == 'Tipo di scheda (label)') {
              $doc->$label = __T($data->TSK);
              $doc->$labeltxt = $doc->$label;
              continue;
          }

          $structure = array();
          if (strpos($value[0], ':') === false && strpos($value[0], '.') === false && strpos($value[0], '->') === false) {
              foreach ($value as $v) {
                  $structure[$v] = array();
              }
          }

          foreach ($value as $v) {
              if (strpos($v, ':') !== false) {
                  $label = str_replace('_ss', '_s', $label);
                  $labeltxt = str_replace('_ss_lower', '_s_lower', $labeltxt);
                  $doc->$label = '';
                  //Quando si indica sub:campo, in realtà campo non ha una corrispondenza
                  //nel model, ma si può ricorstruire l'insieme dei campi che lo compone
                  //tramite il file "elements.json".
                  $f = explode(":", $v);
                  $field = $f[1];
                  $file = str_replace('.', '/', $model);
                  $elements = json_decode(file_get_contents(__Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/' . str_replace('Model', 'elements.json', $file)));
                  foreach ($elements as $e) {
                      if ($e->name == $field) {
                          $element = $e;
                          break;
                      }
                  }
                  //Creo la struttura da utilizzare per l'estrazione dei campi
                  //e appiattisco per ottenere tutti i campi
                  $allSubFields = array();
                  $structure = array();
                  $allSubFields[] = $element->name;

                  foreach ($element->children as $child) {
                      $allSubFields[] = $child->name;
                      if ($child->children) {
                          $structure[$element->name][$child->name] = $mappingHelper->getChildren($child);
                          foreach ($mappingHelper->getChildrenFlat($child) as $v) {
                              $allSubFields[] = $v;
                          }
                      }
                  }

                  foreach ($allSubFields as $fieldKey => $field) {
                      if ($data->$field) {
                          if (is_string($data->$field)) {
                              $mappingHelper->setValuesString($data->$field, $doc->$label, $allSubFields);
                          } else {
                              foreach ($data->$field as $d) {
                                  if (is_object($d)) {
                                      $appoggio = array($d);
                                      $mappingHelper->setValuesString($appoggio, $doc->$label, $allSubFields);
                                  } else {
                                      $mappingHelper->setValuesString($d, $doc->$label, $allSubFields);
                                  }
                              }
                          }
                      }
                  }
                  $doc->$label = rtrim($doc->$label, ' # ');
              } else if (strpos($v, '.') !== false) {
                  $path = explode(".", $v);
                  $fieldValue = $data->$path[0];
                  //Se si tratta di un percorso autore il campo va pescato da DB
                  if ($path[0] == 'AUT') {
                      if ($fieldValue) {
                          $count = 0;
                          foreach ($fieldValue as $f) {
                              $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                              if ($record->load($f->__AUT->id)) {
                                  foreach ($record->getRawData() as $key => $value) {
                                      if ($key == $path[1] && $f->$path[1] == null) {
                                          $fieldValue[$count]->$path[1] = $value;
                                      }
                                  }
                              }
                              $count++;
                          }
                      }
                  }
                  if (!is_string($fieldValue) && $fieldValue != null) {
                      foreach ($fieldValue as $f) {
                          $mappingHelper->setValues($f->$path[1], $doc->$label);
                      }
                  } else if ($fieldValue != null) {
                      $mappingHelper->setValues($fieldValue, $doc->$label);
                  }
              } else if (strpos($v, '->') !== false) {
                  $path = explode("->", $v);
                  $fieldValue = $data->$path[0];
                  if ($fieldValue) {
                      $fieldsToRead = explode("&", $path[2]);
                      foreach ($fieldValue as $fv) {
                          if ($fv->$path[1] && $path[1] != 'AUT') {
                              foreach ($fv->$path[1] as $f) {
                                  foreach ($fieldsToRead as $ftr) {
                                      $mappingHelper->setValues($f->$ftr, $doc->$label);
                                  }
                              }
                          } else {
                              if ($fv->AUT) {
                                  $count = 0;
                                  foreach ($fv->AUT as $f) {
                                      $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                                      if ($record->load($f->__AUT->id)) {
                                          foreach ($record->getRawData() as $key => $value) {
                                              if ($key == $path[2] && $f->$path[2] == null) {
                                                  if ($doc->$label == null) {
                                                      $doc->$label = array();
                                                  }
                                                  array_push($doc->$label, $value);
                                              }
                                          }
                                      }
                                      $count++;
                                  }
                              }
                          }
                      }
                  }
              } else {
                  $mappingHelper->setValues($data->$v, $doc->$label);
              }

              if ($doc->$label == null) {
                  unset($doc->$label);
              }
          }
          $doc->$labeltxt = $doc->$label;

          //Caso particolare codice univoco, unione in unica stringa dei campi
          if ($label == 'codice_univoco_ss') {
              if (is_array($doc->$label)) {
                  foreach ($doc->$label as $d) {
                      $doc->codice_univoco_t .= $d;
                  }
              }
          }
          unset($doc->$label);
      }

      //Informazione sulla presenza o meno di digitale collegato
      $hasImageHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.HasImageHelper');
      $doc->digitale_i = $hasImageHelper->hasImage($data, 'iccd') ? 1 : 0;

      return $doc;
    }
}
