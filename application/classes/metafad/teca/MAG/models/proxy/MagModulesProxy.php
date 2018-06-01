<?php

class metafad_teca_MAG_models_proxy_MagModulesProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $aut = $proxyParams->noAuthority;
        $modules = org_glizy_Modules::getModules();
        foreach ($modules as $key => $value) {
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
            if($value->id == 'metafad.sbn.modules.sbnunimarc' && stripos($value->id,$term))
            {
              $result[] = array(
                  'id' => $value->id,
                  'text' => 'Patrimonio',
              );
            }
            if($value->id == 'archivi' && stripos($value->id,$term))
            {
              $result[] = array(
                  'id' => $value->id,
                  'text' => 'Archivi',
              );
            }
          }
          else if($value->isICCDModule === true || $value->id == 'metafad.sbn.modules.sbnunimarc' || $value->id == 'archivi')
          {
            if($aut && (stripos($value->name,'aut') !== false || stripos($value->name,'bib') !== false))
            {
              continue;
            }
            if($value->id == 'metafad.sbn.modules.sbnunimarc')
            {
              $result[] = array(
                  'id' => $value->id,
                  'text' => 'UNIMARC',
              );
            }
            else if($value->id == 'archivi')
            {
              foreach($value->modelList as $model)
              {
                $result[] = array(
                    'id' => $model,'text' => __T('modelname_'.$model),
                );
              }
            }
            else
            {
              $result[] = array(
                  'id' => $value->id,
                  'text' => $value->name,
              );
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
