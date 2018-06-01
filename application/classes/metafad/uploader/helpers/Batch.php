<?php
class metafad_uploader_helpers_Batch extends metacms_jobmanager_service_JobService
{
	public function run()
	{
		set_error_handler(array($this, 'errorHandler'));

		try{
			$param = $this->params;
			$format = $param['format'];
			$mrcFolder = $param['mrcFolder'];

			$this->updateStatus(metacms_jobmanager_JobStatus::RUNNING);

			if ($format === 'sbn' || $format === 'sbnaut') {
				$createJsonService = __Config::get('metafad.sbn.import');
				$serviceStatus = __Config::get('metafad.sbn.import.status');
				$dirContent = scandir($mrcFolder);
				//Verifico se c'è un file mrc e lo prendo per mandarlo al servizio
				foreach ($dirContent as $file) {
					if(pathinfo($file, PATHINFO_EXTENSION) == 'MRC' || pathinfo($file, PATHINFO_EXTENSION) == 'mrc' || strpos($file,'.mrc') !== false || strpos($file,'.MRC') !== false  )
					{
						$fileToImport = $file;
						break;
					}
				}
				//Se non ci sono mrc allora non ho nulla da importare, posso anche eliminare la cartella per fare spazio
				if(!$fileToImport) {
					$this->rrmdir($mrcFolder);
					$this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
					$this->setMessage('Il file caricato non contiene file in formato .mrc, caricare uno zip contenente un file .mrc. Lista dei file contenuti: '.implode(", ",$dirContent));
					$this->save();
					$statusError = true;
				}
				//altrimenti chiamo il servizio di generazione JSON
				else {
					$input = __Config::get('metafad.'.$format.'.uploadFolder.solr') . '/' . basename($mrcFolder) . '/' . $fileToImport;
					$output = __Config::get('metafad.'.$format.'.outputFolder.solr') . '/' . basename($mrcFolder);
					$profile = ($format == 'sbnaut') ? '&profile=au' : '';

					$createJsonService = str_replace(array('##filename##','##directory##'),array($input,$output),$createJsonService).$profile;
					$message = $this->wget($createJsonService);

					$response = $this->checkStatus($message,$serviceStatus);

					if($response->status == 'error')
					{
						$this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
						$this->setMessage('Il seguente errore si è verificato in fase di import: ' .$response->message);
						$this->save();
						$statusError = true;
					}
				}
			}
			else {
				$this->setMessage('Il pacchetto non presenta file del formato selezionato ('.$format.')');
				$this->save();
			}

			if(!$statusError)
			{
				$this->finish();
				$this->setMessage('Upload pacchetto eseguito.');
				$this->save();
			}
		}
		catch(Error $e){
			$this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
			$this->save();
		}

	}

	public function errorHandler(){
		$error = error_get_last();
		if ($error['type'] === E_ERROR) {
			$this->updateStatus(metacms_jobmanager_JobStatus::ERROR);
			$this->save();
			die;
		}
	}

	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir."/".$object))
					$this->rrmdir($dir."/".$object);
					else
					unlink($dir."/".$object);
				}
			}
			rmdir($dir);
		}
	}

	private function checkStatus($message,$serviceStatus) {
		$response = new stdClass();
		$response->status = 'none';

		while($response->status != 'finished')
		{
			$response = json_decode(file_get_contents($serviceStatus));

			if($response->convertorStatus)
			{
				$count = $response->convertorStatus->count;
				$this->setMessage('Creazione pacchetto dati in corso, importati correttamente '.$count.' record.');
				$this->save();
			}
			if($response->status == 'error' || $response->convertorStatus->status == 'idle')
			{
				return $response;
			}
			sleep(30);
			$count++;
		}
		return $response;
	}

	private function wget($url)
	{
		$command = "wget '".$url."'";
		$o = org_glizy_helpers_Exec::exec($command);
		return $o;
	}
}
