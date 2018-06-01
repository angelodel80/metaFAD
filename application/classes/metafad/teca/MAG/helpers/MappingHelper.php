<?php
class metafad_teca_MAG_helpers_MappingHelper extends GlizyObject
{
  private $docStruProxy;
  private $archiviProxy;
  private $uniqueIccdIdProxy;
  private $images;

  function __construct($docStruProxy)
  {
    $this->docStruProxy = $docStruProxy;
  }

  public function getMapping($record,$data,$model,$id = null)
  {
    $iccdModels = array('SchedaD300.models.Model','SchedaF400.models.Model','SchedaOA300.models.Model','SchedaS300.models.Model');
    $this->images = null;
    if($model == 'archivi.models.UnitaDocumentaria') {
      $this->archiviProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
      return $this->getMappingUD($record,$data);
    }
    else if($model == 'archivi.models.UnitaArchivistica') {
      $this->archiviProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
      return $this->getMappingUA($record,$data);
    }
    else if($model == 'metafad.sbn.modules.sbnunimarc.model.Model')
    {
      return $this->getMappingSBN($record,$data);
    }
    else if(in_array($model, $iccdModels))
    {
      return $this->getMappingICCD($record,$data,$model,$id);
    }
    else {
      return 'error';
    }
  }

  public function hasImages()
  {
    return ($this->images) ? true : false;
  }

  private function getMappingUD($record,$data)
  {
    $this->archiviProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
    $appoggioMag = new stdClass();

    $appoggioMag->BIB_level = 'd';
    $appoggioMag->BIB_dc_type = array();
    $appoggioMag->BIB_dc_type[] = array('BIB_dc_type_value'=>'documento d\'archivio');
    if($record->tipo){
      $appoggioMag->BIB_dc_type[] = array('BIB_dc_type_value'=>$record->tipo);
    }

    $appoggioMag->BIB_dc_creator = array();
    $appoggioMag->BIB_dc_contributor = array();
    if($record->autoreResponsabile){
      foreach ($record->autoreResponsabile as $a) {
        if($a->ruolo == 'firmatario' || $a->ruolo == 'destinatario' || $a->ruolo == 'firma') {
          $appoggioMag->BIB_dc_contributor[] = array('BIB_dc_contributor_value'=>$a->autore->text);
        }
        else {
          $appoggioMag->BIB_dc_creator[] = array('BIB_dc_creator_value'=>$a->autore->text);
        }
      }
    }

    if($record->contestoProvenienza_linguaTesto){
      $appoggioMag->BIB_dc_language = array(array('BIB_dc_language_value',$record->contestoProvenienza_linguaTesto));
    }

    $common = $this->getCommonDCUnit($record,$data,'UD');
    foreach ($common as $key => $value) {
      $appoggioMag->$key = $value;
    }

    return $appoggioMag;
  }

  private function getMappingUA($record,$data)
  {
    $this->archiviProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
    $appoggioMag = new stdClass();

    $appoggioMag->BIB_level = 'f';
    $appoggioMag->BIB_dc_type = array();
    $appoggioMag->BIB_dc_type[] = array('BIB_dc_type_value'=>'fascicolo');

    $common = $this->getCommonDCUnit($record,$data,'UA');
    foreach ($common as $key => $value) {
      $appoggioMag->$key = $value;
    }

    return $appoggioMag;
  }

  private function getMappingICCD($record,$data,$model,$id)
  {
    $this->uniqueIccdIdProxy = __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy');
    $appoggioMag = new stdClass();

    $common = $this->getCommonDCIccd($record,$data,$model,$id);
    foreach ($common as $key => $value) {
      $appoggioMag->$key = $value;
    }

    if($model == 'SchedaD300.models.Model')
    {
      $vals = $this->getElements($record,'SGT','SGTI');
      $appoggioMag->BIB_dc_title = $this->getMultiple($vals,'BIB_dc_title');
      if(empty($appoggioMag->BIB_dc_title))
      {
        $vals = $this->getElements($record,'SGT','SGTT');
        $appoggioMag->BIB_dc_title = $this->getMultiple($vals,'BIB_dc_title');
        if(empty($appoggioMag->BIB_dc_title))
        {
          $appoggioMag->BIB_dc_title = $this->getString($record->OGTN,'BIB_dc_title');

        }
      }
      return $appoggioMag;
    }
    else if($model == 'SchedaF400.models.Model')
    {
      //PUBLISHER
      if($record->PD)
      {
        $publisher = array();
        $pd = $record->PD[0];
        foreach ($pd->PDF as $pdf) {
          $pdfl = ($pdf->PDFL)? $pdf->PDFL.': ' : '';
          $pdfn = $pdf->PDFN;
          $pdfd = ($pdf->PDFD) ? ', '.$pdf->PDFD : '';
          $publisher[] = $pdfl . $pdfn . $pdfd;
        }
        $appoggioMag->BIB_dc_publisher = $this->getMultiple($publisher,'BIB_dc_publisher');
      }

      //DATE
      if($record->DT)
      {
        $date = array();
        foreach ($record->DT as $dt) {
          if($dt->DTS)
          {
            foreach ($dt->DTS as $dts) {
              $date[] = $dts->DTSI;
              $date[] = $dts->DTSF;
            }
          }
        }
        if(!empty($date))
        {
          $appoggioMag->BIB_dc_date = $this->getMultiple($date,'BIB_dc_date');
        }
      }

      $vals = $this->getElements($record, 'SGL', 'SGLT');
      $appoggioMag->BIB_dc_title = $this->getMultiple($vals, 'BIB_dc_title');
      if(empty($appoggioMag->BIB_dc_title)) 
      {
        $vals = $this->getElements($record, 'SGT', 'SGLA');
        $appoggioMag->BIB_dc_title = $this->getMultiple($vals, 'BIB_dc_title');
      }
      if (empty($appoggioMag->BIB_dc_title)) 
      {
        $appoggio = array();
        $c = new stdClass();
        $c->BIB_dc_title_value = $id;
        $appoggio[] = $c;

        $appoggioMag->BIB_dc_title = $appoggio;
      }
      return $appoggioMag;
    }
    else if($model == 'SchedaOA300.models.Model')
    {
      $vals = $this->getElements($record,'SGT','SGTI');
      $appoggioMag->BIB_dc_title = $this->getMultiple($vals,'BIB_dc_title');
      if(empty($appoggioMag->BIB_dc_title))
      {
        $vals = $this->getElements($record,'SGT','SGTT');
        $appoggioMag->BIB_dc_title = $this->getMultiple($vals,'BIB_dc_title');
        if(empty($appoggioMag->BIB_dc_title))
        {
          $appoggioMag->BIB_dc_title = $this->getString($record->OGTN,'BIB_dc_title');

        }
      }
      return $appoggioMag;
    }
    else if($model == 'SchedaS300.models.Model')
    {
      $vals = $this->getElements($record,'SGT','SGTI');
      $appoggioMag->BIB_dc_title = $this->getMultiple($vals,'BIB_dc_title');
      if(empty($appoggioMag->BIB_dc_title))
      {
        $vals = $this->getElements($record,'SGT','SGTT');
        $appoggioMag->BIB_dc_title = $this->getMultiple($vals,'BIB_dc_title');
      }

      //PUBLISHER
      if($record->EDTL)
      {
        $publisher = array();
        foreach ($record->EDT as $edt) {
          $edtl = ($edt->EDTL)? $edt->EDTL.': ' : '';
          $edtn = $edt->EDTN;
          $edte = ($edt->EDTE) ? ', '.$edt->EDTE : '';
          $publisher[] = $edtl . $edtn . $edte;
        }
        $appoggioMag->BIB_dc_publisher = $this->getMultiple($publisher,'BIB_dc_publisher');
      }
      return $appoggioMag;
    }
    return 'error';
  }

  private function getElements($record,$field,$subfield)
  {
    $vals = array();
    if($record->$field)
    {
      foreach ($record->$field as $f) {
        if($f->$subfield)
        {
          if(is_string($f->$subfield))
          {
            $vals[] = $f->$subfield;
          }
          else {
            foreach ($f->$subfield as $el) {
              $vals[] = $el->{$subfield.'-element'};
            }
          }
        }
      }
    }
    return $vals;
  }

  private function getElementsSingleField($record,$field)
  {
    $vals = array();
    if($record->$field)
    {
      foreach ($record->$field as $f) {
          $vals[] = $f->{$field.'-element'};
      }
    }
    return $vals;
  }

  private function getCommonDCIccd($record, $data, $type, $id)
  {
    $appoggioMag = new stdClass();
    if(!empty($data))
    {
      $appoggioMag->GEN_stprog = $data['GEN_stprog'];
      $appoggioMag->GEN_collection = $data['GEN_collection'];
      $appoggioMag->GEN_agency = $data['GEN_agency'];
      $appoggioMag->GEN_access_rights = $data['GEN_access_rights'];
      $appoggioMag->GEN_completeness = $data['GEN_completeness'];
    }

    $appoggioMag->BIB_level = 'm';

    $imgs = $record->FTA;
    if($imgs)
    {
      foreach ($imgs as $i) {
        $this->images[] = $i->{'FTA-image'};
      }
    }

    $record->__model = $type;
    $uniqueIccdId = $this->uniqueIccdIdProxy->createUniqueIccdId($record);
    $appoggioMag->BIB_dc_identifier = $this->getString($uniqueIccdId,'BIB_dc_identifier');
    $appoggioMag->BIB_dc_identifier_index = $uniqueIccdId;

    $appoggioMag->BIB_dc_creator = $this->getMultiple($this->getIccdCreator($record,$type),'BIB_dc_creator');

    $appoggioMag->BIB_dc_subject = $this->getMultiple($this->getIccdSubject($record,$type),'BIB_dc_subject');

    $appoggioMag->BIB_dc_description = $this->getMultiple($this->getIccdDescription($record,$type),'BIB_dc_description');

    $typeTSK = ($record->TSK == 'OA') ? 'opera d\'arte' : 'materiale grafico' ;
    $appoggioMag->BIB_dc_type = $this->getString($typeTSK,'BIB_dc_type');

    $appoggioMag->BIB_dc_format = $this->getMultiple($this->getIccdFormat($record,$type),'BIB_dc_format');

    $appoggioMag->BIB_holdings = array(
      array('BIB_holdings_library' => $record->instituteKey,
            'BIB_holdings_inventory_number' => $this->getIccdInventory($record,$type),
            'BIB_holdings_shelfmark'=>array(array('BIB_holdings_shelfmark_value'=>$this->getIccdShelfmark($record,$type))))
    );

    $appoggioMag->BIB_dc_relation = $this->getMultiple($this->getIccdRelation($record,$type,$id),'BIB_dc_relation');

    return $appoggioMag;
  }

  private function getCommonDCUnit($record,$data,$type)
  {
    $this->archiviProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
    $appoggioMag = new stdClass();

    if(!empty($data))
    {
      $appoggioMag->GEN_stprog = $data['GEN_stprog'];
      $appoggioMag->GEN_collection = $data['GEN_collection'];
      $appoggioMag->GEN_agency = $data['GEN_agency'];
      $appoggioMag->GEN_access_rights = $data['GEN_access_rights'];
      $appoggioMag->GEN_completeness = $data['GEN_completeness'];
    }

    if($record->linkedStruMag){
      $appoggioMag->linkedStru = $this->getLinkedStruMag($record->linkedStruMag);
    }
    else if($record->mediaCollegati){
      $this->images = array($record->mediaCollegati);
    }

    if($record->identificativo){
      $appoggioMag->BIB_dc_identifier = $this->getString($record->identificativo,'BIB_dc_identifier');
      $appoggioMag->BIB_dc_identifier_index = $record->identificativo;
    }

    $parent = $record->parent->text;
    $appoggioMag->BIB_dc_relation = array(array('BIB_dc_relation_value'=>'fa parte di: '.$parent));

    if($record->segnaturaAttuale){
      $appoggioMag->BIB_holdings = array(array('BIB_holdings_shelfmark'=>array(array('BIB_holdings_shelfmark_value'=>$record->segnaturaAttuale))));
    }
    if($record->denominazione){
      $appoggioMag->BIB_dc_title = $this->getString($record->denominazione,'BIB_dc_title');
    }

    $appoggioMag->BIB_dc_date = array();
    if($record->cronologia){
      foreach ($record->cronologia as $c) {
        $appoggioMag->BIB_dc_date[] = array('BIB_dc_date_value'=>$c->estremoCronologicoTestuale);
      }
    }

    if($record->descrizioneFisica_tipologia && $record->visualizzazioneConsistenza){
      $appoggioMag->BIB_dc_format = array(array('BIB_dc_value_format'=>$record->descrizioneFisica_tipologia . ' ' . $record->visualizzazioneConsistenza));
    }

    if($type == 'UA')
    {
      $appoggioMag->BIB_dc_description = array();
      if($record->descrizioneContenuto){
        $appoggioMag->BIB_dc_description[] = array('BIB_dc_description_value'=>$record->descrizioneContenuto);
      }
    }
    else {
      $appoggioMag->BIB_dc_description = array();
      if($record->contestoProvenienza_descrizione){
        $appoggioMag->BIB_dc_description[] = array('BIB_dc_description_value'=>$record->contestoProvenienza_descrizione);
      }
    }
    $antroponimi = $this->getDescriptionUnit($record,'Persone citate: ','antroponimi');
    if($antroponimi){
      $appoggioMag->BIB_dc_description[] = array('BIB_dc_description_value'=>$this->getDescriptionUnit($record,'Persone citate: ','antroponimi'));
    }
    $enti = $this->getDescriptionUnit($record,'Enti citati: ','enti');
    if($enti){
      $appoggioMag->BIB_dc_description[] = array('BIB_dc_description_value'=>$enti);
    }
    $toponimi = $this->getDescriptionUnit($record,'Toponimi: ','toponimi');
    if($toponimi){
      $appoggioMag->BIB_dc_description[] = array('BIB_dc_description_value'=>$toponimi);
    }

    return $appoggioMag;
  }

  private function getMappingSBN($record,$data)
  {
    $appoggioMag = new stdClass();
    $helper = org_glizy_objectFactory::createObject('metafad.sbn.modules.sbnunimarc.model.proxy.SbnToMagProxy');
    $sbnToMagData = $helper->getMappedField($record->id);
    //Collego la stru al mag
    if($record->linkedStruMag){
      $appoggioMag->linkedStru = $this->getLinkedStruMag($record->linkedStruMag);
    }
    else if($record->linkedInventoryStrumag){
      $linkedStru = new stdClass();
      $linkedStru->id = $record->linkedInventoryStrumag[0]->linkedStruMagToInventory->id;
      $linkedStru->text = $record->linkedInventoryStrumag[0]->linkedStruMagToInventory->text;
      $appoggioMag->linkedStru = $linkedStru;

      $stru = $this->getStrumag($linkedStru->id);
      if($stru)
      {
        $this->images = json_decode($stru->physicalSTRU)->image;
      }
    }
    else if($record->linkedMedia)
    {
      $images = array();
      foreach ($record->linkedMedia as $r) {
        $images[] = $r->media;
      }
      $this->images = $images;
    }
    else if($record->linkedInventoryMedia)
    {
      $images = array();
      foreach ($record->linkedInventoryMedia[0]->media as $r) {
        $images[] = $r->mediaInventory;
      }
      $this->images = $images;
    }

    $appoggioMag->GEN_stprog = $data['GEN_stprog'];
    $appoggioMag->GEN_collection = $data['GEN_collection'];
    $appoggioMag->GEN_agency = $data['GEN_agency'];
    $appoggioMag->GEN_access_rights = $data['GEN_access_rights'];
    $appoggioMag->GEN_completeness = $data['GEN_completeness'];

    if($sbnToMagData)
    {
      foreach ($sbnToMagData as $key => $value) {
        if($key == 'BIB_level')
        {
          $appoggioMag->BIB_level = $value;
        }
        else if($key == 'BIB_dc_identifier')
        {
          $appoggioMag->BIB_dc_identifier_index = $value[0];
        }
        if(!is_string($value))
        {
          $appoggioMag->$key = array();
          foreach ($value as $v) {
            array_push($appoggioMag->$key, $this->getObject($v,$key));
          }
        }
      }
      return $appoggioMag;
    }
    else {
      return null;
    }
  }

  private function getString($string,$fieldName)
  {
    $c = new stdClass();
    $c->{$fieldName.'_value'} = $string;
    return array($c);
  }

  private function getMultiple($array,$fieldName)
  {
    $appoggio = array();
    foreach ($array as $v) {
      $c = new stdClass();
      $c->{$fieldName.'_value'} = $v;
      $appoggio[] = $c;
    }
    return $appoggio;
  }

  private function getObject($string,$fieldName)
  {
    $c = new stdClass();
    $c->{$fieldName.'_value'} = $string;
    return $c;
  }

  private function getIccdRelation($record,$type,$id)
  {
    $level = $record->RV[0]->RVE[0]->RVEL;
    $relation = org_glizy_ObjectFactory::createModelIterator( 'metafad.gestioneDati.boards.models.ComplexRelations' )
                ->where('complex_relation_FK_document_id',$id)->first();
    if($level == 0) {
      $it = org_glizy_ObjectFactory::createModelIterator( 'metafad.gestioneDati.boards.models.ComplexRelations' )
            ->where('complex_relation_rver',$relation->complex_relation_rver)
            ->where('complex_relation_level', '0','<>');
      $relArray = array();
      foreach ($it as $ar) {
        $title = $this->getIccdTitle($ar->complex_relation_FK_document_id,$type);
        if($title)
        {
          $relArray[] = 'Comprende: '.$title.' {'.$relation->complex_relation_rver.'-'.$ar->complex_relation_level.'}';
        }
      }
    }
    else {
      $it = org_glizy_ObjectFactory::createModelIterator( 'metafad.gestioneDati.boards.models.ComplexRelations' )
            ->where('complex_relation_rver',$relation->complex_relation_rver)
            ->where('complex_relation_level', '0')->first();
      $relArray = array();
      $title = $this->getIccdTitle($it->complex_relation_FK_document_id,$type);
      if($title)
      {
        $relArray[] = 'Fa parte di: '.$title.' {'.$relation->complex_relation_rver.'-0}';
      }
    }
    return $relArray;
  }

  private function getIccdTitle($id,$model)
  {
    $searchQuery = array();

    $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',
        __Config::get('metafad.solr.url') . 'select?q=id:'.$id.'&wt=json',
        'POST',
        '',
        'application/json');
    $request->setTimeout(1000);
    $request->setAcceptType('application/json');
    $request->execute();
    $response = json_decode($request->getResponseBody())->response->docs;
    if($response)
    {
      return $response[0]->soggetto_identificazione_txt[0];
    }
    else {
      return null;
    }
  }

  private function getIccdInventory($record,$type)
  {
    $inventory = '';
    if($type == 'SchedaF400.models.Model')
    {
      if($record->INV)
      {
        foreach ($record->INV as $i) {
          $inventory .= $i->INVN . ' ';
        }
      }
    }
    else
    {
      if($record->UB)
      {
        foreach ($record->UB as $u) {
          foreach ($u->INV as $i) {
            $inventory .= $i->INVN . ' ';
          }
        }
      }
    }
    return $inventory;
  }

  private function getIccdShelfmark($record,$type)
  {
    $shelfmark = '';
    if($type == 'SchedaF400.models.Model')
    {
      $shelfmark = $record->UBFC;
    }
    else
    {
      if($record->UB)
      {
        foreach ($record->UB as $u) {
          foreach ($u->INV as $i) {
            $shelfmark .= $i->INVC . ' ';
          }
        }
      }
    }
    return $shelfmark;
  }

  private function getIccdFormat($record,$type)
  {
    $format = array();
    if($type == 'SchedaOA300.models.Model')
    {
      if($record->MIS)
      {
        foreach ($record->MIS as $mis) {
          $misu = ($mis->MISU) ?:'';
          $misal = (!$mis->MISA || !$mis->MISL) ? '' : $mis->MISA . 'x' . $mis->MISL . ' ';
          if($misal || $misu)
          {
            $format[] = $misal . $misu;
          }
        }
      }
    }
    else if($type == 'SchedaF400.models.Model')
    {
      if($record->MIS)
      {
        foreach ($record->MIS as $mis) {
          $mism = ($mis->MISM) ?:'';
          if($mis->MISU)
          {
            $misuAr = $this->getElements($record,'MIS','MISU');
            $misu = '';
            foreach ($misuAr as $m) {
              $misu .= $m . ' ';
            }
          }
          if($mism || $misu){
            $format[] = $mism . $misu;
          }
        }
      }
    }
    else
    {
      $misu = ($record->MISU) ?:'';
      $misal = (!$record->MISA || !$record->MISL) ? '' : $record->MISA . 'x' . $record->MISL . ' ';
      if($misal || $misu)
      {
        $format[] = $misal . $misu;
      }
    }
    //MCT
    if($type == 'SchedaF400.models.Model')
    {

      if($record->MTC)
      {
        foreach ($record->MTC as $mtc) {
          $mtcString = '';
          $mtctString = '';
          $mtcString .= $mtc->MTCM;
          $mtct = $this->getElementsSingleField($mtc,'MTCT');
          if($mtct)
          {
            $mtctString .= ' (';
            foreach ($mtct as $v) {
              $mtctString .= $v .' / ';
            }
            $mtctString = rtrim($mtctString,' / ');
            $mtctString .= ')';
          }
          $format[] = $mtcString . $mtctString;
        }
      }
    }
    else
    {
      $mtc = $this->getElementsSingleField($record,'MTC');
      if($mtc)
      {
        $mtcString = '';
        foreach ($mtc as $m) {
          $mtcString .= $m .' / ';
        }
        $format[] = rtrim($mtcString,' / ');
      }
    }
    return $format;
  }

  private function getIccdDescription($record,$type)
  {
    $description = array();
    if($type == 'SchedaF400.models.Model')
    {
      if($record->SGTD)
      {
        $description[] = $record->SGTD;
      }
      if($record->DA)
      {
        foreach ($record->DA as $da) {
          $description[] = 'Notizie storico critiche: '.$da->NSC;
        }
      }
    }
    else
    {
      $description = $this->getElementsSingleField($record,'DESS');
      if($type != 'SchedaS300.models.Model')
      {
        if($record->DESO)
        {
          $description[] = $record->DESO;
        }
      }
    }

    if($record->NSC)
    {
      $description[] = 'Notizie storico critiche: '.$record->NSC;
    }

    return $description;
  }

  private function getIccdSubject($record,$type)
  {
    $subject = array();
    if($type == 'SchedaF400.models.Model')
    {
      if($record->SGTI)
      {
        $subject = $this->getElementsSingleField($record,'SGTI');
      }
    }
    if($record->OGTD)
    {
      $subject[] = $record->OGTD;
    }

    return $subject;
  }

  private function getIccdCreator($record,$type)
  {
    $creator = array();
    if($record->AUT)
    {
      foreach ($record->AUT as $aut) {
        $autId = $aut->__AUT->id;
        $ar = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
        if($ar->load($autId))
        {
          $autData = $ar->getRawData();
          $creator[] = ($aut->AUTR) ? $autData->AUTN . ' ['.$aut->AUTR.']' : $autData->AUTN;
        }
      }
    }
    if($record->ATB)
    {
      foreach ($record->ATB as $atb) {
        $creator[] = ($atb->ATBR) ? $atb->ATBD . ' [' .$atb->ATBR. ']': $atb->ATBD;
      }
    }
    if($type == 'SchedaF400.models.Model')
    {
      if($record->AAT)
      {
        foreach ($record->AAT as $aat) {
          $creator[] = $aat->AATN;
        }
      }
    }
    else
    {
      if($record->AAT)
      {
        $aat = $this->getElementsSingleField($record,'AAT');
        foreach ($aat as $a) {
          $creator[] = $a;
        }
      }
    }
    return $creator;
  }

  private function getDescriptionUnit($record,$text,$field)
  {
    $description = $text;
    if($record->$field)
    {
      foreach ($record->$field as $a){
        $description .= $a->intestazione->text . '; ';
      }
      return $description;
    }
    else{
      return null;
    }
  }

  public function createMag($mapping,$importMode,$linkedModel,$formId,$createImgGroup = false)
  {
    $StruMagProxy = __ObjectFactory::createObject('metafad.teca.STRUMAG.models.proxy.StruMagProxy');
    $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');

    if($importMode == 'substitute')
    {
      $dc_identifier = ($mapping->BIB_dc_identifier[0]->BIB_dc_identifier_value) ?: null;
      if($dc_identifier)
      {
        $it = org_glizy_objectFactory::createModelIterator('metafad.teca.MAG.models.Model')
               ->where('BIB_dc_identifier_index',$dc_identifier);
        if($it->count() > 0)
        {
          foreach ($it as $ar) {
            $this->deleteMag($ar->document_id,'metafad.teca.MAG.models.Model');
          }
        }
      }
    }

    $mag = org_glizy_objectFactory::createModel('metafad.teca.MAG.models.Model');
    foreach ($mapping as $key => $value) {
      $mag->$key = $value;
    }
    $date = new org_glizy_types_DateTime();
    $mag->GEN_creation = $date->date;
    $mag->GEN_lastUpdate =  $date->date;

    $arrayModules = array(
      'metafad.sbn.modules.sbnunimarc' => 'metafad.sbn.modules.sbnunimarc',
      'metafad.sbn.modules.sbnunimarc.model.Model' => 'metafad.sbn.modules.sbnunimarc',
      'SchedaF400.models.Model' => 'SchedaF400',
      'SchedaS300.models.Model' => 'SchedaS300',
      'SchedaOA300.models.Model' => 'SchedaOA300',
      'SchedaD300.models.Model' => 'SchedaD300',
      'archivi.models.UnitaArchivistica' => 'archivi.models.UnitaArchivistica',
      'archivi.models.UnitaDocumentaria' => 'archivi.models.UnitaDocumentaria'
    );

    if(array_key_exists($linkedModel, $arrayModules))
    {
      $mag->linkedFormType = $arrayModules[$linkedModel];
    }

    $linkedForm = array(
      'id' => $formId,
      'text' => $mapping->BIB_dc_title[0]->BIB_dc_title_value
    );
    $mag->linkedForm = $linkedForm;

    $mag->BIB_dc_identifier_index = $mapping->BIB_dc_identifier[0]->BIB_dc_identifier_value;
    if(!$mag->BIB_dc_identifier_index)
    {
      return;
    }
    if(is_object($mag->BIB_dc_identifier_index))
    {
      $mag->BIB_dc_identifier_index = $mag->BIB_dc_identifier_index->BIB_dc_identifier_value;
    }
    //Creazione imggroup di default
    if($createImgGroup)
    {
      if($this->images)
      {
        $imageForGroup = current($this->images);
        $mag->GEN_img_group = $this->createImgGroup($imageForGroup);
      }
    }

    $id = $mag->save(null,false,'PUBLISHED');

    // if($decodeData->linkedStru)
    // {
    //   $decodeData->linkedStru = array("id"=>$decodeData->linkedStru->id,"text"=>$decodeData->linkedStru->text);
    // }

    $decodeData = $mag->getRawData();

    $cl = new stdClass();
    $cl->className = 'metafad.teca.MAG.models.Model';
    $cl->isVisible = true;
    $cl->isTranslated = false;
    $cl->hasPublishedVersion = true;
    $cl->hasDraftVersion = false;
    $cl->document_detail_status = 'PUBLISHED';

    $decodeData->document = json_encode($cl);

    $decodeData->__commit = true;
    $decodeData->__id = $id;
    $decodeData->__model = 'metafad.teca.MAG.models.Model';

    $evt = array('type' => 'insertRecord', 'data' => array('data' => $decodeData, 'option' => array('commit' => true)));
    $this->dispatchEvent($evt);

    //Salvo docstru
    $title = ($decodeData->title) ? : 'Senza titolo' ;
    $rootId = $this->docStruProxy->saveNewRoot($id,$title);

    if($this->images)
    {
      $this->docStruProxy->createPages($rootId,$this->images);
    }
  }

  public function deleteMag($id, $model)
  {
    if ($id) {
        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $struMagProxy = __ObjectFactory::createObject('metafad.teca.STRUMAG.models.proxy.StruMagProxy');
        //Mi occupo anche della cancellazione dalla tabella docstru_tbl e dei figli generati nella pubblicazione
        $relationProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.MagRelationProxy');
        $rootNode = $this->docStruProxy->getRootNodeByDocumentId($id);
        $this->docStruProxy->deleteNode($rootNode->docstru_id);

        $tmp = $contentproxy->loadContent($id, $model);
        $idStruMag = json_decode($tmp['relatedStru'])->id;
        if($idStruMag){
            $doc = new stdClass();
            $doc->MAG = "";
            $struMagProxy->modify($idStruMag, $doc);
        }

        $relationProxy->deleteRelation(null,$id);
        $contentproxy->delete($id, $model);

        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);
    }
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
      return false;
    }
  }

  public function getLinkedStruMag($linkedStruMag)
  {
    $linkedStru = new stdClass();
    $linkedStru->id = $linkedStruMag->id;
    $linkedStru->text = $linkedStruMag->text;

    $stru = $this->getStrumag($linkedStru->id);
    if($stru)
    {
      $this->images = json_decode($stru->physicalSTRU)->image;
    }

    return $linkedStru;
  }

  public function createImgGroup($imageForGroup)
  {
    $mediaId = $imageForGroup->id;

    $imgGroup = array();

    $imgGroupIds = array('M','S');
    foreach($imgGroupIds as $id)
    {
      $o = new stdClass();
      $o->GEN_img_group_ID = $id;
      if($id == 'M')
      {
        $niso = $this->getNisoForGroups($mediaId);
        if($niso)
        {
          $o->GEN_img_group_image_metrics_samplingfrequencyunit = $niso->sampling_frequency_unit;
          $o->GEN_img_group_image_metrics_samplingfrequencyplane = $niso->sampling_frequency_plane;
          $o->GEN_img_group_image_metrics_bitpersample = $niso->bit_per_sample;
          $o->GEN_img_group_image_metrics_photometricinterpretation = $niso->photometric_interpretation;
          $o->GEN_img_group_format_name = $niso->name;
          $o->GEN_img_group_format_mime = $niso->mime;
          $o->GEN_img_group_format_compression = $niso->compression;
          $o->GEN_img_group_scanning_sourcetype = $niso->source_type;
          $o->GEN_img_group_scanning_scanningagency = $niso->scanning_agency;
          $o->GEN_img_group_scanning_devicesource = $niso->device_source;
          $o->GEN_img_group_scanning_scanningsystem_scanner_manufacturer = $niso->scanner_manufacturer;
          $o->GEN_img_group_scanning_scanningsystem_scanner_model = $niso->scanner_model;
          $o->GEN_img_group_scanning_scanningsystem_ = $niso->capture_software;
        }
      }
      if($id == 'S')
      {
        $niso = $this->getNisoForGroups($mediaId, true);
        if ($niso) {
          $o->GEN_img_group_image_metrics_samplingfrequencyunit = $niso->sampling_frequency_unit;
          $o->GEN_img_group_image_metrics_samplingfrequencyplane = $niso->sampling_frequency_plane;
          $o->GEN_img_group_image_metrics_bitpersample = $niso->bit_per_sample;
          $o->GEN_img_group_image_metrics_photometricinterpretation = $niso->photometric_interpretation;
          $o->GEN_img_group_format_name = $niso->name;
          $o->GEN_img_group_format_mime = $niso->mime;
          $o->GEN_img_group_format_compression = $niso->compression;
          $o->GEN_img_group_scanning_sourcetype = $niso->source_type;
          $o->GEN_img_group_scanning_scanningagency = $niso->scanning_agency;
          $o->GEN_img_group_scanning_devicesource = $niso->device_source;
          $o->GEN_img_group_scanning_scanningsystem_scanner_manufacturer = $niso->scanner_manufacturer;
          $o->GEN_img_group_scanning_scanningsystem_scanner_model = $niso->scanner_model;
          $o->GEN_img_group_scanning_scanningsystem_ = $niso->capture_software;
        }
      }
      $imgGroup[] = $o;
    }

    return $imgGroup;
  }

  public function getNisoForGroups($mediaId, $resize = false)
  {
    $damService = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');
    $docstruProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.DocStruProxy');

    $url = $damService->mediaUrl($mediaId) . '?bytestream=true';
    $bytestream = json_decode(file_get_contents($url));
    foreach ($bytestream->bytestream as $b) {
      if ($b->name == 'original') {
        $original = $b;
        break;
      }
    }
    if ($original && !$resize) {
      $nisoUrl = $damService->mediaUrl($mediaId) . '/bytestream/' . $original->id . '/datastream/NisoImg';
      $niso = json_decode(file_get_contents($nisoUrl));

      if (!$niso->NisoImg->id) {
        $exifUrl = $damService->mediaUrl($mediaId) . '/bytestream/' . $original->id . '/datastream/Exif';
        $exif = json_decode(file_get_contents($exifUrl));
        $niso = $docstruProxy->getNisoFromExif($exif);
      }
    }
    else if($resize)
    {
      $nisoResize = file_get_contents($damService->resizeInfoLocal($mediaId, 'original', __Config::get('gruppometa.dam.resizeStreamS')));
      if (!$nisoResize->NisoImg) {
        $niso = $docstruProxy->getNisoFromExif($nisoResize->Exif);
      } else {
        $niso = $nisoResize->NisoImg;
      }
    }
    
    if ($niso) {
      $niso = $niso->NisoImg;
    }

    return $niso;
  }

}
