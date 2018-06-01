<?php

class metafad_opac_models_proxy_OpacFieldProxy extends GlizyObject
{
	public function findTerm($fieldName, $model, $query, $term, $proxyParams)
	{
		$section = $proxyParams->section;
		$form = $proxyParams->form;
		$archive = $proxyParams->archive;

		//Lista dei servizi per la get dei campi
		$arrayUrl = array(
			'bibliografico'=>__Config::get('metafad.opac.fields.url'),
			'archivi'=>__Config::get('metafad.opac.archive.fields.url'),
			'patrimonio'=>__Config::get('metafad.opac.iccd.fields.url'),
			'metaindice'=>__Config::get('metafad.opac.metaindice.fields.url'),
			'metaindiceau'=>__Config::get('metafad.opac.metaindice-au.fields.url')
		);

		$fieldsUrl = $arrayUrl[$section];
		if($form)
		{
			$fieldsUrl = str_replace('iccd','iccd-'.strtolower($form),$fieldsUrl);
		}
		if($section == 'archivi')
		{
			$fieldsUrl = str_replace("-ca","-".$archive,$fieldsUrl);
		}
		$fields = json_decode(file_get_contents($fieldsUrl));

		foreach ($fields->fields as $value) {
			if($term != '')
			{
				if(stripos($value->label,$term) !== false)
				{
					$result[] = array(
						'id' => $value->id,
						'text' => $value->id,
					);
				}
			}
			else {
				$result[] = array(
					'id' => $value->id,
					'text' => $value->id,
				);
			}
		};

		if($result == null)
		{
			return '';
		}
		else {
			return $result;
		}
	}
}
