<?php
class metafad_teca_mets_helpers_MappingHelper extends GlizyObject
{
  private $docStruProxy;
  private $metsProxy;

  function __construct($docStruProxy)
  {
    $this->docStruProxy = $docStruProxy;
    $this->metsProxy = __ObjectFactory::createObject('metafad_teca_mets_models_proxy_MetsProxy');
  }

  public function getMapping($record)
  {
    $appoggioMets = new stdClass();
    
    //Recupero i dati del MODS per la sezione apposita in METS
    $modsToMetsData = $this->getMappedField($record);
    $appoggioMets->modsToMetsData = $modsToMetsData;

    //Collego la stru se presente al mets
    if ($record->linkedStruMag) {
      $appoggioMets->linkedStru = $this->getLinkedStruMag($record->linkedStruMag);
    } else if ($record->linkedMedia) {
      $images = array();
      foreach ($record->linkedMedia as $r) {
        $images[] = $r->media;
      }
      $appoggioMets->images = $images;
    }

    return $appoggioMets;
  }

  private function getMappedField($record)
  {
    $fieldsList = array(
      'identificativo-rep',
      'titolo', 'complementoTitolo', 'numeroParteTitolo',
      'nomeParte', 'autore-rep', 'luogo',
      'editore', 'date', 'lingua-rep',
      'materia-rep', 'tecnica-rep', 'tipoEstensione',
      'abstract', 'tavolaContenuti', 'soggetto-rep',
      'classificazione', 'titoloCollegato-rep', 'parte-rep',
      'localizzazione', 'collocazione-rep', 'compilatore',
      'dataCreazione', 'dataModifica'
    );
    $modsFields = array();
    foreach ($fieldsList as $f) {
      $value = $record->$f;
      if ($value && !empty($value)) {
        $modsFields[$f] = $value;
      }
    }
    return $modsFields;
  }

  public function createMets($mapping, $importMode, $linkedModel, $formId)
  {
    $StruMagProxy = __ObjectFactory::createObject('metafad.teca.STRUMAG.models.proxy.StruMagProxy');
    $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');

    //Rimozione duplicati
    $dc_identifier = ($mapping->modsToMetsData['identificativo-rep'][0]->identificativo) ? : null;
    if ($dc_identifier) {
      $it = org_glizy_objectFactory::createModelIterator('metafad.teca.mets.models.Model')
        ->where('identifier', $dc_identifier);
      if ($it->count() > 0) {
        foreach ($it as $ar) {
          $this->deleteMets($ar->document_id, 'metafad.teca.mets.models.Model');
        }
      }
    }

    //Dati sezione mods
    $mets = org_glizy_objectFactory::createModel('metafad.teca.mets.models.Model');
    $mets->mods = array();
    $mods = new stdClass();
    foreach ($mapping->modsToMetsData as $key => $value) {
      $mods->$key = $value;
    }
    $mets->mods = array($mods);
    
    //Date
    $date = new org_glizy_types_DateTime();

    //Identifier
    $mets->identifier = $dc_identifier;
    $mets->dc = array();
    $bib = new stdClass();
    $bibValue = new stdClass();
    $bibValue->BIB_dc_identifier_value = $dc_identifier;
    $bib->BIB_dc_identifier = array($bibValue);
    $mets->dc = array($bib);

    //Linked strumag
    if ($mapping->linkedStru) 
    {
      $mets->linkedStru = $mapping->linkedStru;
    }

    //Salvataggio
    $decodeData = $mets->getRawData();
    $decodeData->__commit = true;
    $decodeData->__model = 'metafad.teca.mets.models.Model';
    $result = $this->metsProxy->save($decodeData);
    $id = $result['id'];

    //Salvataggio eventuali immagini
    if ($mapping->images) {
      $this->docStruProxy->createPages($result['rootId'], $mapping->images, 'mets');
    }
  }

  public function deleteMets($id, $model)
  {
    if ($id) {
      $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
      $struMagProxy = __ObjectFactory::createObject('metafad.teca.STRUMAG.models.proxy.StruMagProxy');
      $rootNode = $this->docStruProxy->getRootNodeByDocumentId($id);
      $this->docStruProxy->deleteNode($rootNode->docstru_id);

      $tmp = $contentproxy->loadContent($id, $model);
      $idStruMag = json_decode($tmp['relatedStru'])->id;
      if ($idStruMag) {
        $doc = new stdClass();
        $doc->MAG = "";
        $struMagProxy->modify($idStruMag, $doc);
      }

      $contentproxy->delete($id, $model);

      $evt = array('type' => 'deleteRecord', 'data' => $id);
      $this->dispatchEvent($evt);
    }
  }

  public function getStrumag($id)
  {
    $linkedStru = new stdClass();
    $stru = org_glizy_objectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model')
      ->where('document_id', $id)->first();

    if ($stru) {
      $stru->getRawData();
      $linkedStru->physicalSTRU = $stru->physicalSTRU;
      $linkedStru->logicalSTRU = $stru->logicalSTRU;

      return $linkedStru;
    } else {
      return false;
    }
  }

  public function getLinkedStruMag($linkedStruMag)
  {
    $linkedStru = new stdClass();
    $linkedStru->id = $linkedStruMag->id;
    $linkedStru->text = $linkedStruMag->text;

    $stru = $this->getStrumag($linkedStru->id);
    if ($stru) {
      $this->images = json_decode($stru->physicalSTRU)->image;
    }

    return $linkedStru;
  }

}
