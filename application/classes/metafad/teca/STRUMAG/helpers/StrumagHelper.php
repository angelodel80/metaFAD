<?php
class metafad_teca_STRUMAG_helpers_StrumagHelper extends GlizyObject
{
	public function index($ar)
	{
		$decodeData = (object)$ar->getValuesAsArray();

		$cl = new stdClass();

		$cl->className = $ar->getClassName(false);
		$cl->isVisible = $ar->isVisible();
		$cl->isTranslated = $ar->isTranslated();
		$cl->hasPublishedVersion = $ar->hasPublishedVersion();
		$cl->hasDraftVersion = $ar->hasDraftVersion();
		$cl->document_detail_status = $ar->getStatus();

		$decodeData->physicalSTRU = $ar->physicalSTRU;
		$decodeData->logicalSTRU = $ar->logicalSTRU;

		$decodeData->__id = $ar->getId();
		$decodeData->__model = 'metafad.teca.STRUMAG.models.Model';
		$decodeData->instituteKey = $ar->instituteKey;

		$decodeData->document = json_encode($cl);

		$decodeData->__commit = true;
		$evt = array('type' => 'insertRecord', 'data' => array('data' => $decodeData, 'option' => array('commit' => true)));
		$this->dispatchEvent($evt);
	}
}
