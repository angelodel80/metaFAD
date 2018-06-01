<?php
class metafad_oaipmh_core_AbstractMapping extends GlizyObject
{
    protected $ar;

    public function getSetInfo()
    {
        $info = array();
        $info[ 'setSpec' ] = '';
        $info[ 'setName' ] = '';
        $info[ 'setDescription' ] = '';
        $info[ 'setCreator' ] = '';
        $info[ 'model' ] = '';
        return $info;
    }

    function getModelName()
    {
        $info = $this->getSetInfo();
        return $info['model'];
    }

    function loadRecord($id)
    {
        $this->ar = org_glizy_ObjectFactory::createModel( $this->getModelName() );
        return $this->ar->load($id);
    }

    function getRecord($identifier)
    {
        $output .= $this->getMetadata($identifier);
        return $output;
    }
}