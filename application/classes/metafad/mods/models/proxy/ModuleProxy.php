<?php
set_time_limit(0);

class metafad_mods_models_proxy_ModuleProxy extends metafad_common_models_proxy_SolrQueueProxy
{
    /**
     * @var org_glizycms_contents_models_proxy_ModuleContentProxy
     */
    protected $proxy = null;
    private $stack = array();

    function __construct($profileSave = false)
    {
        $this->proxy = __ObjectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
    }

    public function validate($data)
    {
        return $data || !$data;
    }
    
    public function load($id, $model)
    {
        return $this->proxy->loadContent($id, $model);
    }

    /**
     * Esegue il salvataggio (prima presente in archivi.controllers.ajax.Save)
     * @param $data StdClass
     * @return mixed Restituisce $result
     */
    public function save($data)
    {
        $isDraft = false;
        $data->isValid = 0;

        return $this->saveProcedure($data, $isDraft);
    }

    /**
     * @param $data
     * @param $invertRelation
     * @param $isDraft
     * @return array
     */
    protected function saveProcedure($data, $isDraft)
    {
        $res = $this->saveObject($data, $isDraft === true);

        if (isset($res['set'])) {
            $data->__id = $res['set']['__id'];
            $this->appendDocumentToData($data);
            $this->sendDataToSolr($data);

            $ar = __ObjectFactory::createModel('metafad.mods.models.Model');
            $ar->load($data->__id);
        }

        return $res;
    }

    /**
     * @param $data
     * @param $isDraft
     * @return array
     */
    protected function saveObject($data, $isDraft = false)
    {
        $result = $this->proxy->saveContent($data, __Config::get('glizycms.content.history'), $isDraft === true);

        if ($result['__id']) {
            return array('set' => $result);
        } else {
            return array('errors' => $result);
        }
    }

    /**
     * Esegue quel che avrebbe eseguito la metafad_mods_controllers_ajax_SaveDraft::execute()
     * @param $data stdClass
     * @param bool $invertRelation (Default TRUE) serve per chiamare il proxy di inversione delle relazioni
     * @return array|null
     */
    public function saveDraft($data, $invertRelation = true)
    {
        $isDraft = true;
        $data->isValid = 0;

        return $this->saveProcedure($data, $isDraft);
    }

    public function delete($id, $model)
    {
        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);

        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $contentproxy->delete($id, $model);

        return true;
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams = null)
    {
        $it = org_glizy_ObjectFactory::createModelIterator('metafad.mods.models.Model')
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'));

        if ($term) {
            $it->where('titolo', '%'.$term.'%', 'ILIKE');
        }

        $result = array();
        foreach ($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->titolo
            );
        }
        return $result;
    }
}
