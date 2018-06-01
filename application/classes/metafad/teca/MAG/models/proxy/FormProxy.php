<?php

class metafad_teca_MAG_models_proxy_FormProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $moduleId = $proxyParams->modelName;
        if(!$moduleId)
        {
           return '';
        }
        $arrayId = array();
        $module = org_glizy_Modules::getModule($moduleId);
        if($moduleId == 'metafad.sbn.modules.sbnunimarc')
        {
          $searchQuery = array();
          $searchQuery['q'] .= 'docType_s:"unimarcSBN"';
          $searchQuery['wt'] = 'json';
          $searchQuery['start'] = 0;
          $searchQuery['rows'] = 100;
          if($term)
          {
            $searchQuery['q'] .= ' AND '.$term.'*';
          }
          $postBody = $this->buildHttpQuery($searchQuery);

          $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',
              __Config::get('metafad.solr.url') . 'select',
              'POST',
              $postBody,
              'application/x-www-form-urlencoded');
          $request->setTimeout(1000);
          $request->setAcceptType('application/json');
          $request->execute();

          $it = json_decode($request->getResponseBody())->response->docs;
        }
        else if($module->model)
        {
          $it = org_glizy_objectFactory::createModelIterator($module->model);
        }
        else {
          $it = org_glizy_objectFactory::createModelIterator($moduleId);
        }
        $count = 0;
        foreach ($it as $ar) {
            if($moduleId !== 'metafad.sbn.modules.sbnunimarc')
            {
              if(in_array($ar->document_id,$arrayId))
              {
                continue;
              }
              else
              {
                $arrayId[] = $ar->document_id;
              }
            }
            if($moduleId == 'metafad.sbn.modules.sbnunimarc')
            {
              $result[] = array(
                  'id' => $ar->id,
                  'text' => $ar->id . ' - ' . $ar->Titolo_s,
              );
              $count++;
            }
            else if(strpos($moduleId, 'archivi.models') === 0)
            {
              $archiviProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');
              $title = $ar->_denominazione;

              if($term != '' && stripos($ar->document_id,$term) !== false)
              {
                $result[] = array(
                    'id' => $ar->document_id,
                    'text' => $title,
                );
                $count++;
              }
              else if($term == '')
              {
                $result[] = array(
                    'id' => $ar->document_id,
                    'text' => $title,
                );
                $count++;
              }
            }
            else
            {
              $arraySub = array('SGLT'=>'SGL','SGTI'=>'SGT','SGTP'=>'SGT');
			  $titleData = end($ar->getSolrDocument());
			  if(strpos($titleData,',') !== false)
			  {
				  $titleData = explode(',',$titleData);
				  $titleKey = str_replace('_t','',$titleData[1]);
			  }
			  else {
				  $titleKey = str_replace('_t','',end($ar->getSolrDocument()));
			  }
              $e = $ar->$arraySub[$titleKey];
              $val = $e[0]->$titleKey;
              if(is_array($val))
              {
                $val = $val[0]->{$titleKey.'-element'};
              }
              if($val == null)
              {
                $val = 'Senza titolo';
              }
              if($term != '' && stripos($val,$term) !== false)
              {
                $result[] = array(
                    'id' => $ar->document_id,
                    'text' => $val,
                );
                $count++;
              }
              else if($term == '')
              {
                $result[] = array(
                    'id' => $ar->document_id,
                    'text' => $val,
                );
                $count++;
              }
            }

          if($count == 50)
          {
            break;
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

    protected function buildHttpQuery($searchQuery)
    {
        $temp = array_merge($searchQuery, array());
        $url = "";
        unset($temp['url']);
        unset($temp['action']);
        foreach ($searchQuery as $k => $v) {
            if (is_array($v)) {
                if ($k == 'facet.field' || $k == 'fq') {
                    foreach ($v as $v1) {
                        $url .= $k . '=' . $v1 . '&';
                    }
                    unset($temp[$k]);
                } else {
                    $temp[$k] = implode($v, ',');
                }
            }
        }

        return $url . http_build_query($temp);
    }
}
