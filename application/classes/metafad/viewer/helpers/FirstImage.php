<?php
class metafad_viewer_helpers_FirstImage extends GlizyObject
{
  protected $viewerHelper;

  function execute($id,$type)
  {
    $this->viewerHelper = org_glizy_objectFactory::createObject('metafad.viewer.helpers.ViewerHelper');
    if(!$type)
    {
      return array('error'=>'Parametro "type" non definito');
    }

    if($type == 'sbn') {
      $record = org_glizy_objectFactory::createModelIterator('metafad.sbn.modules.sbnunimarc.model.Model')
        ->where('id',$id)->first();
        if($record->linkedStruMag)
        {
          $strumagId = ($record->linkedStruMag->id) ? : $record->linkedStruMag['id'];
          return $this->getImageFromStruMag($strumagId);
        }
        else if($record->linkedMedia)
        {
          $dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($record->linkedMedia[0]->instituteKey));
          if(json_decode($record->linkedMedia[0]->media)->id)
          {
              return array('firstImage' => metafad_teca_DAM_Common::replaceUrl($dam->streamUrl(json_decode($record->linkedMedia[0]->media)->id,'thumbnail')));
          }
        }
        else if($record->linkedInventoryMedia)
        {
          $dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($record->linkedInventoryMedia[0]->instituteKey));
          return array('firstImage' => metafad_teca_DAM_Common::replaceUrl($dam->streamUrl(json_decode($record->linkedInventoryMedia[0]->media[0]->mediaInventory)->id,'thumbnail')));
        }
        else if($record->linkedInventoryStrumag)
        {
          $dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($record->linkedInventoryStrumag[0]->instituteKey));
          $strumagId = $record->linkedInventoryStrumag[0]->linkedStruMagToInventory->id;
          $s = $this->getStrumag($strumagId);

          $ps = json_decode($s->physicalSTRU);
          return array('firstImage' => metafad_teca_DAM_Common::replaceUrl($dam->streamUrl($ps->image[0]->id,'thumbnail')));
        }
    }
    else if($type == 'iccd'){
      //Ottengo il record, in particolare FTA in caso di scheda ICCD
      //Non invio stru logica in quanto non collego strumag alle iccd
      $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
      if($record->load($id)){
        $ar = org_glizy_objectFactory::createModel($record->document_type.'.models.Model');
        $ar->load($id);
        $dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($ar->getRawData()->instituteKey));
        $data = $ar->getRawData()->FTA;
        if ($data) {
          foreach ($data as $k => $v) {
            if($v->{"FTA-image"})
            {
              return array('firstImage'=> metafad_teca_DAM_Common::replaceUrl($dam->streamUrl(json_decode($v->{"FTA-image"})->id,'thumbnail')));
            }
          }
        }
      }
    }
    else if($type == 'archive')
    {
      $record = org_glizy_objectFactory::createModel('archivi.models.Model');
      if($record->load($id)){
        $dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($record->instituteKey));
        if($record->document_type == 'archivi.models.UnitaDocumentaria' || $record->document_type == 'archivi.models.UnitaArchivistica')
        {
          $record = $record->getRawData();
          if($record->linkedStruMag)
          {
            $strumagId = $record->linkedStruMag->id;
            $s = $this->getStrumag($strumagId);

            $ps = json_decode($s->physicalSTRU);
            return array('firstImage' => metafad_teca_DAM_Common::replaceUrl($dam->streamUrl($ps->image[0]->id,'thumbnail')));
          }
          else if($record->mediaCollegati)
          {
            return array('firstImage' => metafad_teca_DAM_Common::replaceUrl($dam->streamUrl(json_decode($record->mediaCollegati)->id,'thumbnail')));
          }
        }
      }
    }
    else {
      return array('error'=>'Il type indicato non ha corrispondenza');
    }
  }

  public function getImageFromStruMag($strumagId)
  {
      $s = $this->getStrumag($strumagId);
      $ps = json_decode($s->physicalSTRU);
      $ik = ($record->linkedStruMag->instituteKey) ? : $record->linkedStruMag['instituteKey'];
      $dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($ik));
      return array('firstImage' => metafad_teca_DAM_Common::replaceUrl($dam->streamUrl($ps->image[0]->id,'thumbnail')));
  }

  public function getStrumag($id)
  {
    $linkedStru = new stdClass();
    $stru = org_glizy_objectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model')
                 ->where('document_id',$id)->first();

    if($stru)
    {
      $stru->getRawData();
      $linkedStru->physicalSTRU = $stru->physicalSTRU;
      $linkedStru->logicalSTRU = $stru->logicalSTRU;
      return $linkedStru;
    }
    else
    {
      return array('error'=>'Il type indicato non ha corrispondenza');
    }
  }
}
