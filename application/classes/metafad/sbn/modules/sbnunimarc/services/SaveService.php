<?php
class metafad_sbn_modules_sbnunimarc_services_SaveService extends GlizyObject
{
	public function save($data, $script = false)
	{
		$decodeData = json_decode($data);

		//$this->setDataForImages($decodeData);

		$instituteKey = (!$script) ? metafad_usersAndPermissions_Common::getInstituteKey() : null;

		$it = org_glizy_objectFactory::createModelIterator($decodeData->__model)
		->where('id',$decodeData->__id)->first();
		//Per ogni media che salvo specifico con che istituto sto lavorando, per il
		//recupero delle informazioni in seguito, dato che ogni record SBN
		//può appartenere a più istituti e avere in teoria quindi immagini miste
		$it->linkedMedia = $this->addInstituteToMediaLinked($decodeData->linkedMedia, $instituteKey);

		$it->linkedInventoryMedia = $this->addInstituteToMediaLinked($decodeData->linkedInventoryMedia, $instituteKey);

		if($decodeData->linkedStruMag)
		{
			if(!$decodeData->linkedStruMag->instituteKey)
			{
				$decodeData->linkedStruMag->instituteKey = $instituteKey;
			}
		}
		$it->linkedStruMag = $decodeData->linkedStruMag;

		$it->visibility = $decodeData->visibility;

		$it->linkedInventoryStrumag = $this->addInstituteToMediaLinked($decodeData->linkedInventoryStrumag, $instituteKey);

		$it->ecommerceLicenses = $decodeData->ecommerceLicenses;

		$it->save(null,false,'PUBLISHED');

		$fi = org_glizy_objectFactory::createObject('metafad.viewer.helpers.FirstImage');
		$firstImage = $fi->execute($decodeData->__id,'sbn');

		$updateSbn = org_glizy_ObjectFactory::createObject('metafad_sbn_modules_sbnunimarc_model_proxy_UpdateSbnProxy');
		$digitale = $updateSbn->updateSbnDigitale($decodeData,$firstImage['firstImage']);

		if (!$firstImage) {
			$kardexService = __ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.services.KardexService');
			$kardexService->updateFE($decodeData->__id);
		}

		$updateSbn->updateSbnEcommerce($decodeData);
		if($decodeData->visibility)
		{
			$updateSbn->updateSbnVisibility($decodeData);
		}
		$updateDateTime = new DateTime();
		$d = $updateDateTime->format('Y-m-d H:i:s');
		$json = '[{"id":"'.$decodeData->__id.'","update_at_s":{"set":"'.$d.'"},"digitale_s":{"set":"'.$digitale.'"}}]';
		$request = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest',
			__Config::get('metafad.solr.url').'update?commit=true',
			'POST',
			is_string($json) ? $json : json_encode($json),
			'application/json');
		$request->execute();

		return $decodeData->__id;
	}


	public function addInstituteToMediaLinked($media,$instituteKey)
	{
		$appoggioMedia = array();
		if($media)
		{
			foreach ($media as $m) {
				if(!$m->instituteKey)
				{
					$m->instituteKey = $instituteKey;
				}
				$appoggioMedia[] = $m;
			}
		}
		return $appoggioMedia;
	}

	public function setDataForImages($data)
	{
		$updateDateTime = new DateTime();
		$d = $updateDateTime->format('Y-m-d H:i:s');

		$json = '[{"id":"'.$data->__id.'","update_at_s":{"set":"'.$d.'"},"images_info_only_store":{"set":'.json_encode(json_encode($data)).'}}]';

		$request = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest',
			__Config::get('metafad.solr.url').'update?commit=true',
			'POST',
			is_string($json) ? $json : json_encode($json),
			'application/json');
		$request->execute();
	}
}
