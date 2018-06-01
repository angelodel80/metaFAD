<?php
class metafad_gestioneDati_boards_models_proxy_ArchiveToIccdProxy extends GlizyObject
{
	public function findTerm($fieldName, $model, $query, $term, $proxyParams)
	{
		$result = array();

		if($proxyParams)
		{
			foreach ($proxyParams as $key => $value) {
				$it = org_glizy_objectFactory::createModelIterator($value)
						->where('instituteKey',metafad_usersAndPermissions_Common::getInstituteKey());

				foreach ($it as $ar) {
					if(!$ar->parent && $ar->_denominazione)
					{
						$result[] = array(
							'id' => $ar->getId(),
							'text' => $ar->_denominazione,
							'model' => $value
						);
					}
				}
			}
		}

		return $result;
	}
}
