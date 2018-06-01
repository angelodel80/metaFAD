<?php
class metafad_teca_MAG_controllers_MetsProcedure extends metafad_common_controllers_Command
{
	public function execute()
	{
		$this->checkPermissionForBackend('edit');

		if (__Config::get('metafad.be.hasMets') === 'demo') {
			$this->logAndMessage(__T('Spiacente, questa funzionalità non è disponibile nella modalità DEMO.'), '', GLZ_LOG_MESSAGE);
			$this->changeAction('import');
		}

		$instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();

		$GEN_stprog = __Request::exists('GEN_stprog_mets') ? __Request::get('GEN_stprog_mets') : null;
		$GEN_collection = __Request::exists('GEN_collection_mets') ? __Request::get('GEN_collection_mets') : null;
		$GEN_agency = __Request::exists('GEN_agency_mets') ? __Request::get('GEN_agency_mets') : null;
		$GEN_access_rights = __Request::exists('GEN_access_rights_mets') ? __Request::get('GEN_access_rights_mets') : null;
		$GEN_completeness = __Request::exists('GEN_completeness_mets') ? __Request::get('GEN_completeness_mets') : null;
		$formType = __Request::exists('formType_mets') ? __Request::get('formType_mets') : null;
		$importMode = __Request::exists('importMode_mets') ? __Request::get('importMode_mets') : null;

		$file = $_FILES['fileToImport_mets'];
		$file_path = $file['tmp_name'];
		$file_name = $file['name'];

		$uploaddir = __Config::get('metafad.modules.importer.uploadFolder');
		$uploadfile = $uploaddir . '/' . basename($file['name']);
		$zipFolder = $uploaddir . '/' . basename($file['name'], ".zip");
		move_uploaded_file($file['tmp_name'], $uploadfile);

		$fileList = array();
		//Estraggo file da archivio
		$zip = new ZipArchive;
		if ($zip->open($uploadfile) === true) {
			$zip->extractTo($zipFolder);
			$zip->close();
		}

		$parameters = "" . ($GEN_stprog != null ? "stprog=$GEN_stprog&" : "") . ($GEN_collection != null ? "collection=$GEN_collection&" : "") . ($GEN_agency != null ? "agency=$GEN_agency&" : "") . ($GEN_access_rights != null ? "access_rights=$GEN_access_rights&" : "") . ($GEN_completeness != null ? "completeness=$GEN_completeness&" : "");

		$files = $this->rsearch($zipFolder, '/^.*\.(xml|XML)/');
		$listMag = [];
		foreach ($files as $value) {
			if ($value != "xml" && $value != "XML") {
				$file = pathinfo($value, PATHINFO_FILENAME);
				$patf = pathinfo($value, PATHINFO_DIRNAME);
				$filef = pathinfo($value, PATHINFO_BASENAME);

				$command = "curl '" . __Config::get('metafad.mets.import.url') . "?" . $parameters . "' -X POST -H 'Content-Type: multipart/form-data' -H 'Accept: application/xml' -F mets=@" . $patf . '/' . $filef;
				$o = org_glizy_helpers_Exec::exec($command);
				if (!strpos($o['stdout'], '<status>500</status>') && !strpos($o['stdout'], '<error>Internal Server Error</error>')) {
					file_put_contents($patf . '/' . $file . '_mag.xml', $o['stdout']);
					$listMag[] = $patf . '/' . $file . '_mag.xml';
				}
			}
		}

		if (!count($listMag)) {
			$this->logAndMessage('Non ci sono file .xml compatibili nel pacchetto caricato', '', GLZ_LOG_ERROR);
		} else {

			$debug = '';

			try {
				// ora che ho la lista dei file (o il singolo file) procedo alla lettura e alla importazione
				$importService = __ObjectFactory::createObject(
					'metafad.teca.MAG.services.ImportMag',
					$this->application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy'),
					__ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia' . $debug, $instituteKey),
					$this->application->retrieveService('metafad.teca.MAG.services.Event' . $debug)
				);

				$importService->setImportOption($formType, $importMode);
				$overwrite = ($importMode == 'substitute') ? true : false;
				$importedFiles = $importService->importFolder($listMag,array('M', 'S'), false, false, $overwrite);

				$this->logAndMessage(sprintf('Importazione completata, %d METS importati', $importedFiles));
			} catch (Exception $e) {
				$this->logAndMessage($e->getMessage(), '', true);
			}
		}

		$this->rrmdir($zipFolder);
		$this->changeAction('import');
	}

	private function rsearch($folder, $pattern)
	{
		try {
			$dir = new RecursiveDirectoryIterator($folder);
			$ite = new RecursiveIteratorIterator($dir);
			$files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
			$fileList = array();
			foreach ($files as $file) {
				$fileList = array_merge($fileList, $file);
			}
			return $fileList;
		} catch (Exception $e) {
			return array();
		}
	}

	private function rrmdir($path)
	{
		return is_file($path) ?
			@unlink($path) :
			array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path);
	}
}
