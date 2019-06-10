<?php
class metafad_print_views_components_Preview extends org_glizy_components_Component
{
	function init()
	{
        // define the custom attributes
		$this->defineAttribute('model', true, '', COMPONENT_TYPE_STRING);
	
		parent::init();
	}

	function process()
	{
		$model = $this->getAttribute('model');

		$record = org_glizy_objectFactory::createModelIterator($model)
			->setOptions(array('type' => 'PUBLISHED_DRAFT'))
			->where('document_id',__Request::get('id'))
			->first();
		
		if($record)
		{
			$moduleService = __ObjectFactory::createObject('metafad.modules.iccd.services.ModuleService');
			$elements = $moduleService->getElements(str_replace('_preview','', str_replace('.models.Model','',$model)));

			$record = $record->getRawData();
			$record->__model = $model;
			$record->__id = $record->document_id;
			$helper = __ObjectFactory::createObject('metafad_solr_helpers_PreviewHelper');
			$detail = $helper->detailMapping($record, $elements);

			$output = '';
			// dd($detail);
			foreach($detail as $k => $v)
			{
				if(strpos($k,'html_') !== false)
				{
					$key = str_replace('_html_nxtxt','',$k);
					$key = str_replace('_html_nxt', '', $key);
					$key = __T(strtoupper(end(explode('_',$key))));
					
					$output .= '<div><span class="label-preview">'.$key .'</span><br/>'. str_replace('label','label-preview-internal',$v) .'</div><br/>';
				}
			}
		}

		$this->addOutputCode($output);

	}
}