<?php

class metafad_gestioneDati_schedeSemplificate_models_proxy_ModulesSimpleProxy extends GlizyObject
{
    public function findTerm($fieldName, $subPageType, $query, $term, $proxyParams)
    {
        $aut = $proxyParams->noAuthority;
        $modules = org_glizy_Modules::getModules();
        foreach ($modules as $key => $value) {
          if($value->isICCDModule)
          {
            if($term != '')
            {
              if($aut && (stripos($value->name,'aut') !== false || stripos($value->name,'bib') !== false))
              {
                continue;
              }
              if($value->isICCDModule === true && stripos($value->name,$term) !== false )
              {
                $result[] = array(
                    'id' => $value->id,
                    'text' => $value->name,
                );
              }
              //Se il modulo contiene più modelli (formato <modelpath>$<modeldescription>)
              else if(!is_null($value->subPageTypes)){
                  foreach ($value->subPageTypes as $subPageType) {
                      if (true && stripos($subPageType,$term) !== false){
                          $array = explode('@', $subPageType);
                          $result[] = array(
                              'id' => /*$value->name . '/' . */$array[0],
                              'text' => end($array) . " ({$value->name})"
                          );
                      }
                  }
              }
            }
            else if($value->isICCDModule === true)
            {
              if($aut && (stripos($value->name,'aut') !== false || stripos($value->name,'bib') !== false))
              {
                continue;
              }
              $result[] = array(
                  'id' => $value->id,
                  'text' => $value->name,
              );
            }
            //Se il modulo contiene più modelli (formato <modelpath>$<modeldescription>)
            else if(!is_null($value->subPageTypes)){
                foreach ($value->subPageTypes as $subPageType) {
                    $array = explode('@', $subPageType);
                    $result[] = array(
                          'id' => /*$value->name . '/' . */$array[0],
                        'text' => end($array) . " ({$value->name})"
                    );
                }
            }
          }
        }

        if($result == null)
        {
          return '';
        }
        else {
          return $result;
        }
    }
}
