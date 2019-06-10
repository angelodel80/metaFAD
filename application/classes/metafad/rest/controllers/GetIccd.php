<?php
class metafad_rest_controllers_GetIccd extends org_glizy_rest_core_CommandRest
{
	/*
		$model = stringa che indica il tipo di scheda
		$code = identificativo univoco ICCD
		$institute = istituto di appartenenza
	 */
	function execute($model, $code, $institute)
	{
		header('Content-Type: application/json');
		if(!__Config::get('metafad.getIccd.activate'))
		{
			return 'Servizio non disponibile.';
		}
		$dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $institute);

		$availableModels = json_decode(__Config::get('metafad.getIccd.availableModels'));

		if (!$model || !$code || !$institute) {
			return 'Errore, uno dei parametri obbligatori non è stato inviato correttamente.';
		}

		//verifico che il valore di $model sia valido e che la scheda sia di una tipologia disponibile
		if (!in_array($model, $availableModels)) {
			return 'Tipologia di scheda scelta non disponibile';
		}

		$form = org_glizy_ObjectFactory::createModelIterator($model . '.models.Model')
			->where('instituteKey', $institute)
			->where('uniqueIccdId', $code)
			->first();

		if (!$form) {
			return 'Scheda non disponibile, verificare i dati inseriti';
		}

		$fields = $form->getValuesAsArray(false, true, false, false);
		if ($fields['FTA']) {
			foreach ($fields['FTA'] as $f) {
				if ($f->{'FTA-image'}) {
					$d = json_decode($f->{'FTA-image'});
					$thumbnail = $dam->streamUrl($d->id,'thumbnail');
					$original = $dam->streamUrl($d->id, 'original');
					$d->thumbnail = $thumbnail;
					$d->original = $original;
					$f->{'FTA-image'} = $d;
				}
			}
		}


		//Se non c'è il parametro fieldsList restituisco tutto
		if (!__Request::get('fieldsList')) {
			$fields['data_ultima_modifica'] = $form->document_detail_modificationDate;
			echo json_encode($fields);
		}
		//Altrimenti filtro i valori
		else {
			$values = new stdClass();
			foreach (explode(',', str_replace(' ', '', __Request::get('fieldsList'))) as $field) {
				$values->$field = $fields[$field];
			}
			$values->data_ultima_modifica = $form->document_detail_modificationDate;

			echo json_encode($values);
		}
		exit;
	}
}
