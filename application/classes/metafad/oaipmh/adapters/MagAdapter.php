<?php
class metafad_oaipmh_adapters_MagAdapter implements org_glizy_oaipmh2_core_AdapterInterface
{
    private $oaiPrefix;

    public function __construct()
    {
        $this->oaiPrefix = __Config::get('oaipmh.oaiPrefix');
    }

    /**
     * @param string $sets
     * @param string $from
     * @param string $until
     * @param integer $limitStart
     * @param integer $limitLength
     * @return org_glizy_oaipmh2_models_VO_ListVO
     */
    public function findAll($sets, $from, $until, $limitStart, $limitLength)
    {
        $filters = [];
        if ($from || $until) {
            $filters[] = $this->filterFromUntil($from, $until);
        }
        $filters[] = 'type_nxs:"OAI-MAG"';

        $postBody = array(
            'q' => implode(' AND ', $filters),
            'start' => $limitStart,
            'rows' => $limitLength,
            'wt' => 'json'
        );

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', __Config::get('metafad.solr.url').'select?', 'POST', http_build_query($postBody));
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();
        $result = json_decode($request->getResponseBody());
        if ($result->response->numFound === 0) {
            throw org_glizy_oaipmh2_core_Exception::noRecordsMatch();
        }

        return org_glizy_oaipmh2_models_VO_ListVO::create(new metafad_oaipmh_adapters_MagRecordIterator($result->response->docs), $result->response->numFound);
    }

    /**
     * @param array $setInfo
     * @param string|integer $id
     * @param string $identifier
     * @return void
     */
    public function findById($setInfo, org_glizy_oaipmh2_models_VO_IdentifierVO $identifierVO)
    {
        $postBody = array(
            'q' => 'id:"'.$identifierVO->id.'" AND type_nxs:"OAI-MAG"',
            'wt' => 'json'
        );

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', __Config::get('metafad.solr.url').'select?', 'POST', http_build_query($postBody));
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();
        $result = json_decode($request->getResponseBody());
        if ($result->response->numFound === 0) {
            throw org_glizy_oaipmh2_core_Exception::idDoesNotExist($identifierVO->identifier);
        }

        $doc = $result->response->docs[0];
        return org_glizy_oaipmh2_models_VO_RecordVO::create($doc->id, $doc->update_at_s, 'metafad.teca.MAG.models.Model', $doc);
    }

    /**
     * @param org_glizy_oaipmh2_core_SetInterface $set
     * @param string $id
     * @return string
     */
    public function createIdentifier($set, $id)
    {
        $setInfo = $set->getSetInfo();
        return sprintf('%s%s:%s', $this->oaiPrefix, $setInfo['setSpec'], $id);
    }

    /**
     * @param string $identifier
     * @return org_glizy_oaipmh2_models_VO_IdentifierVO
     */
    public function parseIdentifier($identifier)
    {
        list($setSpec, $id) = explode(':', str_replace($this->oaiPrefix, '', $identifier));
        return org_glizy_oaipmh2_models_VO_IdentifierVO::create($setSpec, $id, $identifier);
    }

    /**
     * @param string $from
     * @param string $until
     * @return string
     */
    private function filterFromUntil($from, $until)
    {
        $from = $from ? $from : '*';
        $until = $until ? $until : '*';
        return 'update_at_s:['.$from.' TO '.$until.']';
    }


}
