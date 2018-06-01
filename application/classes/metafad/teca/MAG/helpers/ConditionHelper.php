<?php
class metafad_teca_MAG_helpers_ConditionHelper extends GlizyObject
{
  public function checkCondition($data)
  {
    $errors = array();
    //Confronto obbligatorietà condizionale Bib_level & piece
    if($data->BIB_level === 's')
    {
      if(!$data->BIB_piece_year || !$data->BIB_piece_issue)
      {
        $errors['BIB_level'] = 'Il campo BIB Level è \'s\'. I campi "year" e "issue" di "piece" devono essere compilati';
      }
    }

    //Image metrics xsamplingfrequency e ysamplingfrequency
    $countx = 1;
    $county = 1;
    if($data->GEN_img_group)
    {
      foreach($data->GEN_img_group as $img_group)
      {
        $samplingfrequencyunit = $img_group->GEN_img_group_image_metrics_samplingfrequencyunit;
        if($samplingfrequencyunit == '2' || $samplingfrequencyunit == '3' )
        {
          if($img_group->GEN_img_group_ppi == '')
          {
            $xsamplingfrequency = $img_group->GEN_img_group_image_metrics_xsamplingfrequency;
            if(!$xsamplingfrequency)
            {
              $errors['xsamplingfrequency'] = 'Il campo xsamplingfrequency è applicabile, ma non è stato valorizzato. ('.$countx.' occorrenza/e)';
              $countx++;
            }
            $ysamplingfrequency = $img_group->GEN_img_group_image_metrics_ysamplingfrequency;
            if(!$ysamplingfrequency)
            {
              $errors['ysamplingfrequency'] = 'Il campo ysamplingfrequency è applicabile, ma non è stato valorizzato. ('.$county.' occorrenza/e)';
              $county++;
            }
          }
        }
      }
    }

    //Preg match su BIB/piece/stpiece_per
    if($data->BIB_piece_stpiece_per)
    {
      $match = preg_match('@\((\d{4}(/\d{4})?(([0-1][1-9])(/[0-1][1-9])?([1-31](/[1-31])?)?|(([21-24](/[21-24])?)|([31-34](/[31-34])?))*))?(/\d{4}(([0-1][1-9])([1-31])*|([21-24]|[31-34])*)?)?\)\d{1,4}(\:(\d{1,4}(/\d{1,4})?))*@',$data->BIB_piece_stpiece_per);
      if($match == 0)
      {
        $errors['stpiece_per'] = 'Il campo BIB/piece/stpiece_per non rispetta il pattern';
      }
    }

    //Preg match su BIB/piece/stpiece_per
    if($data->BIB_piece_stpiece_vol)
    {
      $match = preg_match('@\d{1,3}\:\d{1,4}(\:\d{1,4})*@',$data->BIB_piece_stpiece_vol);
      if($match == 0)
      {
        $errors['stpiece_vol'] = 'Il campo BIB/piece/stpiece_vol non rispetta il pattern';
      }
    }

    return $errors;
  }

  public function checkMediaCondition($data,$type)
  {
    $errors = array();
    if($type === 'img')
    {
      if($data->imggroupID == '' && ($data->samplingfrequencyunit == '' || $data->samplingfrequencyplane == '' || $data->bitpersample == '' || $data->photometricinterpretation == ''))
      {
        $errors['image_metrics'] = 'Il fieldset Image Metrics deve essere valorizzato se non si utilizza un Img Group (campo Group)';
      }

      if($data->ppi == '')
      {
        if($data->samplingfrequencyunit == 2 || $data->samplingfrequencyunit == 3)
        {
          if($data->xsamplingfrequency == '')
          {
            $errors['xsamplingfrequency'] = 'Il campo xsamplingfrequency deve essere valorizzato se "ppi" è nullo e se "samplingfrequencyunit" ha valore 2 o 3';
          }
          if($data->ysamplingfrequency == '')
          {
            $errors['ysamplingfrequency'] = 'Il campo xsamplingfrequency deve essere valorizzato se "ppi" è nullo e se "samplingfrequencyunit" ha valore 2 o 3';
          }
        }
      }
    }

    if($type === 'audio')
    {
      $proxies = $data->proxies;
      foreach ($proxies as $key => $proxy) {
        if($proxy->audiogroupID == '' && ($proxy->samplingfrequency == '' || $proxy->bitpersample == '' || $proxy->bitrate == ''))
        {
          $errors['audiometrics'.$key] = 'Il fieldset Audio Metrics deve essere valorizzato se non si utilizza un Audio Group ID (errore verificatosi nel proxy N° '.($key+1).')';
        }
      }
    }

    if($type === 'video')
    {
      $proxies = $data->proxies;
      foreach ($proxies as $key => $proxy) {
        if($proxy->videogroupID == '' && ($proxy->videosize == '' || $proxy->aspectratio == '' || $proxy->framerate == ''))
        {
          $errors['videometrics'.$key] = 'Il fieldset Video Metrics deve essere valorizzato se non si utilizza un Video Group ID (errore verificatosi nel proxy N° '.($key+1).')';
        }
      }
    }

    return $errors;
  }
}
