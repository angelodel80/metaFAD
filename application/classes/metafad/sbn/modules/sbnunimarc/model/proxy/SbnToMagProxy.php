<?php
class metafad_sbn_modules_sbnunimarc_model_proxy_SbnToMagProxy extends GlizyObject
{
  public function getMappedField($bid)
  {
    $url = __Config::get('metafad.sbnToMag.url') . $bid;

    $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
    $i = $instituteProxy->getInstituteVoByKey(__Session::get('usersAndPermissions.instituteKey'));
    if ($i->institute_prefix) {
      $url = $url . '&biblioteca=' . $i->institute_prefix;
    }

    $xml = new DomDocument();
    $xml->loadXML(file_get_contents($url));

    $array = array();
    $elementsList = array(
      'identifier',
      'title',
      'creator',
      'publisher',
      'subject',
      'description',
      'contributor',
      'date',
      'type',
      'format',
      'source',
      'language',
      'relation',
      'coverage',
      'rights',
      'holdings',
    );

    $bibLevel = $xml->getElementsByTagName('bib')->item(0);
    $array['BIB_level'] = ($bibLevel) ? $bibLevel->getAttribute('level') : null;

    foreach ($elementsList as $el) {
      $elements = $xml->getElementsByTagName($el);
      foreach ($elements as $e) {
        if ($e->nodeName == 'mag:holdings') {
          $holdings = new StdClass();

          $nodes = $e->getElementsByTagName('library');
          $holdings->BIB_holdings_library = $nodes[0]->nodeValue;

          $nodes = $e->getElementsByTagName('inventory_number');
          $holdings->BIB_holdings_inventory_number = $nodes[0]->nodeValue;

          $nodes = $e->getElementsByTagName('selfmark');
          $shelfmark = new STdClass();
          $shelfmark->BIB_holdings_shelfmark_value = $nodes[0]->nodeValue;
          $holdings->BIB_holdings_shelfmark[] = $shelfmark;

          $array['BIB_holdings'][] = $holdings;
        } else {
          $array['BIB_dc_' . $el][] = $e->nodeValue;
        }
      }
    }

    if ($array['BIB_level'] == null) {
      return null;
    }
    return $array;
  }

  public function getMappedFieldObjects($bid)
    {
      $url = __Config::get('metafad.sbnToMag.url').$bid;

      $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
      $i = $instituteProxy->getInstituteVoByKey(__Session::get('usersAndPermissions.instituteKey'));
      if($i->institute_prefix)
      {
        $url = $url.'&biblioteca='.$i->institute_prefix;
      }

      $xml = new DomDocument();
      $xml->loadXML(@file_get_contents($url));
      
      $array = array(
          'BIB_level' => '',
          'BIB_dc_identifier' => array(),
          'BIB_dc_title' => array(),
          'BIB_dc_creator' => array(),
          'BIB_dc_publisher' => array(),
          'BIB_dc_subject' => array(),
          'BIB_dc_description' => array(),
          'BIB_dc_contributor' => array(),
          'BIB_dc_date' => array(),
          'BIB_dc_type' => array(),
          'BIB_dc_format' => array(),
          'BIB_dc_source' => array(),
          'BIB_dc_language' => array(),
          'list_options' => '',
          'BIB_dc_coverage' => array(),
          'BIB_dc_rights' => array(),
          'BIB_holdings' => array(),
          'BIB_local_bib_geo_coord' => array(),
          'BIB_local_bib_not_date' => array(),
          'BIB_holdings' => array(),
          'BIB_piece_year' => '',
          'BIB_piece_issue' => '',
          'BIB_piece_stpiece_per' => '',
          'BIB_piece_part_number' => '',
          'BIB_piece_part_name' => '',
          'BIB_piece_stpiece_vol' => '',
      );

      $elementsList = array(
        'identifier',
        'title',
        'creator',
        'publisher',
        'subject',
        'description',
        'contributor',
        'date',
        'type',
        'format',
        'source',
        'language',
        'relation',
        'coverage',
        'rights',
        'holdings',
      );

      $bibLevel = $xml->getElementsByTagName('bib')->item(0);
      $array['BIB_level'] = ($bibLevel) ? $bibLevel->getAttribute('level') : null;

      foreach ($elementsList as $el) {
        $elements =$xml->getElementsByTagName($el);
        foreach ($elements as $e) {
          if ($e->nodeName == 'mag:holdings') {
            $holdings = new StdClass();

            $nodes = $e->getElementsByTagName('library');
            $holdings->BIB_holdings_library = $nodes[0]->nodeValue;

            $nodes = $e->getElementsByTagName('inventory_number');
            $holdings->BIB_holdings_inventory_number = $nodes[0]->nodeValue;

            $nodes = $e->getElementsByTagName('selfmark');
            $shelfmark = new StdClass();
            $shelfmark->BIB_holdings_shelfmark_value = $nodes[0]->nodeValue;
            $holdings->BIB_holdings_shelfmark[] = $shelfmark;

            $array['BIB_holdings'][] = $holdings;
          } else {
            $obj = new StdClass();
            $obj->{'BIB_dc_'.$el.'_value'} = $e->nodeValue;
            $array['BIB_dc_'.$el][] = $obj;
          }
        }
      }

      if($array['BIB_level'] == null)
      {
        return array();
      }
      return $array;
    }
}
