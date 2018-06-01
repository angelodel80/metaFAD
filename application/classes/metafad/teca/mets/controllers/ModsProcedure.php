<?php
class metafad_teca_mets_controllers_ModsProcedure extends metafad_common_controllers_Command
{
	public function execute()
	{
		$this->checkPermissionForBackend('edit');

		if(__Config::get('metafad.be.hasMets') === 'demo')
		{
			$this->logAndMessage(__T('Spiacente, questa funzionalità non è disponibile nella modalità DEMO.'), '', GLZ_LOG_MESSAGE);
			$this->changeAction('import');
		}

		if(!__Request::get('ids'))
		{
			$this->logAndMessage('ATTENZIONE: Seleziona almeno un MODS da cui generare METS.', '', true);
			$this->changeAction('import');
		}

		$ids = __Request::get('ids');
		
		$jobFactory = org_glizy_ObjectFactory::createObject('metacms.jobmanager.JobFactory');
		$jobFactory->createJob(
			'metafad.teca.mets.jobs.MetsFromModsJob',
			array(
				'ids' => $ids,
				'instituteKey' => metafad_usersAndPermissions_Common::getInstituteKey()
			),
			'Generazione METS da MODS lanciata in data ' . new org_glizy_types_DateTime(),
			'BACKGROUND'
		);

		$this->logAndMessage('Job di generazione METS creato. Verrà completato appena possibile. Verificare nell\'<a class="link-export" href="' . org_glizy_helpers_Link::makeUrl('link', array('pageId' => 'metafad.modules.importerreport')) . '">apposita sezione</a> lo stato di completamento.', '', false);
		$this->changeAction('import');
	}
}
