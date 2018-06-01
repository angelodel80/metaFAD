<?php

class metafad_sbn_modules_sbnunimarc_model_proxy_InventoryProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $inventoryList = explode(",",str_replace(array("'",'[',']'),array('','',''),$proxyParams->inventory));

        foreach ($inventoryList as $value) {
          if($term != '')
          {
            if(strpos($value,$term)!==false)
            {
              $result[] = array(
                'id' => $value,
                'text' => $value
              );
            }
          }
          else
          {
            $result[] = array(
              'id' => $value,
              'text' => $value
            );
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
