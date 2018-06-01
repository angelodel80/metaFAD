<?php
class metafad_teca_MAG_helpers_MediaLinkHelper extends GlizyObject
{
  public function linkToSBN($model,$id,$images,$stru,$inventory)
  {
    $sbnProxy = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.model.proxy.UpdateSbnProxy');
    $ar = org_glizy_ObjectFactory::createModelIterator($model)
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
            ->where('id',$id)
            ->first();
    if($ar)
    {
      if(sizeof($images) > 1)
      {
        if($inventory)
        {
          $ar->linkedInventoryStrumag = $this->getStrumagInventoryList($ar->linkedInventoryStrumag,$inventory,$stru);
        }
        else
        {
          $ar->linkedStruMag = $stru;
        }
      }
      else
      {
        if($inventory)
        {
          $ar->linkedInventoryMedia = $this->getInventoryList($ar->linkedInventoryMedia,$inventory,$images);
        }
        else
        {
          $linkedMedia = array();
          $linkedMedia[] = array('media' => reset($images));
          $ar->linkedMedia = $linkedMedia;
        }
      }
      $ar->save(null,false,'PUBLISHED');
      $rawData = $ar->getRawData();
      $rawData->__id = $id;

      //Elaboro i dati della prima immagine collegata dal MAG
      $fi = org_glizy_objectFactory::createObject('metafad.viewer.helpers.FirstImage');
      $firstImage = $fi->execute($id, 'sbn');

      $sbnProxy->updateSbnDigitale($rawData,$firstImage['firstImage']);
    }
  }

  public function linkToArchive($model,$id,$images,$stru)
  {
    $archiveProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');

    $id = explode(" ",$id);
    $id = $id[sizeof($id) - 1];
    $ar = org_glizy_ObjectFactory::createModelIterator($model)
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
            ->where('document_id',$id)
            ->first();
    if($ar)
    {
      if(sizeof($images) > 1)
      {
        $ar->linkedStruMag = $stru;
      }
      else
      {
        $ar->mediaCollegati = reset($images);
      }
      $rawData = $ar->getRawData();
      $rawData->__id = $ar->document_id;
      $rawData->__model = $model;
      $archiveProxy->save($rawData);
    }
  }

  public function linkToIccd($model,$id,$images)
  {
    $iccdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy');

    $ar = org_glizy_ObjectFactory::createModelIterator($model)
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
            ->where('uniqueIccdId',$id)
            ->first();
    //N.B. valutare se FTA va azzerato o le immagini vanno semplicemente aggiunte
    if($ar)
    {
      $fta = array();
      $ftaObject = new stdClass();
      if(!empty($images))
      {
        foreach ($images as $image) {
          $ftaObject->{'FTA-image'} = $image;
          $fta[] = $ftaObject;
        }
      }
      $ar->FTA = $fta;

      $rawData = $ar->getRawData();
      $rawData->__model = $model;
      $rawData->__id = $ar->document_id;

      $iccdProxy->save($rawData);
    }
  }

  private function getStrumagInventoryList($linkedInventoryStrumag,$inventory,$stru)
  {
    $arrayStrumag = array();
    if(!empty($linkedInventoryStrumag))
    {
      foreach ($linkedInventoryStrumag as $value) {
        $arrayStrumag[$value->strumagInventoryNumber] = $value->linkedStruMagToInventory;
      }
    }
    $arrayStrumag[$inventory] = $stru;

    return $this->getLinkedMedia($arrayStrumag,'strumagInventoryNumber','linkedStruMagToInventory');
  }

  private function getInventoryList($linkedInventoryMedia,$inventory,$images)
  {
    $arrayImages = array();
    if(!empty($linkedInventoryMedia))
    {
      foreach ($linkedInventoryMedia as $value) {
        $arrayImages[$value->inventoryNumber] = $value->media;
      }
    }
    $obj = new stdClass();
    $obj->mediaInventory = reset($images);
    $arrayImages[$inventory] = array($obj);

    return $this->getLinkedMedia($arrayImages,'inventoryNumber','media');
  }

  private function getLinkedMedia($array,$field1,$field2)
  {
    $linkedMedia = array();
    if(!empty($array))
    {
      foreach ($array as $key => $value) {
        $linkedMedia[] = array(
          $field1 => $key,
          $field2 => $value
        );
      }
    }
    return $linkedMedia;
  }
}
