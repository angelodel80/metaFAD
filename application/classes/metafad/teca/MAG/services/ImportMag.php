<?php
class metafad_teca_MAG_services_ImportMag extends GlizyObject
{
    //Array di appoggio per la creazione della strumag
    private $fileSequence = array(
      'img' => array(),
      'audio' => array(),
      'video' => array(),
      'doc' => array(),
      'ocr' => array(),
      'dis' => array()
    );

    private $arrayModules = array(
      'metafad.sbn.modules.sbnunimarc' => 'metafad.sbn.modules.sbnunimarc',
      'metafad.sbn.modules.sbnunimarc.model.Model' => 'metafad.sbn.modules.sbnunimarc',
      'SchedaF400.models.Model' => 'SchedaF400',
      'SchedaS300.models.Model' => 'SchedaS300',
      'SchedaOA300.models.Model' => 'SchedaOA300',
      'SchedaD300.models.Model' => 'SchedaD300',
      'archivi.models.UnitaArchivistica' => 'archivi.models.UnitaArchivistica',
      'archivi.models.UnitaDocumentaria' => 'archivi.models.UnitaDocumentaria'
    );

    private $stru;

    private $imageForContainer;

    /** var metafad_teca_MAG_models_proxy_DocStruProxyInterface */
    private $docStruProxy;
    /** var metafad_teca_MAG_services_ImportMediaInterface */
    private $dam;
    /** var metafad_teca_MAG_services_EventInterface */
    private $event;

    private $formType;
    private $importMode;
    private $identifier;
    private $linkedStru;
    private $inventory;
    private $priorityMedia;

    public function __construct(
        metafad_teca_MAG_models_proxy_DocStruProxyInterface $docStruProxy,
        metafad_teca_DAM_services_ImportMediaInterface $dam,
        metafad_teca_MAG_services_EventInterface $event)
    {
      $this->docStruProxy = $docStruProxy;
      $this->dam = $dam;
      $this->event = $event;
    }

    public function importFolder($folder, $imageSizePriority=array('M', 'S'), $checkExist=false, $getInventoryFromName=false, $overwrite=false)
    {
        $importedFiles = 0;
        foreach ($folder as $file) {
            $importFile = $this->importFile($file, $this->docStruProxy, $imageSizePriority, $checkExist, $getInventoryFromName, $overwrite);                        
            if($importFile === 'XMLError')
            {
              return $importFile;
            }
            else if ($importFile) {
                $importedFiles++;
                if($this->formType && sizeof($this->fileSequence['img'] >= 1))
                {
                  $this->linkMedia($this->formType);
                }
            }
        }
        return $importedFiles;
    }


    public function importFile($file, $docStruProxy, $imageSizePriority, $checkExist,$getInventoryFromName=false, $overwrite=false)
    {
        $xml = new DomDocument();

        error_reporting(E_ERROR | E_PARSE);
        $load = $xml->load($file);
        if(!$load){
          return 'XMLError';
        }
        error_reporting(E_ERROR | E_PARSE | E_WARNING);

        $rootId = $this->createRootNode($xml, $docStruProxy, $checkExist, $getInventoryFromName,$file, $overwrite);
        if (!$rootId) {
          // mag già importato
          return false;
        }

        try {
          $this->createPages($xml, $rootId['idDocStru'], $docStruProxy, pathinfo($file, PATHINFO_DIRNAME), pathinfo($file, PATHINFO_FILENAME), $imageSizePriority);
          $this->createStruMag($file,$rootId['idMag']);
        } catch (MyException $e) {
            $application = org_glizy_ObjectValues::get('org.glizy', 'application' );
            $application->executeCommand('metafad.teca.MAG.controllers.Delete', $rootId['idMag'], 'metafad.teca.MAG.models.Model', false );
        }

        return true;
    }

    private function createRootNode($xml, $docStruProxy, $checkExist,$getInventoryFromName=false,$file=null,$overwrite=false)
    {
        //lettura GEN e BIB (esclusi groups dal GEN)
        $data = $this->readRootData($xml,$getInventoryFromName,$file);
        $this->identifier = $data->BIB_dc_identifier_index;

        if ($checkExist || $overwrite) {
            $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Model');
            $it->where('BIB_dc_identifier_index', $data->BIB_dc_identifier_index, '=');
        }

        if ($overwrite) {
            if ($it->count()) {
                $ar = $it->first();
                $magProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.MagProxy');
                $magProxy->delete($ar->getId(), 'metafad.teca.STRUMAG.models.Model');
            }
        } else if ($checkExist) {
            if ($it->count()) {
                return false;
            }
        }

        //Lettura groups in GEN
        $data->GEN_img_group = $this->readImgGroups($xml);
        $data->GEN_audio_group = $this->readAudioGroups($xml);
        $data->GEN_video_group = $this->readVideoGroups($xml);

        $title = $data->BIB_dc_title[0]->BIB_dc_title_value;
        $data->id = $docStruProxy->createNewRootNode($title);
        $data->docstru_title = ($title) ?:'Senza titolo';
        $data->documentSubType = 'mag';

        //TODO CREAZIONE RECORD STRUMAG
        //la stru può avere una definizione o no nell'xml
        //se non ce l'ha è necessario creare una strumag semplice
        //(corrispondenza cartella -> una immagine)
        //$data->stru

        // cerca il record collegato
        //$data->docstru_refInfo = $this->linkRecord($data->dc_identifier);

        $__id = $docStruProxy->saveContentForNode($data);

        $decodeData = $data;

        //SALVARE ANCHE IN SOLR
        $decodeData->__id = $__id;
        $decodeData->__model = 'metafad.teca.MAG.models.Model';
        
        $this->appendDocumentToData($decodeData);

        $decodeData->__commit = true;
        
        $this->event->insert($decodeData);

        return array('idDocStru'=>$data->id,'idMag'=>$__id);
    }

    public function appendDocumentToData($data, $ar=null)
    {
        if ($data->__id) {
            if (!$ar) {
                $ar = __ObjectFactory::createModel($data->__model);
                $ar->load($data->__id, 'PUBLISHED_DRAFT');
            }

            $cl = new stdClass();
            if ($data->livelloDiDescrizione) {
                $cl->sectionType = $data->livelloDiDescrizione;
            }
            $cl->className = $data->__model;
            $cl->isVisible = $ar->isVisible();
            $cl->isTranslated = $ar->isTranslated();
            $cl->hasPublishedVersion = $ar->hasPublishedVersion();
            $cl->hasDraftVersion = $ar->hasDraftVersion();
            $cl->document_detail_status = $ar->getStatus();
            
            $data->document = json_encode($cl);
        }
    }

    private function createPages($xml, $rootId, $docStruProxy, $dirName, $magName, $imageSizePriority)
    {
        //Creo il container per le immagini
        $this->imageForContainer['addMedias'] = array();

        $typesArray = array('img','audio','video','doc','ocr','dis');
        foreach ($typesArray as $type) {
          $this->fileSequence[$type] = array();
          $elements = $xml->getElementsByTagName($type);
          if ($elements) {
              foreach($elements as $el) {
                  $data = $this->{'read'.ucfirst($type).'Data'}($el);
                  $nomenclature = $el->getElementsByTagName('nomenclature')->item(0)->nodeValue;
                  if($type === 'img')
                  {
                    $imageData = $this->saveImageOnSOLR($data->file, $dirName, $magName, $imageSizePriority, $nomenclature);
                    if(!$imageData)
                    {
                      $data->altimg = $this->readAltImgData($el);
                    }
                    else if($imageData){
                      $imageDataDecoded = json_decode($imageData);
                      if($imageDataDecoded->id)
                      {
                        $imageDataDecoded->title = $nomenclature;
                        $imageDataDecoded->label = $nomenclature;
                        $imageDataDecoded->file_name = $data->file;

                        $obj = new StdClass();
                        $obj->instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
                        $obj->mediaId = $imageDataDecoded->id;

                        $data->dam_media_id = $imageDataDecoded->id;
                        $data->file = json_encode($obj);
                        $data->altimg = $this->readAltImgData($el,$data->file,$this->priorityMedia);
                        $this->fileSequence[$type][$data->sequence_number] = json_encode($imageDataDecoded);
                        $data->originalSizeName = $this->priorityMedia;
                        $this->imageForContainer['addMedias'][] = $imageDataDecoded->id;
                      }
                    }
                  }
                  $data->parentId = $rootId;
                  $data->recordId = $rootId;
                  $data->type = $type;
                  if($type !== 'dis')
                  {
                    $data->order = $data->sequence_number;
                  }
                  $docStruProxy->saveContentForNode($data);
              }
          }
        }
        
        //Questo controllo evita la creazione del container per MAG con una sola immagine
        if(sizeof($this->imageForContainer['addMedias']) > 1)
        {
          $this->dam->addMediaToContainer($magName,json_encode($this->imageForContainer),$this->imageForContainer['addMedias'][0]);
        }
    }

    private function readRootData($xml,$getInventoryFromName=false,$file=null)
    {
        $data = new StdClass;

        $gen = $xml->getElementsByTagName("gen")->item(0);
        if($gen) {
            $data->GEN_creation = str_replace('T', ' ', $gen->getAttribute('creation'));
            $data->GEN_stprog = $xml->getElementsByTagName("stprog")->item(0)->nodeValue;
            $data->GEN_collection = $xml->getElementsByTagName("collection")->item(0)->nodeValue;
            $data->GEN_agency = $xml->getElementsByTagName("agency")->item(0)->nodeValue;
            $data->GEN_access_rights = $xml->getElementsByTagName("access_rights")->item(0)->nodeValue;
            $data->GEN_completeness = $xml->getElementsByTagName("completeness")->item(0)->nodeValue;
        }

        $bibNode = $xml->getElementsByTagName("bib")->item(0);
        if($bibNode) {
            //Attributo level
            $data->BIB_level = $bibNode->getAttribute('level');
            //Campi DC
            $data->BIB_dc_identifier = $this->getMultipleObject('BIB_dc_identifier_value',$bibNode,'identifier');
            $data->BIB_dc_identifier_index = ($data->BIB_dc_identifier[0]) ? $data->BIB_dc_identifier[0]->BIB_dc_identifier_value : '';
            $data->BIB_dc_title = $this->getMultipleObject('BIB_dc_title_value',$bibNode,'title');
            if (!$data->BIB_dc_title || !$data->BIB_dc_title[0]->BIB_dc_title_value) {
              $temp = new StdClass;
              $temp->BIB_dc_title_value = $data->BIB_dc_identifier[0]->BIB_dc_identifier_value;
              $data->BIB_dc_title = array($temp);
            }

            $data->BIB_dc_creator = $this->getMultipleObject('BIB_dc_creator_value',$bibNode,'creator');
            $data->BIB_dc_publisher = $this->getMultipleObject('BIB_dc_publisher_value',$bibNode,'publisher');
            $data->BIB_dc_contributor = $this->getMultipleObject('BIB_dc_contributor_value',$bibNode,'contributor');
            $data->BIB_dc_subject = $this->getMultipleObject('BIB_dc_subject_value',$bibNode,'subject');
            $data->BIB_dc_description = $this->getMultipleObject('BIB_dc_description_value',$bibNode,'description');
            $data->BIB_dc_date = $this->getMultipleObject('BIB_dc_date_value',$bibNode,'date');
            $data->BIB_dc_type = $this->getMultipleObject('BIB_dc_type_value',$bibNode,'type');
            $data->BIB_dc_format = $this->getMultipleObject('BIB_dc_format_value',$bibNode,'format');
            $data->BIB_dc_source = $this->getMultipleObject('BIB_dc_source_value',$bibNode,'source');
            $data->BIB_dc_language = $this->getMultipleObject('BIB_dc_language_value',$bibNode,'language');
            $data->BIB_dc_relation = $this->getMultipleObject('BIB_dc_relation_value',$bibNode,'relation');
            $data->BIB_dc_coverage = $this->getMultipleObject('BIB_dc_coverage_value',$bibNode,'coverage');
            $data->BIB_dc_rights = $this->getMultipleObject('BIB_dc_rights_value',$bibNode,'rights');

            $holding = new StdClass();
            $shelfmark = new StdClass();
            if($bibNode->getElementsByTagName("holdings")->item(0))
            {
              $holding->BIB_holdings_ID = $bibNode->getElementsByTagName("holdings")->item(0)->getAttribute('ID');
            }
            $holding->BIB_holdings_library = $bibNode->getElementsByTagName("library")->item(0)->nodeValue;

            //La lettura di inventory può dover arrivare se richiesto direttamente dal nome del file
            if($getInventoryFromName)
            {
              $filename = pathinfo($file,PATHINFO_FILENAME);
              $filename = explode("_",$filename);
              //Regole cablate sul nome dei file per casi specifici
              //SNSP e IISS
              if($filename[0] == 'iiss')
              {
                $inventory = $filename[2];
              }
              else if($filename[0] == 'snsp')
              {
                $inventory = (sizeof($filename) == 5) ? $filename[3] : $filename[2];
              }
              //Se non ricado in nessun caso, cerco comunque di recuperarlo da XML
              else
              {
                $inventory = $bibNode->getElementsByTagName("inventory_number")->item(0)->nodeValue;
              }
              $holding->BIB_holdings_inventory_number = $inventory;
            }
            else
            {
              $holding->BIB_holdings_inventory_number = $bibNode->getElementsByTagName("inventory_number")->item(0)->nodeValue;
            }
            $this->inventory = $holding->BIB_holdings_inventory_number;


            $shelfmark->BIB_holdings_shelfmark_value = $bibNode->getElementsByTagName("shelfmark")->item(0)->nodeValue;
            if($bibNode->getElementsByTagName("shelfmark")->item(0))
            {
              $shelfmark->BIB_holdings_shelfmark_type = $bibNode->getElementsByTagName("shelfmark")->item(0)->getAttribute('type');
            }
            $holding->BIB_holdings_shelfmark = array($shelfmark);
            $data->BIB_holdings = array($holding);
            //Mancano campi del fieldset Local Bib trovare esempio
            $data->BIB_holdings_piece = $bibNode->getElementsByTagName("piece")->item(0)->nodeValue;
        }

        //Estrazione struttura dati da STRU
        $nodes = $xml->getElementsByTagName('metadigit')->item(0)->childNodes;
        $struNodes = array();
        foreach ($nodes as $node) {
          if($node->localName === 'stru')
          {
            $struNodes[] = $node;
          }
        }

        //Ottengo la STRU e la memorizzo per ora in $data->stru
        $this->stru = $this->getStru($struNodes);
        $data->stru = $this->stru;

        //Collegamento schede
        if($data->BIB_dc_identifier_index)
        {
          if($this->formType == 'metafad.sbn.modules.sbnunimarc.model.Model')
          {
            $link = __ObjectFactory::createModelIterator($this->formType)
                    ->where('id', $data->BIB_dc_identifier_index);
            if($link->count() > 0)
            {
              $data->linkedFormType = 'metafad.sbn.modules.sbnunimarc';
              $o = new stdClass();
              $o->id = $data->BIB_dc_identifier_index;
              $o->text = $data->BIB_dc_identifier_index;
              $data->linkedForm = $o;
            }
          }
          else if(strpos($this->formType,'Scheda') === 0)
          {
            $link = __ObjectFactory::createModelIterator($this->formType)
              ->where('uniqueIccdId', $data->BIB_dc_identifier_index);
            if ($link->count() > 0) {
              $data->linkedFormType = $this->arrayModules[$this->formType];
              $o = new stdClass();
              $o->id = $data->BIB_dc_identifier_index;
              $o->text = $data->BIB_dc_identifier_index;
              $data->linkedForm = $o;
            }
          }
          else if($this->formType == 'archivi.models.UnitaDocumentaria' || $this->formType == 'archivi.models.UnitaArchivistica')
          {
            $link = __ObjectFactory::createModel($this->formType);
            if($link->load($data->BIB_dc_identifier_index))
            {
              $data->linkedFormType = $this->arrayModules[$this->formType];
              $o = new stdClass();
              $o->id = $data->BIB_dc_identifier_index;
              $o->text = $link->_denominazione;
              $data->linkedForm = $o;
            }

          }
        }

        return $data;
    }

    private function getStru($struNodes)
    {
      $struNodesObj = array();
      foreach ($struNodes as $struNode) {
        $struNodeData = new StdClass();
        $children = $struNode->childNodes;

        //Controllo stru annidate
        $struChildren = array();
        foreach ($children as $c) {
          if($c->localName === 'stru')
          {
            $struChildren[] = $c;
          }
        }
        $struNodeData->stru = $this->getStru($struChildren);

        $struNodeData->sequence_number = $struNode->getElementsByTagName('sequence_number')->item(0)->nodeValue;
        $struNodeData->nomenclature = $struNode->getElementsByTagName('nomenclature')->item(0)->nodeValue;

        $struNodeData->element = array();

        //Controllo elements che sono figli unicamente di questo tag STRU
        $elementChildren = array();
        {
          foreach ($children as $c) {
            if($c->localName === 'element')
            {
              $elementChildren[] = $c;
            }
          }
        }

        foreach($elementChildren as $c)
        {

          $struElementData = new StdClass();
          $struElementData->nomenclature = $c->getElementsByTagName('nomenclature')->item(0)->nodeValue;
          $struElementData->file = $c->getElementsByTagName('file')->item(0)->nodeValue;
          $struElementData->identifier = $c->getElementsByTagName('identifier')->item(0)->nodeValue;
          $struElementData->resource = $c->getElementsByTagName('resource')->item(0)->nodeValue;
          $struElementData->start = $c->getElementsByTagName('start')->item(0)->getAttribute('sequence_number');
          $struElementData->stop = $c->getElementsByTagName('stop')->item(0)->getAttribute('sequence_number');
          $struNodeData->element[] = $struElementData;
        }

        $struNodesObj[] = $struNodeData;
      }

      return $struNodesObj;
    }

    private function getMultipleObject($fieldName,$node,$tagName)
    {
      $el = new StdClass;
      $length = $node->getElementsByTagName($tagName)->length;
      if($length === 1)
      {
        $el->$fieldName = $node->getElementsByTagName($tagName)->item(0)->nodeValue;
      }
      else
      {
        //I campi dc ripetuti vengono estratti in questo modo
        $arrayEl = array();
        for($i = 0;$i < $length;$i++)
        {
          $el = new StdClass;
          $el->$fieldName = $node->getElementsByTagName($tagName)->item($i)->nodeValue;
          $arrayEl[] = $el;
        }
        return $arrayEl;
      }
      if($el->$fieldName)
      {
        return array($el);
      }
      else {
        return null;
      }
    }

    private function readImgGroups($xml)
    {
        $imgGroups = array();
        $imgGroupsNodes = $xml->getElementsByTagName("gen")->item(0)->getElementsByTagName("img_group");
        if($imgGroupsNodes) {
            $imgGroups = array();
            foreach($imgGroupsNodes as $imgGroup) {
                $imgGroupData = new StdClass();
                $imgGroupData->GEN_img_group_ID = $imgGroup->getAttribute('ID');
                $imgGroupData->GEN_img_group_image_metrics_samplingfrequencyunit = $imgGroup->getElementsByTagName("samplingfrequencyunit")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_image_metrics_samplingfrequencyplane = $imgGroup->getElementsByTagName("samplingfrequencyplane")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_image_metrics_xsamplingfrequency = $imgGroup->getElementsByTagName("xsamplingfrequency")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_image_metrics_ysamplingfrequency = $imgGroup->getElementsByTagName("ysamplingfrequency")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_image_metrics_photometricinterpretation = $imgGroup->getElementsByTagName("photometricinterpretation")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_image_metrics_bitpersample = $imgGroup->getElementsByTagName("bitpersample")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_ppi = $imgGroup->getElementsByTagName("ppi")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_dpi = $imgGroup->getElementsByTagName("dpi")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_format_name = $imgGroup->getElementsByTagName("name")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_format_mime = $imgGroup->getElementsByTagName("mime")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_format_compression= $imgGroup->getElementsByTagName("compression")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_scanning_sourcetype = $imgGroup->getElementsByTagName("sourcetype")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_scanning_scanningagency = $imgGroup->getElementsByTagName("scanningagency")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_scanning_devicesource = $imgGroup->getElementsByTagName("devicesource")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_scanning_scanningsystem_scanner_manufacturer = $imgGroup->getElementsByTagName("scanner_manufacturer")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_scanning_scanningsystem_scanner_model = $imgGroup->getElementsByTagName("scanner_model")->item(0)->nodeValue;
                $imgGroupData->GEN_img_group_scanning_scanningsystem_capture_software = $imgGroup->getElementsByTagName("capture_software")->item(0)->nodeValue;
                $imgGroups[] = $imgGroupData;
            }
        }
        return $imgGroups;
    }

    private function readAudioGroups($xml)
    {
        $audioGroups = array();
        $audioGroupsNodes = $xml->getElementsByTagName("gen")->item(0)->getElementsByTagName("audio_group");
        if($audioGroupsNodes) {
            $audioGroups = array();
            foreach($audioGroupsNodes as $audioGroup) {
                $audioGroupData = new StdClass();
                $audioGroupData->GEN_audio_group_ID = $audioGroup->getAttribute('ID');

                $audioGroupData->GEN_audio_group_audio_metrics_samplingfrequency = $audioGroup->getElementsByTagName("samplingfrequency")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_audio_metrics_bitpersample = $audioGroup->getElementsByTagName("bitpersample")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_audio_metrics_bitrate = $audioGroup->getElementsByTagName("bitrate")->item(0)->nodeValue;

                $audioGroupData->GEN_audio_group_format_name = $audioGroup->getElementsByTagName("name")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_format_mime = $audioGroup->getElementsByTagName("mime")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_format_compression = $audioGroup->getElementsByTagName("compression")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_format_channel_configuration = $audioGroup->getElementsByTagName("channel_configuration")->item(0)->nodeValue;

                $audioGroupData->GEN_audio_group_transcription_sourcetype = $audioGroup->getElementsByTagName("source_type")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_transcription_transcriptionagency = $audioGroup->getElementsByTagName("transcriptionagency")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_transcription_transcriptiondate = $audioGroup->getElementsByTagName("transcriptiondate")->item(0)->nodeValue;
                $audioGroupData->GEN_audio_group_transcription_devicesource = $audioGroup->getElementsByTagName("devicesource")->item(0)->nodeValue;

                $audioGroups[] = $audioGroupData;
            }
        }
        return $audioGroups;
    }

    private function readVideoGroups($xml)
    {
        $videoGroups = array();
        $videoGroupsNodes = $xml->getElementsByTagName("gen")->item(0)->getElementsByTagName("video_group");
        if($videoGroupsNodes) {
            $videoGroups = array();
            foreach($videoGroupsNodes as $videoGroup) {
                $videoGroupData = new StdClass();
                $videoGroupData->GEN_img_group_ID = $videoGroup->getAttribute('ID');
                $videoGroupData->GEN_video_group_video_metrics_videosize = $videoGroup->getElementsByTagName("videosize")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_video_metrics_aspectratio = $videoGroup->getElementsByTagName("aspectratio")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_video_metrics_framerate = $videoGroup->getElementsByTagName("framerate")->item(0)->nodeValue;

                $videoGroupData->GEN_video_group_format_name = $videoGroup->getElementsByTagName("name")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_format_mime = $videoGroup->getElementsByTagName("mime")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_format_videoformat = $videoGroup->getElementsByTagName("videoformat")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_format_encode = $videoGroup->getElementsByTagName("encode")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_format_streamtype = $videoGroup->getElementsByTagName("streamtype")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_format_codec = $videoGroup->getElementsByTagName("codec")->item(0)->nodeValue;

                $videoGroupData->GEN_video_group_digitisation_sourcetype = $videoGroup->getElementsByTagName("sourcetype")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_digitisation_transcriptionagency = $videoGroup->getElementsByTagName("transcriptionagency")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_digitisation_devicesource = $videoGroup->getElementsByTagName("devicesource")->item(0)->nodeValue;
                $videoGroupData->GEN_video_group_format_codec = $videoGroup->getElementsByTagName("codec")->item(0)->nodeValue;

                $videoGroups[] = $videoGroupData;
            }
        }
        return $videoGroups;
    }

    private function readImgData($xml)
    {
        $data = new StdClass;
        $data->imggroupID = $xml->getAttribute('imggroupID');
        $data->docstru_title = ($xml->getElementsByTagName("nomenclature")->item(0)->nodeValue) ? : 'Senza titolo';
        $data->nomenclature = $xml->getElementsByTagName("nomenclature")->item(0)->nodeValue;
        $data->sequence_number = $xml->getElementsByTagName("sequence_number")->item(0)->nodeValue;
        $img_usage = $this->getMultipleObject('usage_value',$xml,'usage');
        $data->usage = array($img_usage[0]);
        $data->side = $xml->getElementsByTagName("side")->item(0)->nodeValue;
        $data->scale = $xml->getElementsByTagName("scale")->item(0)->nodeValue;
        $data->file = $xml->getElementsByTagName("file")->item(0)->getAttribute('xlink:href');
        $data->md5 = $xml->getElementsByTagName("md5")->item(0)->nodeValue;
        $data->filesize = $xml->getElementsByTagName("filesize")->item(0)->nodeValue;
        $data->imagelength = $xml->getElementsByTagName("imagelength")->item(0)->nodeValue;
        $data->imagewidth = $xml->getElementsByTagName("imagewidth")->item(0)->nodeValue;
        $data->source_xdimension = $xml->getElementsByTagName("source_xdimension")->item(0)->nodeValue;
        $data->source_ydimension = $xml->getElementsByTagName("source_ydimension")->item(0)->nodeValue;
        $data->samplingfrequencyunit = $xml->getElementsByTagName("samplingfrequencyunit")->item(0)->nodeValue;
        $data->samplingfrequencyplane = $xml->getElementsByTagName("samplingfrequencyplane")->item(0)->nodeValue;
        $data->xsamplingfrequency = $xml->getElementsByTagName("xsamplingfrequency")->item(0)->nodeValue;
        $data->ysamplingfrequency = $xml->getElementsByTagName("ysamplingfrequency")->item(0)->nodeValue;
        $data->bitpersample = $xml->getElementsByTagName("bitpersample")->item(0)->nodeValue;
        $data->photometricinterpretation = $xml->getElementsByTagName("photometricinterpretation")->item(0)->nodeValue;
        $data->ppi = $xml->getElementsByTagName("ppi")->item(0)->nodeValue;
        $data->dpi = $xml->getElementsByTagName("dpi")->item(0)->nodeValue;
        $data->name = $xml->getElementsByTagName("name")->item(0)->nodeValue;
        $data->mime = $xml->getElementsByTagName("mime")->item(0)->nodeValue;
        $data->compression = $xml->getElementsByTagName("compression")->item(0)->nodeValue;
        $data->sourcetype = $xml->getElementsByTagName("sourcetype")->item(0)->nodeValue;
        $data->scanningagency = $xml->getElementsByTagName("scanningagency")->item(0)->nodeValue;
        $data->devicesource = $xml->getElementsByTagName("devicesource")->item(0)->nodeValue;
        $data->scanner_manufacturer = $xml->getElementsByTagName("scanner_manufacturer")->item(0)->nodeValue;
        $data->scanner_model = $xml->getElementsByTagName("scanner_model")->item(0)->nodeValue;
        $data->capture_software = $xml->getElementsByTagName("capture_software")->item(0)->nodeValue;
        $data->datetimecreated = $xml->getElementsByTagName("datetimecreated")->item(0)->nodeValue;
        $data->targetType = $xml->getElementsByTagName("targetType")->item(0)->nodeValue;
        $data->imageData = $xml->getElementsByTagName("imageData")->item(0)->nodeValue;
        $data->performanceData = $xml->getElementsByTagName("performanceData")->item(0)->nodeValue;
        $data->profiles = $xml->getElementsByTagName("profiles")->item(0)->nodeValue;

        $data->datetimecreated= str_replace('T', ' ', $xml->getElementsByTagName("datetimecreated")->item(0)->nodeValue);
        return $data;
    }

    private function readAltImgData($xml,$file = null,$original = null)
    {
        $altImgs = array();
        $count = 0;
        $altImgsNodes = $xml->getElementsByTagName("altimg");
        if ($altImgsNodes) {
            $altImgs = array();
            foreach($altImgsNodes as $altImg) {
                $altImgData = new StdClass();
                $altImgData->altimg_imggroupID = $altImg->getAttribute('imggroupID');
                $altimg_usage = $this->getMultipleObject('altimg_usage_value',$xml,'usage');
                $altImgData->altimg_usage = array($altimg_usage[$count + 1]);
                if($file) {
                    $altImgData->altimg_file = $file;
                }
                else {
                  $altImgData->altimg_file = $altImg->getElementsByTagName("file")->item(0)->getAttribute('xlink:href');
                }
                $altImgData->altimg_md5 = $altImg->getElementsByTagName("md5")->item(0)->nodeValue;
                $altImgData->altimg_filesize = $altImg->getElementsByTagName("filesize")->item(0)->nodeValue;
                $altImgData->altimg_imagelength = $altImg->getElementsByTagName("imagelength")->item(0)->nodeValue;
                $altImgData->altimg_imagewidth = $altImg->getElementsByTagName("imagewidth")->item(0)->nodeValue;
                $altImgData->altimg_source_xdimension = $altImg->getElementsByTagName("source_xdimension")->item(0)->nodeValue;
                $altImgData->altimg_source_ydimension = $altImg->getElementsByTagName("source_ydimension")->item(0)->nodeValue;
                $altImgData->altimg_samplingfrequencyunit = $altImg->getElementsByTagName("samplingfrequencyunit")->item(0)->nodeValue;
                $altImgData->altimg_samplingfrequencyplane = $altImg->getElementsByTagName("samplingfrequencyplane")->item(0)->nodeValue;
                $altImgData->altimg_xsamplingfrequency = $altImg->getElementsByTagName("xsamplingfrequency")->item(0)->nodeValue;
                $altImgData->altimg_ysamplingfrequency = $altImg->getElementsByTagName("ysamplingfrequency")->item(0)->nodeValue;
                $altImgData->altimg_bitpersample = $altImg->getElementsByTagName("bitpersample")->item(0)->nodeValue;
                $altImgData->altimg_photometricinterpretation = $altImg->getElementsByTagName("photometricinterpretation")->item(0)->nodeValue;
                $altImgData->altimg_ppi = $altImg->getElementsByTagName("ppi")->item(0)->nodeValue;
                $altImgData->altimg_dpi = $altImg->getElementsByTagName("dpi")->item(0)->nodeValue;
                $altImgData->altimg_name = $altImg->getElementsByTagName("name")->item(0)->nodeValue;
                $altImgData->altimg_mime = $altImg->getElementsByTagName("mime")->item(0)->nodeValue;
                $altImgData->altimg_compression = $altImg->getElementsByTagName("compression")->item(0)->nodeValue;
                $altImgData->altimg_sourcetype = $altImg->getElementsByTagName("sourcetype")->item(0)->nodeValue;
                $altImgData->altimg_scanningagency = $altImg->getElementsByTagName("scanningagency")->item(0)->nodeValue;
                $altImgData->altimg_devicesource = $altImg->getElementsByTagName("devicesource")->item(0)->nodeValue;
                $altImgData->altimg_scanner_manufacturer = $altImg->getElementsByTagName("scanner_manufacturer")->item(0)->nodeValue;
                $altImgData->altimg_scanner_model = $altImg->getElementsByTagName("scanner_model")->item(0)->nodeValue;
                $altImgData->altimg_capture_software = $altImg->getElementsByTagName("capture_software")->item(0)->nodeValue;
                $altImgData->altimg_datetimecreated = str_replace('T', ' ', $altImg->getElementsByTagName("datetimecreated")->item(0)->nodeValue);
                $altImgData->altimg_note = $altImg->getElementsByTagName("note")->item(0)->nodeValue;
                $count++;
                $altImgs[] = $altImgData;
            }
        }

        return $altImgs;
    }

    private function readAudioData($xml)
    {
        $data = new StdClass;
        $data->audiogroupID = $xml->getAttribute('audiogroupID');
        $data->docstru_title = ($xml->getElementsByTagName("nomenclature")->item(0)->nodeValue) ? : 'Senza titolo';
        $data->nomenclature = $xml->getElementsByTagName("nomenclature")->item(0)->nodeValue;
        $data->sequence_number = $xml->getElementsByTagName("sequence_number")->item(0)->nodeValue;
        $data->proxies = $this->readAudioProxies($xml);
        $data->note = $xml->getElementsByTagName("note")->item(0)->nodeValue;
        return $data;
    }

    private function readAudioProxies($xml)
    {
        $proxies = array();
        $proxiesNodes = $xml->getElementsByTagName("proxies");
        if ($proxiesNodes) {
            $proxies = array();
            foreach($proxiesNodes as $proxy) {
                $proxyData = new StdClass();
                $proxyData->audiogroupID = $proxy->getAttribute('audiogroupID');
                $proxyData->usage = $this->getMultipleObject('usage_value',$xml,'usage');
                $proxyData->file = $proxy->getElementsByTagName("file")->item(0)->getAttribute('xlink:href');
                $proxyData->md5 = $proxy->getElementsByTagName("md5")->item(0)->nodeValue;
                $proxyData->filesize = $proxy->getElementsByTagName("filesize")->item(0)->nodeValue;

                $proxyData->duration = $proxy->getElementsByTagName("duration")->item(0)->nodeValue;

                $proxyData->samplingfrequency = $proxy->getElementsByTagName("samplingfrequency")->item(0)->nodeValue;
                $proxyData->bitpersample = $proxy->getElementsByTagName("bitpersample")->item(0)->nodeValue;
                $proxyData->bitrate = $proxy->getElementsByTagName("bitrate")->item(0)->nodeValue;

                $proxyData->name = $proxy->getElementsByTagName("name")->item(0)->nodeValue;
                $proxyData->mime = $proxy->getElementsByTagName("mime")->item(0)->nodeValue;
                $proxyData->compression = $proxy->getElementsByTagName("compression")->item(0)->nodeValue;
                $proxyData->channel_configuration = $proxy->getElementsByTagName("channel_configuration")->item(0)->nodeValue;

                $proxyData->sourcetype = $proxy->getElementsByTagName("sourcetype")->item(0)->nodeValue;
                $proxyData->transcriptionagency = $proxy->getElementsByTagName("transcriptionagency")->item(0)->nodeValue;
                $proxyData->transcriptiondate = $proxy->getElementsByTagName("transcriptiondate")->item(0)->nodeValue;
                $proxyData->devicesource = $proxy->getElementsByTagName("devicesource")->item(0)->nodeValue;

                //$proxyData->transcriptionchain
                //$proxyData->transcriptionsummary
                //$proxyData->transcriptiondata

                $proxyData->datetimecreated = str_replace('T', ' ', $proxy->getElementsByTagName("datetimecreated")->item(0)->nodeValue);
                $proxies[] = $proxyData;
            }
        }

        return $proxies;
    }

    private function readVideoData($xml)
    {
        $data = new StdClass;
        $data->videogroupID = $xml->getAttribute('videogroupID');
        $data->docstru_title = ($xml->getElementsByTagName("nomenclature")->item(0)->nodeValue) ? : 'Senza titolo';
        $data->nomenclature = $xml->getElementsByTagName("nomenclature")->item(0)->nodeValue;
        $data->sequence_number = $xml->getElementsByTagName("sequence_number")->item(0)->nodeValue;
        $data->proxies = $this->readVideoProxies($xml);
        $data->note = $xml->getElementsByTagName("note")->item(0)->nodeValue;
        return $data;
    }

    private function readVideoProxies($xml)
    {
        $proxies = array();
        $proxiesNodes = $xml->getElementsByTagName("proxies");
        if ($proxiesNodes) {
            $proxies = array();
            foreach($proxiesNodes as $proxy) {
                $proxyData = new StdClass();
                $proxyData->videogroupID = $proxy->getAttribute('videogroupID');
                $proxyData->usage = $this->getMultipleObject('usage_value',$xml,'usage');
                $proxyData->file = $proxy->getElementsByTagName("file")->item(0)->getAttribute('xlink:href');
                $proxyData->md5 = $proxy->getElementsByTagName("md5")->item(0)->nodeValue;
                $proxyData->filesize = $proxy->getElementsByTagName("filesize")->item(0)->nodeValue;

                $proxyData->duration = $proxy->getElementsByTagName("duration")->item(0)->nodeValue;

                $proxyData->videosize = $proxy->getElementsByTagName("videosize")->item(0)->nodeValue;
                $proxyData->aspectratio = $proxy->getElementsByTagName("aspectratio")->item(0)->nodeValue;
                $proxyData->framerate = $proxy->getElementsByTagName("framerate")->item(0)->nodeValue;

                $proxyData->name = $proxy->getElementsByTagName("name")->item(0)->nodeValue;
                $proxyData->mime = $proxy->getElementsByTagName("mime")->item(0)->nodeValue;
                $proxyData->videoformat = $proxy->getElementsByTagName("videoformat")->item(0)->nodeValue;
                $proxyData->encode = $proxy->getElementsByTagName("encode")->item(0)->nodeValue;
                $proxyData->streamtype = $proxy->getElementsByTagName("streamtype")->item(0)->nodeValue;
                $proxyData->codec = $proxy->getElementsByTagName("codec")->item(0)->nodeValue;

                $proxyData->sourcetype = $proxy->getElementsByTagName("sourcetype")->item(0)->nodeValue;
                $proxyData->transcriptionagency = $proxy->getElementsByTagName("transcriptionagency")->item(0)->nodeValue;
                $proxyData->devicesource = $proxy->getElementsByTagName("devicesource")->item(0)->nodeValue;

                //$proxyData->transcriptionchain
                //$proxyData->transcriptionsummary
                //$proxyData->transcriptiondata

                $proxyData->datetimecreated = str_replace('T', ' ', $proxy->getElementsByTagName("datetimecreated")->item(0)->nodeValue);
                $proxies[] = $proxyData;
            }
        }

        return $proxies;
    }

    private function readDocData($xml)
    {
        $data = new StdClass;
        $data->holdingsID = $xml->getAttribute('holdingsID');
        $data->docstru_title = ($xml->getElementsByTagName("nomenclature")->item(0)->nodeValue) ? : 'Senza titolo';
        $data->nomenclature = $xml->getElementsByTagName("nomenclature")->item(0)->nodeValue;
        $data->sequence_number = $xml->getElementsByTagName("sequence_number")->item(0)->nodeValue;
        $data->usage = $this->getMultipleObject('usage_value',$xml,'usage');
        $data->file = $xml->getElementsByTagName("file")->item(0)->nodeValue;
        $data->md5 = $xml->getElementsByTagName("md5")->item(0)->nodeValue;
        $data->filesize = $xml->getElementsByTagName("filesize")->item(0)->nodeValue;
        $data->name = $xml->getElementsByTagName("name")->item(0)->nodeValue;
        $data->mime = $xml->getElementsByTagName("mime")->item(0)->nodeValue;
        $data->compression = $xml->getElementsByTagName("compression")->item(0)->nodeValue;

        $data->datetimecreated = str_replace('T', ' ', $xml->getElementsByTagName("datetimecreated")->item(0)->nodeValue);
        $data->note = $xml->getElementsByTagName("note")->item(0)->nodeValue;
        return $data;
    }

    private function readOcrData($xml)
    {
        $data = new StdClass;
        $data->holdingsID = $xml->getAttribute('holdingsID');
        $data->docstru_title = ($xml->getElementsByTagName("nomenclature")->item(0)->nodeValue) ? : 'Senza titolo';
        $data->nomenclature = $xml->getElementsByTagName("nomenclature")->item(0)->nodeValue;
        $data->sequence_number = $xml->getElementsByTagName("sequence_number")->item(0)->nodeValue;
        $data->usage = $this->getMultipleObject('usage_value',$xml,'usage');
        $data->file = $xml->getElementsByTagName("file")->item(0)->nodeValue;
        $data->md5 = $xml->getElementsByTagName("md5")->item(0)->nodeValue;
        $data->source = $xml->getElementsByTagName("source")->item(0)->nodeValue;
        $data->filesize = $xml->getElementsByTagName("filesize")->item(0)->nodeValue;
        $data->name = $xml->getElementsByTagName("name")->item(0)->nodeValue;
        $data->mime = $xml->getElementsByTagName("mime")->item(0)->nodeValue;
        $data->compression = $xml->getElementsByTagName("compression")->item(0)->nodeValue;
        $data->software_ocr = $xml->getElementsByTagName("software_ocr")->item(0)->nodeValue;

        $data->datetimecreated = str_replace('T', ' ', $xml->getElementsByTagName("datetimecreated")->item(0)->nodeValue);
        $data->note = $xml->getElementsByTagName("note")->item(0)->nodeValue;
        return $data;
    }

    private function saveImageOnSOLR($image, $dirName, $magName, $imageSizePriority,$nomenclature)
    {
      if (!$image) return null;
      $image = str_replace(array("/H/",".tif",'./'),array("/M/",".jpg",'/'),$image);
      $filePaths = array();
      foreach($imageSizePriority as $size) {
        $filePaths[] = $dirName.'/'.preg_replace('/\/[HMS]\//', '/'.$size.'/', $image);
        $filePaths[] = $dirName.preg_replace('/^\.\/([^\/])*\/Immagini\/([HMS])/', '/Immagini/'.$size, $image);
        $filePaths[] = $dirName.preg_replace('/^\.\/([^\/])*\/Immagini\/([HMS])/', '/'.$magName.'/Immagini/'.$size, $image);
      }
      $filePath = null;
      $sizeIndex = 0;
      foreach($filePaths as $file) {
        $file = str_replace('//', '/', $file);
        if (file_exists($file)) {
          $filePath = $file;
          break;
        }
        $sizeIndex++;
      }

      // non è stata trovata nessuna immagine
      if ($filePath == null) {
          throw new Exception('mag senza immagini, immagine non trovata:'. $image);
      }

      $sizeIndex = $sizeIndex / 2;
      $this->priorityMedia = $imageSizePriority[$sizeIndex];

      // file non trovato
      // TODO mostrare errore
      if (!$filePath) return null;

      $media = new StdClass();
      $media->title = ($nomenclature) ? $nomenclature : pathinfo($filePath, PATHINFO_BASENAME);
      $media->filename = $filePath;

      $mediaData = array();
      $mediaData['addMedias'][] = array(
          'MainData' => $media,
          'bytestream' => realpath($filePath)
      );

      $mediaData = json_encode($mediaData);
      $mediaExists = $this->dam->mediaExists($filePath);

      if (!$mediaExists['response']) {
          $res = $this->dam->insertMedia($mediaData);
      }

      if (!empty($res)) {
          $id = $res->ids[0];
          $sizeArray = array('S','M','H');
          foreach ($sizeArray as $size) {
            if($imageSizePriority[$sizeIndex] != $size)
            {
              $imageSized = str_replace("/".$imageSizePriority[$sizeIndex]."/","/".$size."/",$filePath);
              if ($imageSized != '' && file_exists($imageSized)) {
                $bytestreamData["addBytestream"] = array();
                $bytestreamData["addBytestream"][] = array(
                                      "name"=>$size,
                                      "path"=>$imageSized
                                    );
                $this->dam->insertBytestream(json_encode($bytestreamData),$id);
              }
            }
          }
      }
      else if($mediaExists['ids']) {
          $id = $mediaExists['ids'][0];
      }

      $result = $this->dam->getJSON($id, $media->title);
      return !empty($result) ? $result : NULL;
    }

    // private function readDisData($xml)
    // {
    //   $data = new StdClass;
    //   $dis_items = array();
    //   $dis_itemNodes = $xml->getElementsByTagName("dis_item");
    //   if ($dis_itemNodes) {
    //       $dis_items = array();
    //       foreach($dis_itemNodes as $dis_item) {
    //           $dis_itemData = new StdClass();
    //           $dis_itemData->file = $dis_item->getElementsByTagName("file")->item(0)->getAttribute('xlink:href');
    //           $dis_itemData->preview = $xml->getElementsByTagName("preview")->item(0)->nodeValue;
    //           $dis_itemData->available = $xml->getElementsByTagName("available")->item(0)->nodeValue;
    //           $dis_items[] = $dis_itemData;
    //       }
    //   }
    //   $data->dis_item = $dis_items;
    //   return $data;
    // }

    // private function linkRecord($identifier)
    // {
    //     $helper = org_glizy_ObjectFactory::createObject('chviewer.modules.docstru.helpers.TypeInstanceHelper');
    //     $type = $helper->getInstanceType();
    //     $it = org_glizy_ObjectFactory::createModelIterator($type['model'])
    //             ->load('recordPicker', array('term' => $identifier));
    //     if ($it->count()) {
    //         $ar = $it->first();
    //         $result = new StdClass;
    //         $result->id = $ar->document_id;
    //         $result->text = $ar->$type['titleString'];
    //         $result->bid = $ar->id;
    //         return $result;
    //     } else {
    //         return null;
    //     }
    // }


    // private function flipArrayForDocuments($data)
    // {
    //     $tempData = array();
    //     if (count($data)) {
    //         $keys = array_keys((array)$data[0]);
    //         foreach($data as $v) {
    //             foreach($keys as $k) {
    //                 if (!$tempData[$k]) {
    //                     $tempData[$k] = array();
    //                 }
    //                 $tempData[$k][] = $v->{$k};
    //             }
    //         }
    //     }
    //     return $tempData;
    // }


    private $count = 1;
    private $arrayStartStop = array();

    private function createStruMag($file,$rootId)
    {
      $this->linkedStru = null;
      $stru = $this->stru;
      $images = $this->fileSequence['img'];
      //Questo controllo evita di creare la strumag per MAG con una sola immagine
      if(sizeof($images) <= 1)
      {
        return;
      }

      $logicalSTRU = array();
      $logicalSTRU[] = array('folder'=>false,'key'=>'exclude','title'=>'Elementi esclusi');
      $physicalSTRU = array('image'=>array());
      $strumag = org_glizy_ObjectFactory::createModel('metafad.teca.STRUMAG.models.Model');
      $strumag->title = $this->identifier ? $this->identifier : basename($file, ".xml");
      $strumag->state = 0;
      
      if(!empty($images) && $stru)
      {
        //Creo la logicalSTRU
        foreach ($stru as $s) {
          if(empty($e->element))
          {
            continue;
          }
          $isFolder = ($s->stru) ? true : false;
          $expanded = ($isFolder) ? true : null;
          $title = $s->nomenclature;
          $key = $this->count;
          $this->setStartStop($s->element,$key);
          $this->count++;
          $children = ($s->stru) ? $this->iterateStru($s->stru) : null;
          $el = array('folder'=>$isFolder,'expanded'=>$expanded,'key'=>$key,'title'=>$title,'children'=>$children);
          $logicalSTRU[] = $el;
        }

        //Creo la physicalStru
        foreach($this->arrayStartStop as $k => $v)
        {
          $interval = $v['stop'] - $v['start'];
          $startKey = $v['start'];
          for ($i = 0; $i <= $interval; $i++) {
            $mediaData = json_decode($images[$startKey]);
            $mediaData->keyNode = $k;
            $physicalSTRU['image'][] = $mediaData;
            $startKey++;
          }
        }
      }
      
      if(!empty($images) && empty($physicalSTRU['image']))
      {
        //Creo la logicalSTRU e la physicalSTRU
        foreach ($images as $img) {
          $image = json_decode($img);
          $title = $image->title;
          $key = $this->count;
          $this->count++;
          $el = array('folder'=>false,'key'=>$key,'title'=>$title);
          //COME RICHIESTO DA POLODEBUG-339 QUESTA PARTE NON DEVE GENERARE UNA LOGICAL STRU
          //$logicalSTRU[] = $el;
          $image->keyNode = 'exclude';
          $physicalSTRU['image'][] = $image;
        }
      }

      $strumag->physicalSTRU = json_encode($physicalSTRU);
      $strumag->logicalSTRU = json_encode($logicalSTRU);

      $id = $strumag->publish();

      $decodeData = (object)$strumag->getValuesAsArray();

      $cl = new stdClass();

      $it = org_glizy_ObjectFactory::createModelIterator( 'metafad.teca.STRUMAG.models.Model' );

      if ($it->getArType() === 'document') {
          $it->setOptions(array('type' => 'PUBLISHED_DRAFT'));
      }

      $it->where('document_id', $id, 'ILIKE');
      foreach ($it as $record) {
          $cl->className = $record->getClassName(false);
          $cl->isVisible = $record->isVisible();
          $cl->isTranslated = $record->isTranslated();
          $cl->hasPublishedVersion = $record->hasPublishedVersion();
          $cl->hasDraftVersion = $record->hasDraftVersion();
          $cl->document_detail_status = $record->getStatus();
      }
      $decodeData->__id = $id;
      $decodeData->__model = 'metafad.teca.STRUMAG.models.Model';

      $decodeData->document = json_encode($cl);
      $decodeData->__commit = true;
      $this->event->insert($decodeData);

      $linkedStru = new stdClass();
      $linkedStru->id = $id;
      $linkedStru->text = $strumag->title;

      $mag = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Model')
            ->where('document_id',$rootId)->first();
      $mag->linkedStru = $linkedStru;
      $this->linkedStru = $linkedStru;
      $mag->saveCurrentPublished();
    }

    private function iterateStru($stru)
    {
      $el = array();
      foreach ($stru as $s) {
        $isFolder = ($s->stru) ? true : false;
        $expanded = ($isFolder) ? true : null;
        $title = $s->nomenclature;
        $key = $this->count;
        $this->setStartStop($s->element,$key);
        $this->count++;
        $children = ($s->stru) ? $this->iterateStru($s->stru) : null;
        $el[] = array('folder'=>$isFolder,'expanded'=>$expanded,'key'=>$key,'title'=>$title,'children'=>$children);
      }
      return $el;
    }

    private function setStartStop($elements,$key)
    {
      foreach ($elements as $e) {
        if($e->resource == 'img')
        {
          $this->arrayStartStop[$key] = array('start'=>$e->start,'stop'=>$e->stop);
        }
      }
    }

    public function setImportOption($formType,$importMode)
    {
      $this->formType = $formType;
      $this->importMode = $importMode;
    }

    public function linkMedia($model)
    {
      $identifier = $this->identifier;
      if(!$identifier)
      {
        return;
      }
      $mediaLinkHelper = org_glizy_ObjectFactory::createObject('metafad.teca.MAG.helpers.MediaLinkHelper');

      if($model == 'metafad.sbn.modules.sbnunimarc.model.Model'){
        $mediaLinkHelper->linkToSBN($model,$identifier,$this->fileSequence['img'],$this->linkedStru,$this->inventory);
      }
      else if(strpos($model,'archivi.models.') === 0){
        $mediaLinkHelper->linkToArchive($model,$identifier,$this->fileSequence['img'],$this->linkedStru);
      }
      else{
        $mediaLinkHelper->linkToIccd($model,$identifier,$this->fileSequence['img']);
      }
    }
}