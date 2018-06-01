<?php
class metafad_teca_DAM_services_DamService extends GlizyObject
{
    protected $damUrl;
    protected $damUrlLocal;

    public function __construct($damInstance = null)
    {
        $this->damUrl = metafad_teca_DAM_Common::getDamUrl($damInstance);
        $this->damUrlLocal = metafad_teca_DAM_Common::getDamUrlLocal($damInstance);
    }

    public function getAllStreamTypes()
    {
        $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $this->damUrlLocal.'/bytestream/getAllTypes');
        $result = $r->execute();
        $response = json_decode($r->getResponseBody());
        return $response;
    }
}
