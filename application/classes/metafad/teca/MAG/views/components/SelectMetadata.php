<?php
class metafad_teca_MAG_views_components_SelectMetadata extends org_glizy_components_Component
{
  function init()
  {
    parent::init();
  }

  function render()
  {
    $mediaId = __Request::get('mediaId');

    $damService = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');
		$bytestream_url = $damService->mediaUrl($mediaId) . '?bytestream=true';

    $response = json_decode(file_get_contents($bytestream_url));

    $dataArray = array();
    if($response != false)
    {
      $bytestreams = $response->bytestream;
      foreach ($bytestreams as $value) {
        $bytestreamId = $value->id;
        $bytestreamName = $value->name;
        if($bytestreamName == 'original')
        {
          $imgUrl = $value->url;
        }
        if(!$bytestreamUrl)
        {
          $bytestreamUrl = $value->url;
        }
        $url = $damService->mediaUrl($mediaId) . '/bytestream/'.$bytestreamId.'/datastream/NisoImg';
        $response = json_decode(@file_get_contents($url));
        if($response)
        {
          $metadata = $response->NisoImg;

          if (!$metadata->id) {
            $docStruProxy = $this->_application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
            $exifUrl = $damService->mediaUrl($mediaId) . '/bytestream/'.$bytestreamId.'/datastream/Exif';
            $exif = json_decode(@file_get_contents($exifUrl));
            $niso = $docStruProxy->getNisoFromExif($exif);
            $metadata = $niso->NisoImg;
          }
          
          foreach ($metadata as $k => $v) {
            $dataArray[$bytestreamName][$k] = $v;
          }
          $dataArray[$bytestreamName]['groupid'] = $bytestreamName;
        }
      }
    }

    $output = '';
    $output .= '<form class="stickyForm"><div class="col-sm-6 metadata-left">
                  <div class="metadata-preview-container metadata-left-section">
                    <img class="metadata-preview" src="'.$imgUrl.'"/>
                  </div>
                  <div class="metadata-stream metadata-left-section">
                  <h2>Stream Disponibili</h2>';
    foreach ($dataArray as $key => $value) {
      $first = ($first) ? $first : $key;
      $checked = ($key == $first) ? 'checked' : '';
      $output .= '<div class="stream-name"><input type="radio" value="'.$key.'" name="stream" '.$checked.'/> '.$key.'</div>';
    }
    $output .= '</div></div>';

    $output .= '<div class="col-sm-6 metadata-right">
                <h2>Metadati NISO: <span class="js-stream-name">'.$first.'<span></h2>';
    foreach ($dataArray as $key => $value) {
      $count = 0;
      $classHide = ($key != $first) ? 'hide' : '';
      $output .= '<div class="'.$classHide.' stream-container" data-stream="'.$key.'">';
      foreach ($value as $k => $v) {
        if($k != 'id' && $k != 'get')
        {
          $k = ucfirst(str_replace("_"," ",$k));
          $output .= '<div class="metadata-key-value"><span class="metadata-key">'.$k.'</span> : '.$v.'</div>';
          $count++;
        }
      }
      if($count <= 1)
      {
        $output .= '<div class="metadata-key-value">Nessun metadato associato.</div>';
      }
      $output .= '</div>';
    }
    $output .= '</div>';

    //Pulsanti
    $output .= '<div class="formButtons fomrButtonsSBN">
                <div class="content">
                  <input class="btn btn-flat btn-info js-import" type="button" value="Conferma" />
                  <input class="btn btn-flat js-back" type="button" value="Annulla" />
                  </div>
                </div></form>';

    $output .= '<script> var metadataArray = new Array();';
    foreach ($dataArray as $key => $value) {
      $output .= 'metadataArray["'.$key.'"] = new Array();';
      foreach ($value as $k => $v) {
        if(!$v)
        {
          $v = 'undefined';
        }
        $output .= 'metadataArray["'.$key.'"]["'.$k.'"] = "'.$v.'";';
      }
    }
    $output .= '</script>';

    $this->addOutputCode($output);
  }
}
