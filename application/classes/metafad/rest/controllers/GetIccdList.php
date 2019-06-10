<?php
class metafad_rest_controllers_GetIccdList extends org_glizy_rest_core_CommandRest
{
	/*
		$model = stringa che indica il tipo di scheda
		$institute = istituto di appartenenza
	 */
	function execute($model, $institute)
	{
		set_time_limit(0);
		header('Content-Type: application/json');
		if(!__Config::get('metafad.getIccd.activate')) 
		{
			return 'Servizio non disponibile.';
		}

		$availableModels = json_decode(__Config::get('metafad.getIccd.availableModels'));

		if (!$model || !$institute) {
			return 'Errore, uno dei parametri obbligatori non è stato inviato correttamente.';
		}

		//verifico che il valore di $model sia valido e che la scheda sia di una tipologia disponibile
		if (!in_array($model, $availableModels)) {
			return 'Tipologia di scheda scelta non disponibile';
		}

		$formList = array();
		$uniqueIccdIdProxy = org_glizy_ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy');
		$date = (__Request::get('date')) ? date('Y-m-d h:m:s', strtotime(__Request::get('date'))) : null;
		$lir = (__Request::get('LIR')) ? : null;
		$start = (__Request::get('start')) ? : 0;
		$stop = (__Request::get('stop')) ? : null;


		$it = org_glizy_ObjectFactory::createModelIterator($model . '.models.Model')
			->where('instituteKey', $institute);

		if ($date) {
			$it = $it->where('document_detail_modificationDate', $date, '>=');
		}

		if ($lir) {
			$it = $it->where('LIR', $lir);
		}

		if ($stop) {
			$it = $it->limit($start, $stop - $start);
		}

		foreach ($it as $ar) {
			$id = $uniqueIccdIdProxy->createUniqueIccdId($ar);
			if ($id) {
				$formList[] = $id;
			}
		}

		echo json_encode($formList);
		exit;
	}
}
