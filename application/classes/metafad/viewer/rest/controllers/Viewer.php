<?php
class metafad_viewer_rest_controllers_Viewer extends org_glizy_rest_core_CommandRest
{
	private $ecommerceInfo;
	private $dam;
	private $viewerHelper;

	protected function loadLinkedStruMag($id, $init, $num, $linkedStruMag, $doc)
	{
		$instituteKey = (is_array($linkedStruMag)) ? $linkedStruMag['instituteKey'] : $linkedStruMag->instituteKey;
		$this->dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($instituteKey));
		$null = null;
		$strumagId = (is_array($linkedStruMag)) ? $linkedStruMag['id'] : $linkedStruMag->id;
		$s = $this->getStrumag($strumagId);
		
		$ps = json_decode($s->physicalSTRU);
		$doc->physicalSTRU['tot'] = sizeof($ps->image);
		
		$images = ($folder) ? $this->filterFolder($ps->image,$folder) : $ps->image;
		if($folder)
		{
			$doc->physicalSTRU['totFolder'] = sizeof($images);
		}
		
		$this->iterateImage($doc->physicalSTRU['image'],$init,$num,$images,'ps',$id);
		$doc->logicalSTRU = json_decode($s->logicalSTRU);
	}

	function execute($id)
	{
		$this->viewerHelper = org_glizy_objectFactory::createObject('metafad.viewer.helpers.ViewerHelper');

		$init = (__Request::get('init')) ? intval(__Request::get('init')) : 0;
		$num = (__Request::get('num')) ? intval(__Request::get('num')) : 'all';
		$onlyItems = (__Request::get('onlyItems') == 'true') ? true : false;
		$folder = __Request::get('folder');
		$type = __Request::get('type');

		if(!$type)
		{
			return array('error'=>'Parametro "type" non definito');
		}

		$doc = new stdClass();
		$doc->id = $id;
		$doc->MAG = '';
		$doc->state = null;
		$doc->physicalSTRU = array();
		$doc->logicalSTRU = array();

		//Il $type deve avere un valore valido
		if($type == 'sbn') {
			//$this->dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', '*');
			$record = org_glizy_objectFactory::createModelIterator('metafad.sbn.modules.sbnunimarc.model.Model')
			->where('id',$id)->first();
			$doc->title = $record->id . ' - ' . $record->title[0];
			$doc->licenses = $this->getGlobalLicenseDetail($record->getRawData()->ecommerceLicenses);
			if($record->linkedStruMag) {
				$struMagId = (is_array($record->linkedStruMag)) ? $record->linkedStruMag['id'] : $record->linkedStruMag->id;
				$linkedStruMag = $this->getStrumag($struMagId);
				$this->loadLinkedStruMag($id, $init, $num, $linkedStruMag, $doc);
			} else if($record->linkedMedia) {
				$instituteKey = (is_array($record->linkedMedia[0])) ? $record->linkedMedia[0]['instituteKey'] : $record->linkedMedia[0]->instituteKey;
				$this->dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($instituteKey));
				$this->iterateImage($doc->physicalSTRU['image'],$init,$num,$record->linkedMedia,$type,$id);
				$doc->physicalSTRU['tot'] = sizeof($record->linkedMedia);
			} else if($record->linkedInventoryMedia) {
				$instituteKey = (is_array($record->linkedInventoryMedia[0])) ? $record->linkedInventoryMedia[0]['instituteKey'] : $record->linkedInventoryMedia[0]->instituteKey;
				$this->dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($instituteKey));
				$this->iterateImage($doc->physicalSTRU['image'],$init,$num,$record->linkedInventoryMedia[0]->media,'inventory',$id);
				$doc->physicalSTRU['tot'] = sizeof($record->linkedInventoryMedia[0]->media);
			} else if($record->linkedInventoryStrumag) {
				$null = null;
				$instituteKey = (is_array($record->linkedInventoryStrumag[0])) ? $record->linkedInventoryStrumag[0]['instituteKey'] : $record->linkedInventoryStrumag[0]->instituteKey;
				$this->dam = $this->viewerHelper->initializeDam($this->viewerHelper->getKey($instituteKey));

				$strumagId = $record->linkedInventoryStrumag[0]->linkedStruMagToInventory->id;
				$s = $this->getStrumag($strumagId);

				$ps = json_decode($s->physicalSTRU);
				$doc->physicalSTRU['tot'] = sizeof($ps->image);

				$images = ($folder) ? $this->filterFolder($ps->image,$folder) : $ps->image;
				if($folder)
				{
					$doc->physicalSTRU['totFolder'] = sizeof($images);
				}

				$this->iterateImage($doc->physicalSTRU['image'],$init,$num,$images,'ps',$id);
				$doc->logicalSTRU = json_decode($s->logicalSTRU);
			} else {
				$kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
				$struMagId = $kardexService->getFirstStruMagId($id);
				if ($struMagId) {
					$linkedStruMag = $this->getStrumag($struMagId);
					$this->loadLinkedStruMag($id, $init, $num, $linkedStruMag, $doc);
				}
			}

			$doc->info = ($this->getInfoFromMetaindex($id)) ?: $doc->title;
		}
		else if($type == 'iccd'){
			//Ottengo il record, in particolare FTA in caso di scheda ICCD
			//Non invio stru logica in quanto non collego strumag alle iccd
			$record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
			if($record->load($id)){
				$ar = org_glizy_objectFactory::createModel($record->getType().'.models.Model');
				$ar->load($id);
				$doc->licenses = $this->getGlobalLicenseDetail($ar->getRawData()->ecommerceLicenses);
				$instituteKey = ($ar->getRawData()->instituteKey) ?:'*';
				$this->dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);
				$data = $ar->getRawData()->FTA;
				$this->iterateImage($doc->physicalSTRU['image'],$init,$num,$data,$type,$id,$doc->physicalSTRU['tot']);
			}
			else {
				return array('error'=>'Nessun record trovato');
			}
			$arraySub = array('SGLT'=>'SGL','SGTI'=>'SGT','SGTP'=>'SGT');
			$titleKey = current(explode(',',str_replace(array('_s','_t'),array('',''),end($ar->getSolrDocument()))));

			if(strpos($titleKey,',') !== false)
			{
				$titleKey = end(explode(',',$titleKey));
			}
			$e = $ar->$arraySub[$titleKey];
			$val = $e[0]->$titleKey;
			if(is_array($val))
			{
				$val = $val[0]->{$titleKey.'-element'};
			}
			if($val == null)
			{
				$val = 'Senza titolo';
			}

			$doc->title = $val;
			$doc->info = ($this->getInfoFromMetaindex($id)) ?: $val;
		}
		else if($type == 'archive')
		{
			$record = org_glizy_objectFactory::createModel('archivi.models.Model');
			if($record->load($id)){
				$instituteKey = ($record->getRawData()->instituteKey) ?:'*';
				$this->dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);
				$ar = org_glizy_objectFactory::createModel($record->document_type);
				$ar->load($id);
				$doc->licenses = $this->getGlobalLicenseDetail($ar->getRawData()->ecommerceLicenses);
				$record = $ar->getRawData();
				if($record->linkedStruMag)
				{
					$null = null;

					$strumagId = $record->linkedStruMag->id;
					$s = $this->getStrumag($strumagId);

					$ps = json_decode($s->physicalSTRU);
					$doc->physicalSTRU['tot'] = sizeof($ps->image);

					$images = ($folder) ? $this->filterFolder($ps->image,$folder) : $ps->image;
					if($folder)
					{
						$doc->physicalSTRU['totFolder'] = sizeof($images);
					}

					$this->iterateImage($doc->physicalSTRU['image'],$init,$num,$images,'ps',$id);
					$doc->logicalSTRU = json_decode($s->logicalSTRU);
				}
				else if($record->mediaCollegati)
				{
					$this->setSingleImage($doc->physicalSTRU['image'],$record->mediaCollegati);
					$doc->physicalSTRU['tot'] = sizeof($record->linkedMedia);
				}
				$doc->info = $this->getInfoFromMetaindex($id);
			}
			else {
				return array('error'=>'Nessun record trovato');
			}
		}
		else if($type == 'kardex')
		{
			$s = $this->getStrumag($id);
			$this->dam = $this->viewerHelper->initializeDam($s->instituteKey);
			
			$ps = json_decode($s->physicalSTRU);
			$doc->physicalSTRU['tot'] = sizeof($ps->image);

			$images = ($folder) ? $this->filterFolder($ps->image, $folder) : $ps->image;
			if ($folder) {
				$doc->physicalSTRU['totFolder'] = sizeof($images);
			}

			$this->iterateImage($doc->physicalSTRU['image'], $init, $num, $images, 'ps', $id);
			$doc->logicalSTRU = json_decode($s->logicalSTRU);
		}
		else {
			return array('error'=>'Il type indicato non ha corrispondenza');
		}
		if($onlyItems || empty($doc->logicalSTRU))
		{
			unset($doc->logicalSTRU);
		}
		return $doc;
	}

	function getGlobalLicenseDetail($licenses)
	{
		if($licenses)
		{
			$appoggio = array();
			foreach ($licenses as $l) {
				$licenseProxy = org_glizy_objectFactory::createObject('metafad_ecommerce_licenses_models_proxy_LicensesProxy');
				$license = $licenseProxy->getDetailFromId($l->id);
				$appoggio[] = $license;
			}
			return $appoggio;
		}
		else {
			return null;
		}
	}

	function iterateImage(&$physicalSTRU,$init,$num,$data,$type,$id,&$tot = null,$folder = null)
	{
		$count = -1;
		$total = 0;
		//In caso di scheda ICCD non è detto che FTA abbia effettivamente un'immagine allegata
		//quindi in fase di conteggio vanno esclusi questi casi
		if($type == 'iccd')
		{
			foreach($data as $v)
			{
				if($v->{"FTA-image"})
				{
					$tot++;
				}
			}
		}
		foreach($data as $v)
		{
			$count++;
			if($count < $init)
			{
				continue;
			}
			if($total >= $num && $num != 'all')
			{
				break;
			}
			if($type == 'iccd')
			{
				$d = json_decode($v->{"FTA-image"});
				if(!$d)
				{
					$count--;
					continue;
				}
			}
			else if($type == 'ps')
			{
				$d = $v;
			}
			else if($type == 'inventory')
			{
				$d = json_decode($v->mediaInventory);
			}
			else
			{
				$d = json_decode($v->media);
			}

			$image = array();

			if($this->dam->getInstance() == '*')
			{
				$this->correctInstance($d->id);
			}

			$image['thumbnail'] =  metafad_teca_DAM_Common::replaceUrl($this->dam->streamUrl($d->id,'thumbnail'));
			$image['title'] =  $d->title;
			$image['id'] =  $d->id;
			$image['type'] =  ($d->type)?:'IMAGE';
			$image['url'] = $this->dam->streamUrl($d->id,'original');
			$image['label'] =  ($d->label) ?: $title;
			$image['tile'] = $this->zoomSrc($this->dam->streamUrl($d->id,'original'));
			$image['ecommerce'] = $this->getEcommerceInfo($id, $d->id);
			if($d->keyNode)	{
				$image['keyNode'] = $d->keyNode;
			}
			if($d->aliasKeyNode) {
				$image['aliasKeyNode'] = $d->aliasKeyNode;
			}
			$physicalSTRU[] = $image;
			$total++;
		}
	}

	function setSingleImage(&$physicalSTRU,$v)
	{
		$d = json_decode($v);
		$image = array();

		$image['thumbnail'] =  metafad_teca_DAM_Common::replaceUrl($this->dam->streamUrl($d->id,'thumbnail'));
		$image['title'] =  $d->title;
		$image['id'] =  $d->id;
		$image['type'] = ($d->type)?:'IMAGE';
		$image['url'] = $this->dam->streamUrl($d->id,'original');
		$image['label'] = ($d->label) ?: $title;
		$image['tile'] = $this->zoomSrc($this->dam->streamUrl($d->id,'original'));
		if($d->keyNode)
		{
			$image['keyNode'] = $d->keyNode;
		}
		$physicalSTRU[] = $image;
	}

	function filterFolder($data,$folder)
	{
		$array = array();
		foreach ($data as $value) {
			if($value->keyNode == $folder)
			{
				$array[] = $value;
			}
		}
		return $array;
	}

	function getInfoFromMetaindex($id)
	{
		$request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', __Config::get('metafad.metaindice.detail.url'), 'POST', 'id='.$id, 'application/x-www-form-urlencoded');
		$request->setAcceptType('application/json');
		$request->execute();

		$doc = json_decode($request->getResponseBody())->response->docs[0];
		$html = '';
		$title = '';
		if(!$doc) {
			return $html;
		}
		foreach ($doc->nodes[0]->nodes as $key => $value) {
			if ($value->id == 'denominazione/titolo') {
				$title .= '<h3>'.$value->values[0].'</h3>';
			} else if ($value->id == 'responsabilita') {
				$html .= '<p class="value"><span class="label">'.__T($value->id).'</span>'.$value->values[0].'</p>';
			} else {
				$html .= '<p class="value"><span class="label">'.__T($value->id).'</span>';
				foreach ($value->values as $v) {
					$html .= $v.'<br>';
				}
				$html .= '</p>';
			}
		}

		return $title.$html;
	}

	public function getStrumag($id)
	{
		$linkedStru = new stdClass();
		$stru = org_glizy_objectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model')
			  ->where('document_id',$id)->first();

		if($stru)
		{
			$stru->getRawData();
			$linkedStru->id = $stru->getId();
			$linkedStru->physicalSTRU = $stru->physicalSTRU;
			$linkedStru->logicalSTRU = $this->checkLS($stru->logicalSTRU);
			$linkedStru->instituteKey = $stru->instituteKey;

			return $linkedStru;
		}
		else
		{
			return array('error'=>'Nessun record trovato');
		}
	}

	private function zoomSrc($src)
	{
		return __Config::get('metafad.FE.url').'/zoom.php?id='.preg_replace('/^(.*)\/([^\/]*)(\/get\/)([^\?]*)(\?timestamp=\d*)?/', '$2$3$4', $src);
	}

	private function checkLS($logicalSTRU)
	{
		$ls = json_decode($logicalSTRU);
		if(count($ls) == 1 && $ls[0]->key == 'exclude') {
			return null;
		}
		else {
			return $logicalSTRU;
		}
	}

	private function getEcommerceInfo($id,$mediaId)
	{
		if(!$this->ecommerceInfo)
		{
			$url = __Config::get('metafad.solr.metaindice.url');
			$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.'select/?q=id:'.$id.'&wt=json', 'GET', '', 'application/json');
			$r->execute();

			if(json_decode($r->getResponseBody())->response->numFound >= 1) {
				$this->ecommerceInfo = json_decode(json_decode($r->getResponseBody())->response->docs[0]->ecommerce_nxs[0]);
			}
			else {
				return false;
			}
		}

		if($this->ecommerceInfo->medias)
		{
			foreach ($this->ecommerceInfo->medias as $m) {
				if($m->id == $mediaId)
				{
					return json_encode($m->license);
				}
			}
		}
		else
		{
			return false;
		}
	}

	public function correctInstance($id)
	{
		$url = __Config::get('gruppometa.dam.solr.url');
		$r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.'select/?q=id:'.$id.'&wt=json', 'GET', '', 'application/json');
		$r->execute();
		if($r->getResponseBody())
		{
			$realInstance = json_decode($r->getResponseBody())->response->docs[0]->instance_s;
			$this->dam = $this->viewerHelper->initializeDam($realInstance);
		}
	}
}
