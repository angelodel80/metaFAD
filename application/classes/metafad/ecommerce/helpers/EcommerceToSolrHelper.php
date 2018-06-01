<?php
class metafad_ecommerce_helpers_EcommerceToSolrHelper extends GlizyObject
{
  //Funzione che restituisce i dati relativi all'ecommerce
  //in modo da avere nei risultati di ricerca la possibilità di comprare
  //la prima immagine relativa al risultato
  private $licenseProxy;

  public function getEcommerceInfo($data,$type)
  {
    $licenseData = null;
    $media = null;
    $ecommerce = null;
    $this->licenseProxy = org_glizy_objectFactory::createObject('metafad_ecommerce_licenses_models_proxy_LicensesProxy');

    if($type == 'sbn')
    {
      $ecommerceObject = new stdClass();
      $ecommerceObject->id = $data->__id;
      $imagesCount = 0;
      $hasEcommerce = false;

      //Ricostruisco le informazioni relative alle licenze per l'intero record
      if($data->ecommerceLicenses)
      {
        $licensesArray = array();
        foreach ($data->ecommerceLicenses as $license) {
          $licensesArray[] = $this->licenseProxy->getDetailFromId($license->id);
        }
        $ecommerceObject->sintetic = $licensesArray;
      }

      $ecommerceObject->medias = array();

      if($data->linkedStruMag)
      {
        $hasEcommerce = $this->iterateStrumag($data,$ecommerceObject,$imagesCount);
      }
      else if($data->linkedMedia)
      {
        $hasEcommerce = true;
        foreach($data->linkedMedia as $media)
        {
          $licensesArray = $this->iterateLicenses($media);
          if($licensesArray)
          {
            $imagesCount++;
            array_push($ecommerceObject->medias,array('id'=>json_decode($media->media)->id,'license'=>$licensesArray));
          }
          else if($ecommerceObject->sintetic)
          {
            $imagesCount++;
            array_push($ecommerceObject->medias,array('id'=>json_decode($media->media)->id,
                                                    'license'=>array()));
            $hasEcommerce = true;
          }
        }
      }
      else if($data->linkedInventoryMedia)
      {
        $hasEcommerce = true;
        foreach($data->linkedInventoryMedia as $linkedInventory)
        {
          foreach ($linkedInventory->media as $media) {
            $licensesArray = $this->iterateLicenses($media);
            if($licensesArray)
            {
              $imagesCount++;
              array_push($ecommerceObject->medias,array('id'=>json_decode($media->mediaInventory)->id,'license'=>$licensesArray));
            }
            else if($ecommerceObject->sintetic)
            {
              $imagesCount++;
              array_push($ecommerceObject->medias,array('id'=>json_decode($media->mediaInventory)->id,
                                                      'license'=>array()));
              $hasEcommerce = true;
            }
          }
        }
      }
      else if($data->linkedInventoryStrumag)
      {
        foreach ($data->linkedInventoryStrumag as $strumag) {
          $hasEcommerce = $this->iterateStrumag($data,$ecommerceObject,$imagesCount,'linkedStruMagToInventory');
        }
      }

      $ecommerceObject->imagesCount = $imagesCount;
    }
    else if($type == 'iccd')
    {
      $ecommerceObject = new stdClass();
      $ecommerceObject->id = $data->__id;
      //Ricostruisco le informazioni relative alle licenze per l'intero record
      if($data->ecommerceLicenses)
      {
        $licensesArray = array();
        foreach ($data->ecommerceLicenses as $license) {
          $licensesArray[] = $this->licenseProxy->getDetailFromId($license->id);
        }
        $ecommerceObject->sintetic = $licensesArray;
      }
      //Ricostruisco le informazioni relative alle licenze per le singole immagini
      if($data->FTA)
      {
        $ecommerceObject->medias = array();
        $fta = $data->FTA;
        $imagesCount = 0;
        foreach ($fta as $media) {
          if($media->{'FTA-image'})
          {
            $licensesArray = $this->iterateLicenses($media);
            if($licensesArray)
            {
              $imagesCount++;
              array_push($ecommerceObject->medias,array('id'=>json_decode($media->{'FTA-image'})->id,
                                                      'license'=>$licensesArray));
              $hasEcommerce = true;
            }
            else if($ecommerceObject->sintetic)
            {
              $imagesCount++;
              array_push($ecommerceObject->medias,array('id'=>json_decode($media->{'FTA-image'})->id,
                                                      'license'=>array()));
              $hasEcommerce = true;
            }
          }
        }
        $ecommerceObject->imagesCount = $imagesCount;

        if($ecommerceObject->medias)
        {
          return json_encode($ecommerceObject);
        }
      }

      if(!$ecommerceObject->medias)
      {
        return null;
      }
    }
    else if($type == 'archive')
    {
      $ecommerceObject = new stdClass();
      $ecommerceObject->id = $data->__id;
      $imagesCount = 0;
      $hasEcommerce = false;

      //Ricostruisco le informazioni relative alle licenze per l'intero record
      if($data->ecommerceLicenses)
      {
        $licensesArray = array();
        foreach ($data->ecommerceLicenses as $license) {
          $licensesArray[] = $this->licenseProxy->getDetailFromId($license->id);
        }
        $ecommerceObject->sintetic = $licensesArray;
      }

      $ecommerceObject->medias = array();

      if($data->linkedStruMag)
      {
        $hasEcommerce = $this->iterateStrumag($data,$ecommerceObject,$imagesCount);
      }
      else if($data->mediaCollegati)
      {
        $media = json_decode($data->mediaCollegati);
        $media->linkedMediaEcommerce = $data->linkedMediaEcommerce;
        $licensesArray = $this->iterateLicenses($media);
        if($licensesArray)
        {
          $imagesCount++;
          array_push($ecommerceObject->medias,array('id'=>$media->id,
                                                  'license'=>$licensesArray));
          $hasEcommerce = true;
        }
        else if($ecommerceObject->sintetic)
        {
          $imagesCount++;
          array_push($ecommerceObject->medias,array('id'=>$media->id,
                                                  'license'=>array()));
          $hasEcommerce = true;
        }
      }
      $ecommerceObject->imagesCount = $imagesCount;
    }

    if($hasEcommerce) {
      return json_encode($ecommerceObject);
    }
    else {
      return null;
    }
  }

  public function getLicenseData($id)
  {
    $ar = org_glizy_objectFactory::createModel('metafad.ecommerce.licenses.models.Model');
    $ar->load($id);

    return json_encode($ar->getRawData());
  }

  public function iterateLicenses($media)
  {
    $licensesArray = array();
    if(!empty($media->linkedMediaEcommerce) && $media->linkedMediaEcommerce)
    {
      foreach ($media->linkedMediaEcommerce as $license) {
        $licensesArray[] = $this->licenseProxy->getDetailFromId($license->id);
      }
    }
    return $licensesArray;
  }

  public function iterateLicensesStrumag($media)
  {
      $licensesArray = array();
      if (!empty($media->license) && $media->license) {
          $licensesArray[] = $this->licenseProxy->getDetailFromId($media->license->id);
      }
      return $licensesArray;
  }

  public function iterateStrumag($data,&$ecommerceObject,&$imagesCount,$field="linkedStruMag")
  {
    $hasEcommerce = false;
    $ar = org_glizy_objectFactory::createModel('metafad.teca.STRUMAG.models.Model');
    if ($ar->load($data->$field->id)) {
      $stru = json_decode($ar->physicalSTRU);
      foreach ($stru->image as $media) {
        $licensesArray = $this->iterateLicensesStrumag($media);
        //da $media va letto il campo "linkedMediaEcommerce", va aggiunto da MATTEO ROSSI
        if(!empty($licensesArray) && $licensesArray)
        {
          $imagesCount++;
          array_push($ecommerceObject->medias,array('id'=>$media->id,'license'=>$licensesArray));
          $hasEcommerce = true;
        }
      }
    }
    return $hasEcommerce;
  }
}
