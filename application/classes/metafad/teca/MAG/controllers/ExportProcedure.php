<?php
class metafad_teca_MAG_controllers_ExportProcedure extends metafad_common_controllers_Command
{
	public function execute()
	{
		$this->checkPermissionForBackend('publish');

		if(__Config::get('metafad.be.hasMag') === 'demo')
		{
			$this->logAndMessage(__T('Spiacente, questa funzionalità non è disponibile nella modalità DEMO.'), '', GLZ_LOG_MESSAGE);
			$this->changeAction('export');
		}

		$exportAll = (__Request::get('exportAll')) ?: false;
		$exportSelected = (__Request::get('exportSelected')) ?: false;
		$ids = (__Request::get('ids')) ?: '';
		$exportTitle = (__Request::get('exportTitle')) ?:'export';
		$exportMode = (__Request::get('exportMode'));
		$exportFormat = (__Request::get('exportFormat'));
		$exportEmail = (__Request::get('exportEmail')) ? : false;

		if($exportAll)
		{
			$magList = array();
			$lastSearch = __Session::get('lastSearch');
			$query = $lastSearch['search'];
			$numFound = $lastSearch['numFound'];
			$query = str_replace('&rows=10','&rows='.$numFound,$query);
			$request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest',$query);
			$request->setTimeout(1000);
			$request->setAcceptType('application/json');
			$request->execute();

			$docs = json_decode($request->getResponseBody())->response->docs;
			foreach ($docs as $d) {
				$magList[] = $d->id;
			}
		}
		else if($exportSelected)
		{
			$magList = explode(",",$ids);
		}
		else {
			$this->logAndMessage('ATTENZIONE: Selezionare con apposito checkbox i record della sintetica che si desidera esportare', '', true);
			$this->changeAction('export');
		}

		if(sizeof($magList) == 0)
		{
			$this->logAndMessage('ATTENZIONE: Non è stato selezionato nessun record da esportare!', '', true);
			$this->changeAction('export');
		}
		else
		{
			if(__Config::get('metafad.be.hasExport') === true)
			{
				$exportHelper = org_glizy_ObjectFactory::createObject('metafad.teca.MAG.helpers.ExportHelper',$this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy'));
				$exportHelper->createExportPack($magList,$exportTitle,$exportMode,$exportEmail,$exportFormat);
				$this->logAndMessage('Job di esportazione creato. Verrà completato appena possibile. Verificare nell\'<a class="link-export" href="'.org_glizy_helpers_Link::makeUrl( 'link', array( 'pageId' => 'metafad.modules.importerreport' ) ).'">apposita sezione</a> lo stato di completamento.', '', false);
			}
			else
			{
				$this->logAndMessage('ATTENZIONE: Servizio momentaneamente non attivo.', '', true);
				$this->changeAction('export');
			}
		}

		$this->changeAction('export');
	}
}
