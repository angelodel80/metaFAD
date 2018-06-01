<?php
class metafad_ecommerce_requests_views_helpers_ObjectInfoHelper extends GlizyObject
{
  public function getInfoFromMetaindex($id,$getLink=true,$isOrderItem=false,$orderItemData=null)
  {
    $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', __Config::get('metafad.metaindice.detail.url'), 'POST', 'id='.strtoupper($id), 'application/x-www-form-urlencoded');
    $request->setAcceptType('application/json');
    $request->execute();

    if(is_numeric($id)) {
      $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
      $record->load($id);
    }
    else {
      $record = org_glizy_objectFactory::createModelIterator('metafad.sbn.modules.sbnunimarc.model.Model')
                ->where('id',$id)->first();
    }
    $doc = json_decode($request->getResponseBody())->response->docs[0];
	//Istituto di riferimento dei media (non basta l'istituto del record,
	//perchè sbn è multiistituto
	$medias = json_decode($doc->attributes->Ecommerce)->medias;
	if($medias)
	{
		$instituteProxy = org_glizy_objectFactory::createObject('metafad_usersAndPermissions_institutes_models_proxy_InstitutesProxy');
		$media = $medias[0];
		$requestDam = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', __Config::get('gruppometa.dam.solr.url').'select', 'GET', 'q=id:'.$media->id.'&wt=json', 'application/x-www-form-urlencoded');
	    $requestDam->setAcceptType('application/json');
	    $requestDam->execute();
		$mediaFromSolr = json_decode($requestDam->getResponseBody())->response->docs[0];
		$instance = $instituteProxy->getInstituteVoByKey($mediaFromSolr->instance_s)->institute_name;

	}

    $html = '';
    if(!$doc)
    {
      return 'Nessun oggetto con l\'id in questione risulta presente. Probabilmente è stato cancellato. Si consiglia di eliminare la richiesta.';
    }

	$url = $this->getRouting($id,$record);

    $o = ($getLink) ? '<a target="_blank" href="'.$url.'">'.$id.'</a>' : $id;
    if($isOrderItem)
    {
      $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', '*');
      $image = ($orderItemData[1]) ? $dam->streamUrl($orderItemData[1],'thumbnail') : '';
      $divImage = ($image) ? '<div class="col-md-12 image-record"><img src="'.$image.'" /></div>' : '';
    }

    $html .= $divImage.'<div><div class="row row-obj"><div class="col-md-3 object-label">Codice oggetto:</div><div class="col-md-9">'.$o.'</div></div>';

	if($instance)
	{
		$html .= '<div><div class="row row-obj"><div class="col-md-3 object-label">Istituto responsabile:</div><div class="col-md-9">'.$instance.'</div></div>';
	}

	foreach ($doc->nodes[0]->nodes as $key => $value) {
      if($value->id == 'denominazione/titolo')
      {
        $html .= '<div class="row row-obj"><div class="col-md-3 object-label">Titolo:</div><div class="col-md-9"> '.implode(" | ",$value->values).'</div></div>';
      }
    }
    if($isOrderItem)
    {
      $html .= '<div class="row row-obj"><div class="col-md-3 object-label">Prezzo:</div><div class="col-md-9"> '.$orderItemData[4].' €</div></div>';
      $typeOrder = (!$orderItemData[1]) ? 'record intero': 'singola immagine';
      $html .= '<div class="row row-obj"><div class="col-md-3 object-label">Tipo acquisto:</div><div class="col-md-9">'.$typeOrder.'</div></div>';
    }
    $html .= '</div>';

    return $html;
  }

  public function getRouting($id,$ar)
  {
    $type = $ar->document_type;
    if(strpos($type,'archivi.') === 0)
    {
      return __Routing::makeUrl('archiviMVC', array(
        'id' => $id,
        'pageId' => $ar->pageId,
        'sectionType' => $ar->livelloDiDescrizione,
        'action' => 'edit'.($ar->getStatus() == 'DRAFT' ? "Draft" : "")));
    }
    else if($type == 'metafad.chviewer.modules.solr')
    {
      return __Routing::makeUrl('actionsMVC', array(
        'id' => $id,
        'pageId' => 'metafad.sbn.modules.sbnunimarc',
        'action' => 'show'));
    }
    else
    {
      return __Routing::makeUrl('actionsMVC', array(
        'id' => $id,
        'pageId' => strtolower($type),
        'action' => 'edit'));
    }
  }
}
