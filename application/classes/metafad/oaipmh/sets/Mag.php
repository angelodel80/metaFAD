<?php
class metafad_oaipmh_sets_Mag implements org_glizy_oaipmh2_core_SetInterface
{
    /**
     * @return array
     */
    public function getSetInfo()
    {
        $info = array();
        $info[ 'setSpec' ] = 'MAG';
        $info[ 'setName' ] = 'MAG';
        $info[ 'setDescription' ] = 'MAG';
        $info[ 'setCreator' ] = 'Meta srl';
        $info[ 'model' ] = 'metafad.teca.MAG.models.Model';
        return $info;
    }


    /**
     * @return string
     */
    function getModelName()
	{
		$info = $this->getSetInfo();
		return $info['model'];
	}

    /**
     * @param org_glizy_oaipmh2_models_VO_RecordVO $recordVO
     * @return string
     */
    public function getRecord(org_glizy_oaipmh2_models_VO_RecordVO $recordVO)
    {
        return str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $recordVO->document->xml_only_store);
    }
}
