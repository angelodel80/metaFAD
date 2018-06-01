<?php

class metafad_solr_helpers_ArchiveFEHelper extends GlizyObject
{
	private $appoggio = array();
	private $mappingFile;
	private $fieldsLabel;
	private $mappingHelper;
	private $labels;
	private $translationLabels = array();
	private $arrayECTCase = array('contestoStatuale'=>array('ect'=>'cont_stat_cronologia','field'=>'estremoCronologicoTestuale'),
								  'titoloEntita'=>array('ect'=>'titolo_cronologia','field'=>'estremoCronologicoTestuale'),
							  	  'attivitaProfessioneQualifica'=>array('ect'=>'att_prof_qual_cronologia','field'=>'estremoCronologicoTestuale'));
	private $arrayPrincipalECT = array('cronologiaPersona','cronologiaFamiglia','cronologiaEnte');
	private $isECT = false;
	private $isPrincipalECT = false;
	private $isEntity = false;
	/**
	* @param $data stdClass dati da mappare in front-end e da salvare
	* @param string $option Nome dell'opzione da inoltrare al listener (mettere 'queue' se si vuole accodare la richiesta
	* senza commit)
	*/
	public function mappingFE($data, $option = 'commit')
	{
		$indexOnMeta = false;
		$model = $data->__model;
		$this->fieldsLabel = new stdClass();
		$thrower = new GlizyObject();
		$docForSolr = new stdClass();
		$hasImageHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.HasImageHelper');
		$this->mappingHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.MappingHelper');

		//Valori per traduzioni etichette in FE (secondo indicazioni su POLODEBUG sparse)
		$this->labels = (array)json_decode(file_get_contents(__Paths::get('APPLICATION_TO_ADMIN') . 'classes/metafad/solr/json/labelsArchive.json'));

		if ($model == 'archivi.models.ComplessoArchivistico') {
			$mappingFilePath = __Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/archivi/json/mappingCA.json';
			$fieldsForHtml = __Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/archivi/json/ComplessoArchivistico.json';
			$complex = true;
			$docForSolr->type_nxs = 'archiveCA';
			$visibility = $this->checkVisibility($data);
			if($visibility !== '0'){
				$docForSolr->visibility_nxs = $visibility;
			}
			else {
				return;
			}
		} else if ($model == 'archivi.models.UnitaDocumentaria' || $model == 'archivi.models.UnitaArchivistica') {
			$ecommerceHelper = org_glizy_ObjectFactory::createObject('metafad_ecommerce_helpers_EcommerceToSolrHelper');
			$mappingFilePath = __Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/archivi/json/mappingU.json';
			$fieldsForHtml = ($model == 'archivi.models.UnitaDocumentaria') ?
			__Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/archivi/json/UnitaDocumentaria.json' :
			__Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/archivi/json/UnitaArchivistica.json';
			$complex = false;
			$docForSolr->type_nxs = 'archiveUN';
			$visibility = $this->checkVisibility($data);
			if($visibility !== '0'){
				$docForSolr->visibility_nxs = $visibility;
			}
			else {
				return;
			}
			//Info ecommerce
			$ecommerceInfo = $ecommerceHelper->getEcommerceInfo($data,'archive');
			if($ecommerceInfo){
				$docForSolr->ecommerce_nxs = $ecommerceInfo;
			}

			$metaindiceHelper = org_glizy_objectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
			$area_digitale = $metaindiceHelper->mappingDigitalField($data, 'archive');
			if ($area_digitale) {
				$docForSolr->area_digitale_ss = $area_digitale;
				$docForSolr->area_digitale_txt = $area_digitale;
			}

			//Informazione sulla presenza o meno di digitale collegato
			$hasImage = $hasImageHelper->hasImage($data, 'archive');
			if ($hasImage) {
				$docForSolr->digitale_s = 'true';
			}

			$indexOnMeta = true;
		} else if ($model == 'archivi.models.ProduttoreConservatore') {
			//TODO per ora si genera solo html, generare poi i campi per indice specifico
			$fieldsForHtml = __Paths::get('APPLICATION_TO_ADMIN') . 'classes/userModules/archivi/json/ProduttoreConservatore.json';
			$json = json_decode(file_get_contents($fieldsForHtml));
			$this->getFieldsLabel($json);
			$this->isEntity = true;
			$fieldList = $this->getFields($json);
			unset($data->persona_denominazione[0]->persona_linguaDenominazione);
			$docHtml = $this->generateHtml($data, $fieldList);
			$docForSolr->titolo_s = $this->getAuthorTitle($data);
			$docForSolr->titolo_t = $docForSolr->titolo_s;

			$docForSolr->titolo_nodata_s = $this->getAuthorTitle($data,false);
			$docForSolr->titolo_nodata_t = $docForSolr->titolo_nodata_s;

			foreach ($docHtml as $key => $value) {
				$docForSolr->$key = $value;
			}

			$docForSolr->id = $data->__id;
			$evt = array('type' => 'insertData', 'data' => array('data' => $docForSolr, 'option' => array(($option === "queue" ? "queue" : "commit") => true, 'url' => __Config::get('metafad.solr.archiveaut.url'))));
			$thrower->dispatchEvent($evt);

			$metaindice = org_glizy_ObjectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
			$metaindice->mapping($data, 'archiveaut', $option);
			return;
		} else {
			return;
		}

		$this->mappingFile = json_decode(file_get_contents($mappingFilePath));
		$fields = $this->mappingFile->$model;

		$docForSolr->id = $data->__id;

		if ($model == 'archivi.models.UnitaDocumentaria' || $model == 'archivi.models.UnitaArchivistica')
		{
			//Prendo le info per la prima immagine da avere in SOLR
			$fi = org_glizy_objectFactory::createObject('metafad.viewer.helpers.FirstImage');
			$firstImage = $fi->execute($docForSolr->id,'archive');
			if($firstImage)
			{
				$docForSolr->digitale_idpreview_s = $firstImage['firstImage'];
				$docForSolr->digitale_idpreview_t = $firstImage['firstImage'];
			}
		}

		$docForSolr->institutekey_s = $data->instituteKey ? $data->instituteKey : metafad_usersAndPermissions_Common::getInstituteKey();

		$updateDateTime = new DateTime();
		$docForSolr->update_at_s = $updateDateTime->format('Y-m-d H:i:s');

		$doc = $this->indexArchive($data, $fields, $complex);

		foreach ($doc as $key => $value) {
			$label = str_replace(array(":", '(', ')', '/'), '_', strtolower($key));
			$label = str_replace(' ', '_', $label);

			if($label !== 'autoreruolo_html_nxtxt')
			{
				$docForSolr->{$label . '_ss'} = $value;
				$docForSolr->{$label . '_txt'} = $value;
			}
			else {
				$docForSolr->$label = $value;
			}
		}

		$json = json_decode(file_get_contents($fieldsForHtml));
		$this->getFieldsLabel($json);

		$fieldList = $this->getFields($json);
		$docHtml = $this->generateHtml($data, $fieldList);

		foreach ($docHtml as $key => $value) {
			if(!ctype_space($value) && $value != '')
			{
				$docForSolr->$key = $value;
			}
		}

		$unitTypes = array('unita'=>'unità','sottounita'=>'sottounità','sottosottounita'=>'sottosottounità');
		foreach ($docForSolr as $key => $value) {
			if(strpos($key,'livello_descrizione') !== false)
			{
				$appoggio = array();
				foreach ($value as $v) {
					$appoggio[] = (array_key_exists($v,$unitTypes)) ? $unitTypes[$v] : $v ;
				}
				$docForSolr->$key = $appoggio;
			}
			if(strpos($key,'livelloDiDescrizione') !== false)
			{
				$docForSolr->$key = (array_key_exists($docForSolr->$key,$unitTypes)) ? $unitTypes[$docForSolr->$key] : $docForSolr->$key;
			}
		}

		if ($data->root != 'true') {
			$firstLevel = $this->getFirstLevel($data);
			$docForSolr->primo_livello_id_s = $firstLevel['id'];
			$docForSolr->primo_livello_label_s = $firstLevel['text'];
		}

		$evt = array('type' => 'insertData', 'data' => array('data' => $docForSolr, 'option' => array(($option === "queue" ? "queue" : "commit") => true, 'url' => __Config::get('metafad.solr.archive.url'))));
		$thrower->dispatchEvent($evt);

		if ($indexOnMeta) {
			$metaindice = org_glizy_ObjectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
			$metaindice->mapping($data, 'archive', $option, $docForSolr, $ecommerceInfo);
		}
	}

	public function checkVisibility($data)
	{
		if($data->visibility === '0')
		{
			//Cancellazione da FE
			$archProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
			$archProxy->delete($data->__id,true,false,true);
		}
		return $data->visibility;
	}

	public function getFieldsLabel($json)
	{
		$tabs = $json->tabs;
		foreach ($tabs as $t) {
			foreach ($t->fields as $f) {
				$this->fieldsLabel->{$f->id} = $f->label;
				if ($f->children) {
					$this->getChildrenLabel($f->children);
				}
			}
		}
	}

	public function getChildrenLabel($children)
	{
		foreach ($children as $f) {
			$this->fieldsLabel->{$f->id} = ($f->label) ? $f->label : $f->attributes->label;
			if ($f->children) {
				$this->getChildrenLabel($f->children);
			}
		}
	}

	public function getFields($json)
	{
		$fieldList = array();
		foreach ($json->tabs as $tab) {
			if ($tab->id == 'tabMediaCollegati') {
				continue;
			}
			$label = $tab->id;
			$fields = $tab->fields;
			foreach ($fields as $field) {
				$this->getTranslation($field);
				$type = $field->type;

				if ($type == 'Fieldset' && $field->children) {
					foreach ($field->children as $c) {
						$fieldLabel = $label . '_' . $c->id;
						$fieldName = $c->id;
						$fieldList[$fieldName] = array('originalLabel' => $c->label, 'label' => $fieldLabel, 'type' => $c->type);
					}
				} else {
					$fieldLabel = $label . '_' . $field->id;
					$fieldName = $field->id;
					$fieldList[$fieldName] = array('originalLabel' => $c->label, 'label' => $fieldLabel, 'type' => $field->type);
				}
			}
		}
		return $fieldList;
	}

	private function getTranslation($field)
	{
		if($field->children)
		{
			foreach ($field->children as $child)
			{
				$label = '';
				if(!$child->label)
				{
					if($child->attributes)
					{
						$label = $child->attributes->label;
					}
				}
				$this->translationLabels[$child->id] = ($label != '') ? $label : $child->label;
				if($child->children)
				{
					$this->getTranslation($child);
				}
			}
		}
	}

	private function recursiveHtmlGeneration($item, $name, &$valList, $originalLabel){
		$isObject = is_object($item);
		$item = is_object($item) ? json_decode(json_encode($item), true) : $item;
		if ($name != $originalLabel) {
			$html = '<div class="label">' . $this->mappingHelper->translateLabel($name,$this->labels,$this->translationLabels) . '</div>';
		}
		$htmlAppoggio = '';
		if (!is_array($item)){
			$htmlAppoggio .= htmlentities($item);
			$valList[] = htmlentities($item);
		}
		else if($isObject)
		{
			if($name === 'Collegamento Soggetto Produttore')
			{
				$textArray = explode("||",htmlentities($item['text']));
				$text = $textArray[1] . ' || '. $textArray[2];
				$htmlAppoggio .= $this->createLink($item['id'],$text);
				$valList[] = $htmlAppoggio;
			}
			else {
				$htmlAppoggio .= htmlentities($item['text']);
				$valList[] = htmlentities($item['text']);
			}
		}
		else {
			if($item[0] && !$this->isECT)
			{
				foreach($item as $itemDetail)
				{
					foreach ($itemDetail as $k => $v){
						$htmlAppoggio .= $this->recursiveHtmlGeneration($v, $k, $valList, $originalLabel);
					}
				}
			}
			else if(!$this->isECT)
			{
				foreach ($item as $k => $v){
					$htmlAppoggio .= $this->recursiveHtmlGeneration($v, $k, $valList, $originalLabel);
				}
			}
		}

		if(!ctype_space($htmlAppoggio) && $htmlAppoggio != '') {
			$html .= '<div class="value">'.$htmlAppoggio.'</div>';
		}
		else {
			return '';
		}

		return $html;
	}

	private function indexVoicesHtml($item, $name, &$valList){
		$item = is_object($item) ? json_decode(json_encode($item), true) : $item;
		$html = '';
		if($item['text'])
		{
			$html .= '<div class="value">';

			$html .= $item['text'];

			$html .= '</div>';
		}
		return $html;
	}

	public function generateHtml($data, $fieldList)
	{
		$indexVoices = array('antroponimi','enti','toponimi','descrittori');
		$doc = new stdClass();
		foreach ($fieldList as $key => $value) {
			if($value['type'] == 'Hidden')
			{
				continue;
			}
			$type = $value['type'];
			$label = $value['label'];
			$originalLabel = $value['originalLabel'];
			//Gestione Link
			if ($data->$key) {
				if ($value['type'] == 'Link' && ($key == 'soggettoProduttore' || $key == 'soggettoConservatore')) {
					$textArray = explode("||",$data->$key->text);
					$text = $textArray[1] . ' || '. $textArray[2];
					$doc->{$label . '_html_nxt'} = $this->createLink($data->$key->id,$text,$key,false);
					$doc->{$label . '_s'} = $data->$key->text;
					$doc->{$label . '_t'} = $data->$key->text;
				}
				else if ($value['type'] == 'Link') {
					$doc->{$label . '_html_nxt'} =  $data->$key->text;
					$doc->{$label . '_s'} = $data->$key->text;
					$doc->{$label . '_t'} = $data->$key->text;
				} //Gestione Repeater
				else if ($value['type'] == 'Repeater') {
					$htmls = array();
					$valList = array();
					$html = '';
					foreach ($data->$key as $v) {
						$htmlElement = '';
						$htmlGroup = '<div class="group-value">';
						foreach ($v as $k => $val) {
							$fieldLabel = $this->fieldsLabel->$k;
							if ($val && !(is_object($val) || is_array($val))) {
								if(ctype_space(htmlentities($val))) {
									$htmlElement = '';
								}
								else {
									$getHtml = true;
									if($key !== 'descrittori')
									{
										//Array appoggio per escludere campi
										$arrayExclude = array("Codifica della data");
										//Array appoggio per label da skippare
										$arraySkipLabel = array("Estremo cronologico testuale");
										$arrayKeysToSkip = array('altraDenominazione');

										if(in_array($fieldLabel,$arrayExclude))
										{
											continue;
										}
										else if(!in_array($fieldLabel,$arraySkipLabel))
										{
											if($this->isEntity)
											{
												//La casistica della polodebug 491 è implementata a partire da qui
												//solo per entità
												$this->isECT = array_key_exists($key,$this->arrayECTCase);
												$this->isPrincipalECT = in_array($key,$this->arrayPrincipalECT);
											}
											if(!$this->isPrincipalECT && !in_array($key, $arrayKeysToSkip))
											{
												$htmlGroup .= '<div class="label">' . $this->mappingHelper->translateLabel($fieldLabel,$this->labels,$this->translationLabels) . '</div>';
											}
										}
									}

									if($this->isECT)
									{
										$ect = $this->arrayECTCase[$key]['ect'];
										$ectfield = $this->arrayECTCase[$key]['field'];
										$ectValues = $v->$ect;
										$ectFinal = ($ectValues[0]->$ectfield) ? ' ('.htmlentities($ectValues[0]->$ectfield).')' : '';
										$htmlGroup .= '<div class="value">' . $this->linkify(htmlentities($val))  . $ectFinal. '</div>';
									}
									else if($this->isPrincipalECT)
									{
										$htmlGroup = $this->ectPrincipalHTML($v);
									}
									else
									{
										$htmlGroup .= '<div class="value">' . $this->linkify(htmlentities($val)) . '</div>';
									}

									$valList[] = $val;
								}
							} else if (is_array($val) || is_object($val)){
								if(in_array($key,$indexVoices)) {
									$htmlElement .= $this->indexVoicesHtml($val, $fieldLabel, $valList);
									if($htmlElement)
									{
										$getHtml = true;
									}
								}
								else if($key === 'autoreResponsabile')
								{
									$fieldLabel = $this->fieldsLabel->$k;
									$htmlGroup .= '<div class="label">' . $this->mappingHelper->translateLabel($this->fieldsLabel->$k,$this->labels,$this->translationLabels) . '</div>';
									if(is_object($val)) {
										$textArray = explode("||",$val->text);
										$text = $textArray[1] . ' || '. $textArray[2];
										$htmlGroup .= $this->createLink($val->id, $text, $key);
									}
									else {
										$htmlGroup .= '<div class="value">' . $val . '</div>';
									}
									$valList[] = $val;
								}
								else {
									if(!$this->isECT)
									{
										$htmlElement .= $this->recursiveHtmlGeneration($val, $fieldLabel, $valList, $originalLabel);
										if($htmlElement) {
											$getHtml = true;
										}
									}
								}
							}
						}
						if($htmlElement || $getHtml = true)
						{
							$html .= $htmlGroup.$htmlElement.'</div>';
						}
						else {
							$html = '';
							$getHtml = false;
						}
						if ($getHtml) {
							if($html != '<div class="group-value"></div>')
							{
								$htmls[] = $html;
							}
							$html = '';
							$getHtml = false;
						}
					}
					$doc->{$label . '_html_nxtxt'} = $htmls;

					if ($valList) {
						$doc->{$label . '_ss'} = array();
						foreach ($valList as $val) {
							if(is_object($val)) {
								array_push($doc->{$label . '_ss'}, $val->text);
							}
							else {
								array_push($doc->{$label . '_ss'}, $val);
							}
						}
						$doc->{$label . '_txt'} = $doc->{$label . '_ss'};
					}
				} else {
					$doc->{$label . '_html_nxt'} = $data->$key;
					$doc->{$label . '_s'} = $data->$key;
					$doc->{$label . '_t'} = $data->$key;
				}
			}
		}

		return $doc;
	}

	public function ectPrincipalHTML($d)
	{
		$html = '<div class="group-value">';
		$html .= '<div class="value">'.$d->estremoCronologicoTestuale.'</div>';
		return $html;
	}

	public function indexArchive($data, $fields, $complex)
	{
		$indexVoices = array('antroponimi','enti','toponimi','descrittori');
		$doc = new stdClass();
		foreach ($fields as $k => $field) {
			$doc->$k = array();
			if(!is_array($doc->autoreruolo_html_nxtxt))
			{
				$doc->autoreruolo_html_nxtxt = array();
			}
			foreach ($field as $f) {
				if (strpos($f, '.') !== false) {
					$composition = explode(".", $f);
					$d = $data->{$composition[0]};
					if ($d) {
						foreach ($d as $repeat) {
							if ($repeat->{$composition[1]}) {
								if(is_object($repeat->{$composition[1]}))
								{
									if($repeat->{$composition[1]}->text)
									{
										//caso particolare autore
										if($composition[1] === 'autoreCognomeNome' || $composition[1] === 'autoreDenominazione') {
											$nc = explode("||",$repeat->{$composition[1]}->text);
											array_push($doc->$k, $nc[1]);

											//Per query su ruolo
											$ruolo = ($repeat->ruolo) ? '['.$repeat->ruolo.']' : '';
											array_push($doc->autoreruolo_html_nxtxt ,$repeat->{$composition[1]}->id . $ruolo);
										}
										else {
											array_push($doc->$k, $repeat->{$composition[1]}->text);
										}
									}
								}
								else
								{
									array_push($doc->$k, $repeat->{$composition[1]});
								}
							}
						}
					}
				}
				else if(in_array($f,$indexVoices)){
					if ($data->$f)
					{
						$valArray = array();
						foreach ($data->$f as $i) {
							$val = '';
							if($i->intestazione)
							{
								$val = $i->intestazione->text;
							}
							else if($i->voce)
							{
								$val = $i->voce->text;
							}
							if($val)
							{
								$valArray[] = $val;
							}
						}
						array_push($doc->$k, $valArray);
					}
				}
				else if (strpos($f, 'link:') === false) {
					if ($data->$f && !is_array($data->$f) && !is_object($data->$f)) {
						if(!ctype_space($data->$f))
						{
							array_push($doc->$k, $data->$f);
						}
					} else if ($data->$f) {
						$array = json_decode(json_encode($data->$f), true);
						array_walk_recursive($array, 'metafad_solr_helpers_ArchiveFEHelper::recursion');
					}
				}
				else {
					//Gestione collegamenti
					$tmp = explode("link:", $f);
					$label = $tmp[1];
					if ($data->$label) {
						if ($label == 'soggettoProduttore' || $label == 'soggettoConservatore') {
							$model = 'archivi.models.ProduttoreConservatore';
							$id = $data->$label->id;
							array_push($doc->autoreruolo_html_nxtxt ,$id);
							$ar = org_glizy_objectFactory::createModel($model);
							$ar->load($id);
							$doc->$k = array();
							$explodedLabel = explode("||",$data->$label->text);
							$name = (ctype_space($explodedLabel[1])) ? $explodedLabel[0] : $explodedLabel[1];
							array_push($doc->$k, $name);
							$otherDoc = $this->indexArchive($ar->getRawData(), $this->mappingFile->$model, $complex);
							//Unisco i dati estratti da produttori e conservatori
							foreach ($otherDoc as $key => $value) {
								if (!$doc->$key) {
									$doc->$key = array();
								}
								foreach ($value as $v) {
									array_push($doc->$key, $v);
								}
							}
						} else if ($label == 'parent') {
							array_push($doc->$k, $data->$label->text);
							$nodes = $this->getParent($data->$label->id);
							foreach ($nodes as $n) {
								foreach ($n as $key => $value) {
									if (!$doc->$key) {
										$doc->$key = array();
									}
									foreach ($value as $v) {
										array_push($doc->$key, $v);
									}
								}
							}
						} else {
							$model = 'archivi.models.SchedaStrumentoRicerca';
							$ids = array();
							$vals = array();
							foreach ($data->$label as $value) {
								$ids[] = $value->linkStrumentiRicerca->id;
								$vals[] = $value->linkStrumentiRicerca->text;
							}
							$doc->$k = array();
							foreach ($ids as $key => $id) {
								$ar = org_glizy_objectFactory::createModel($model);
								$ar->load($id);
								array_push($doc->$k, $vals[$key]);
								if ($complex) {
									$otherDoc = $this->indexArchive($ar->getRawData(), $this->mappingFile->$model, $complex);
									//Unisco i dati estratti dagli strumenti di ricerca
									foreach ($otherDoc as $key => $value) {
										if (!$doc->$key) {
											$doc->$key = array();
										}
										foreach ($value as $v) {
											array_push($doc->$key, $v);
										}
									}
								}
							}
						}
					}
				}
			}
			if (!empty($this->appoggio)) {
				foreach ($this->appoggio as $v) {
					array_push($doc->$k, $v);
				}
			}
			if (empty($doc->$k)) {
				unset($doc->$k);
			}
			$this->appoggio = array();
		}
		return $doc;
	}

	public function getParent($parentId)
	{
		$record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
		$record->load($parentId, 'PUBLISHED_DRAFT');
		$nodes = array();
		//Se è un complesso prendo i suoi dati
		if ($record->document_type == 'archivi.models.ComplessoArchivistico') {
			$nodes[] = $this->indexArchive($record->getRawData(), $this->mappingFile->{$record->document_type}, false);
		}

		//Se sono alla radice ho terminato
		if ($record->root == 'true') {
			return $nodes;
		} //Altrimenti recupero ricorsivamente il parent di questo nodo
		else {
			$recParentId = is_array($record->parent) ? $record->parent['id'] : $record->parent->id;
			$parent = $this->getParent($recParentId);
			$nodes[] = $parent[0];
			return $nodes;
		}
	}

	public function getFirstLevel($data)
	{
		$record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
		$record->load($data->parent->id, 'PUBLISHED_DRAFT');
		$archProxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
		if ($record->root == 'true') {
			$title = $record->_denominazione;
			$title = explode("||",$title);

			$nome = trim($title[1], " ");
			$data = trim($title[2], " ");
			$data = $data == "-" ? "" : $data;

			$finalTitle = ($data) ? $nome . '|' .$data : $nome;
			return array('id' => $record->document_id, 'text' => $finalTitle);
		} else {
			return $this->getFirstLevel($record);
		}
	}

	public function recursion(&$item, $key)
	{
		if ($item) {
			$this->appoggio[] = $item;
		}
	}

	private function getAuthorTitle($data,$date=true)
	{
		$titolo = '';
		if($data->tipologiaChoice === 'Persona')
		{
			if($data->persona_denominazione){
				$persona = $data->persona_denominazione;
				$a = $persona[0];
				$cognome = $a->entitaDenominazione;
				$nome = ($a->persona_nome) ? ', '.$a->persona_nome : '';
				if($data->cronologiaPersona)
				{
					$cronologia = ($data->cronologiaPersona[0]->estremoCronologicoTestuale && $date) ? ' | ' . $data->cronologiaPersona[0]->estremoCronologicoTestuale : '';
				}
				$titolo = $cognome . $nome . $cronologia;
			}
		}
		else if($data->tipologiaChoice === 'Ente' || $data->tipologiaChoice === 'Famiglia')
		{
			if($data->ente_famiglia_denominazione){
				$ef = $data->ente_famiglia_denominazione;
				$a = $ef[0];
				$denominazione = $a->entitaDenominazione;
				$titolo = $denominazione;
			}
		}

		return $titolo;
	}

	/**
     * Turn all URLs in clickable links.
     *
     * @param string $value
     * @param array  $protocols  http/https, ftp, mail, twitter
     * @param array  $attributes
     * @param string $mode       normal or all
     * @return string
     */
    public function linkify($value, $protocols = array('http', 'mail'), array $attributes = array())
    {
        // Link attributes
        $attr = '';
        foreach ($attributes as $key => $val) {
            $attr = ' ' . $key . '="' . htmlentities($val) . '"';
        }

        $links = array();

        // Extract existing links and tags
        $value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) { return '<' . array_push($links, $match[1]) . '>'; }, $value);

        // Extract text links for each protocol
        foreach ((array)$protocols as $protocol) {
            switch ($protocol) {
                case 'http':
                case 'https':   $value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { if ($match[1]) $protocol = $match[1]; $link = $match[2] ?: $match[3]; return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>") . '>'; }, $value); break;
                case 'mail':    $value = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
                case 'twitter': $value = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\">{$match[0]}</a>") . '>'; }, $value); break;
                default:        $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
            }
        }

        // Insert all link
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value);
    }

	private function createLink($id,$text,$type=null,$includeExternalDiv = true)
	{
		$query = urlencode('{"query":{"clause":{"type":"SimpleClause","operator":{"operator":"AND"},"field":"Tutto","innerOperator":{"operator":"contains one"},"values":["*"]},"start":0,"rows":10,"facetLimit":100,"facetMinimum":1,"filters":[{"type":"SimpleClause","operator":{"operator":"AND"},"field":"autoreruolo_html_nxtxt","innerOperator":{"operator":"AND"},"values":["'.$id.'"]}],"facets":null,"orderClauses":null,"fq":null,"fieldNamesAreNative":false}}');

		$filter = ($type) ? '?filterFields='.$type : '';
		$links ='  <a target="_blank" href="{filterEntity}'.$query.'">'.$text.'</a>'.
				'  <a target="_blank" href="{linkToEntityDetail}'.$id.'">${button}</a>'.
				'  <a class="js-openhere" data-modal="{linkToEntity}'.$id.$filter.'">(i)</a>';
		if($includeExternalDiv)
		{
			return '<div class="value">'.$links.'</div>';
		}
		else {
			return $links;
		}
	}
}
