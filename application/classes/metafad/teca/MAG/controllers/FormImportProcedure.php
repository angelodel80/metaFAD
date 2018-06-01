<?php
class metafad_teca_MAG_controllers_FormImportProcedure extends metafad_common_controllers_Command
{
	public function execute()
	{
		$result = $this->checkPermissionForBackend('edit');

		if(__Config::get('metafad.be.hasMag') === 'demo')
		{
			$this->logAndMessage(__T('Spiacente, questa funzionalità non è disponibile nella modalità DEMO.'), '', GLZ_LOG_MESSAGE);
			$this->changeAction('import');
		}

		if (is_array($result)) {
			return $result;
		}

		$instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();

		$data = __Request::getAllAsArray();
		$model = $data['form_formType'];
		$start = $data['dateStart'] . ' 00:00:00';
		$end = $data['dateEnd'] . ' 23:59:59';
		$importMode = $data['form_importMode'];
		
		//Seleziono le schede modificate entro l'intervallo scelto
		$it = org_glizy_objectFactory::createModelIterator($model)->
		where('document_detail_modificationDate',$start,'>=')->
		where('document_detail_modificationDate',$end, '<=');
		if($model != 'metafad.sbn.modules.sbnunimarc.model.Model')
		{
			$it->where('instituteKey',$instituteKey);
		}

		if($it->count() == 0){
			$this->logAndMessage('Attenzione, non ci sono schede importabili per il tipo selezionato', '', true);
		}
		else {
			$jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
			$jobFactory->createJob('metafad.teca.MAG.helpers.FormImportBatch',
			array(
				'model' => $data['form_formType'],
				'start' => $data['dateStart'],
				'end' => $data['dateEnd'],
				'data' => __Request::getAllAsArray(),
				'importMode' => $importMode,
				'instituteKey' => $instituteKey
			),
			'Creazione MAG da schede. Schede modificate nell\'intervallo: ' . $it->count() . ' (model: '.$model.')',
			'BACKGROUND');
			$this->logAndMessage('Il job di creazione dei MAG sarà lanciato appena possibile (verificare nell\'apposita sezione import/export)' , '', false);
		}
		$this->changeAction('import');
	}

}
