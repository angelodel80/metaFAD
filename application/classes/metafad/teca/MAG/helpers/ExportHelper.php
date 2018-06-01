<?php
class metafad_teca_MAG_helpers_ExportHelper extends GlizyObject
{
  private $docStruProxy;
  private $damUrl;
  private $originalSizeName;
  private $damPath;

  function __construct(metafad_teca_MAG_models_proxy_DocStruProxy $docStruProxy)
  {
    $this->docStruProxy = $docStruProxy;
  }

  function createExportPack($magList, $title, $mode, $email, $format)
  {
    $jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
    $jobFactory->createJob(
      'metafad.teca.MAG.helpers.Batch',
      array(
        'magList' => $magList,
        'title' => $title,
        'mode' => $mode,
        'email' => $email,
        'format' => $format,
        'instituteKey' => metafad_usersAndPermissions_Common::getInstituteKey()
      ),
      'Esportazione MAG "' . $title . '" lanciata in data ' . new org_glizy_types_DateTime(),
      'BACKGROUND'
    );
  }

  function showXML($id)
  {
    if ($id) {
      $publicationRoot = $this->docStruProxy->getRootNode($id);
      if ($publicationRoot) {
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
        header('Expires: -1');
        header("Content-Type: text/xml; charset=utf-8");

        echo $this->generateXML($id);
        exit;
      }
    }

    new org_glizy_Exception(__T('GLZ_ERR_404'), GLZ_E_404);
  }

  protected function convertDate($date)
  { 
      preg_match('/(\d+)\/(\d+)\/(\d+) (\d+:\d+:\d+)/', $date, $m);
      return $magData->gen['GEN_creation'] = $m[3].'-'.$m[2].'-'.$m[1].'T'.$m[4];
  }

  private function createMagData($id)
  {
    $mag = org_glizy_ObjectFactory::createModel('metafad.teca.MAG.models.Model');
    $mag->load($id, 'PUBLISHED_DRAFT');

    $docstru = $this->docStruProxy->getRootNodeByDocumentId($id);

    $magData = new StdClass;
    $magData->gen = $this->docStruProxy->readContentFromNode($docstru->docstru_id);
    $magData->gen['img_group'] = $this->convertObject($magData->gen['GEN_img_group']);
    $magData->images = array();

    $magData->gen['GEN_creation'] = $this->convertDate($magData->gen['GEN_creation']);
    $magData->gen['GEN_lastUpdate'] = $this->convertDate($magData->gen['GEN_lastUpdate']);

    $it = org_glizy_ObjectFactory::createModelIterator('metafad.teca.MAG.models.Img')
      ->where('docstru_rootId', $docstru->docstru_id)
      ->where('docstru_type', 'Img');
    foreach ($it as $ar) {
      $magData->images[] = $ar->getRawData();
    }

    if ($mag->stru_options === '1' || $mag->stru_options == '2') {
      $struData = $this->getStruData($mag->linkedStru);
      if ($struData) {
        $magData->struData = $struData;
      }
    }

    return $magData;
  }

  public function convertObject($data)
  {
    $result = array();
    $keys = array_keys((array)$data[0]);
    $numItems = count($keys);
    if ($data) {
      $count = 0;
      foreach ($data as $d) {
        foreach ($keys as $v) {
          if ($v == 'datetimecreated') {
            $temp->{$v} = str_replace(' ', 'T', glz_localeDate2ISO($d->{$v}));
            continue;
          }
          $result[$count][$v] = $d->{$v};
        }
        $count++;
      }
    }
    return $result;
  }

  private function generateXML($id)
  {
    $magData = $this->createMagData($id);
    $skinClass = org_glizy_ObjectFactory::createObject('org.glizy.template.skin.PHPTAL', 'mag.xml', '');
    $skinClass->set('Component', $magData);
    $output = $skinClass->execute();
    return $output;
  }

  public function createXMLFile($filename, $xml)
  {
    $xmlFile = fopen($filename, "wb");
    fwrite($xmlFile, $xml);
    fclose($xmlFile);
  }

  public function makeDownloadLink($title)
  {
    return __Link::makeUrl('moduleAction', array('pageId' => 'tecamag', 'action' => 'getMagExport')) . '?exportName=' . $title;
  }

  public function sendDownloadEmail($email, $title, $date)
  {
    $emails = explode(",", $email);
    $subject = str_replace("{title}", $title, __T('subjectExportMAG'));
    $body = str_replace(array("{link}", "{date}"), array($this->makeDownloadLink($title), $date), __T('bodyExportMAG'));
    foreach ($emails as $e) {
      $r = org_glizy_helpers_Mail::sendEmail(
        array('email' => $e, 'name' => 'Utente metafad'),
        array('email' => org_glizy_Config::get('SMTP_EMAIL'), 'name' => org_glizy_Config::get('SMTP_SENDER')),
        $subject,
        $body
      );
    }
  }

  public function sendOAIEmail($email, $title, $date)
  {
    $emails = explode(",", $email);
    $subject = str_replace("{title}", $title, __T('subjectExportMAGOAI'));
    $body = __T('bodyExportOAIMAG');
    foreach ($emails as $e) {
      $r = org_glizy_helpers_Mail::sendEmail(
        array('email' => $e, 'name' => 'Utente metafad'),
        array('email' => org_glizy_Config::get('SMTP_EMAIL'), 'name' => org_glizy_Config::get('SMTP_SENDER')),
        $subject,
        $body
      );
    }
  }

  public function getStruData($data)
  {
    if ($data) {
      $strumag = org_glizy_ObjectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model')
        ->where('document_id', $data->id)
        ->first();
      if ($strumag) {
        $nodesTitle = array();
        $nodesContent = array();
        $nodesAlias = array();

        $logicalStru = $strumag->logicalSTRU;
        $physicalStru = $strumag->physicalSTRU;

        $folders = json_decode($logicalStru);
        foreach ($folders as $folder) {
          if ($folder->key != 'exclude') {
            $nodesTitle[$folder->key] = $folder->title;
            $nodesContent[$folder->key] = array();
            $nodesNumMedia[$folder->key] = $folder->data->count;
            $nodesAlias[$folder->key] = array();
            if ($dataCount === null) {
              $dataCount = ($folder->data->count !== null) ? true : false;
            }
          }
        }

        if (empty($nodesContent)) {
          return null;
        }

        //Conteggio in caso di data->numMedia
        $images = json_decode($physicalStru)->image;
        $count = 1;
        if ($images) {
          foreach ($images as $image) {
            if ($image->keyNode != 'exclude') {
              if(is_array($nodesContent[$image->keyNode]))
              {
                array_push($nodesContent[$image->keyNode], $count);
              }
              $count++;
            }
          }
        }
        
        //RECUPERO INFO ALIAS
        foreach($images as $image)
        {
          $keyNode = $image->aliasKeyNode;
          if($keyNode)
          {
            foreach($keyNode as $kn)
            {
              $pos = $image->pos;
              if(is_array($nodesAlias[$kn]))
              {
                array_push($nodesAlias[$kn], $pos + 1);
              }
            }
          }
        }

        $merge = array();
        foreach ($nodesTitle as $k => $v) {
          $merge[$v] = array('normal'=>$nodesContent[$k],'alias'=> $nodesAlias[$k]);
        }
        return $merge;
      }
      return null;
    }
  }

}
