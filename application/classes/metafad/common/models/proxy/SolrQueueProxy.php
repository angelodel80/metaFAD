<?php

/**
 * Class metafad_common_models_proxy_SolrQueueProxy
 */
class metafad_common_models_proxy_SolrQueueProxy extends GlizyObject
{
    protected $queueSize = 1;
    private $count = 0;

    /**
     * @return int
     */
    public function getQueueSize()
    {
        return $this->queueSize;
    }

    /**
     * @param int $queueSize
     * @return metafad_common_models_proxy_SolrQueueProxy
     */
    public function setQueueSize($queueSize)
    {
        $this->queueSize = max($queueSize, 1);
        return $this;
    }

    /**
     * Fa il commit dell'eventuale coda rimasta.
     */
    public function commit(){
        $evt = array('type' => 'commit');
        $this->dispatchEvent($evt);
    }

    /**
     * @param string $type
     * @param stdClass $data
     * @param array $option
     * @throws Exception
     */
    public function raiseEventToSolrListener($type = 'insertRecord', $data = null, $option = array()){
        $this->count = $this->count + 1;
        $option = is_array($option) ? $option : array();
        $opt = array_merge($this->getSolrOption(), $option); //Faccio override delle opzioni di default dela getSolrOption

        $evt = array('type' => $type ?: 'insertRecord', 'data' => array('data' => $data, 'option' => $opt));
        try{
            $this->dispatchEvent($evt);
        } catch (Exception $ex){
            $this->commit();
            $this->count = 0;
            throw new Exception("Errore nel dispatch dell'evento", -1, $ex);
        }

        $this->count = $opt['commit'] ? 0 : $this->count;
    }

    /**
     * @param $data stdClass
     * @throws Exception
     */
    public function sendDataToSolr($data, $option=array()){
        $this->raiseEventToSolrListener('insertRecord', $data, $option);
    }

    /**
     * @return array
     */
    public function getSolrOption()
    {
        $coda = max($this->queueSize, 1);

        $option = ($coda > $this->count ? array('queue' => true, 'commit' => false) : array('commit' => true, 'queue' => false));
        return $option;
    }

    /**
     * @param $data
     */
    public function appendDocumentToData($data, $ar=null)
    {
        if ($data->__id) {
            if (!$ar) {
                $ar = __ObjectFactory::createModel($data->__model);
                $ar->load($data->__id, 'PUBLISHED_DRAFT');
            }

            $cl = new stdClass();
            if ($data->livelloDiDescrizione) {
                $cl->sectionType = $data->livelloDiDescrizione;
            }
            $cl->className = $data->__model;
            $cl->isVisible = $ar->isVisible();
            $cl->isTranslated = $ar->isTranslated();
            $cl->hasPublishedVersion = $ar->hasPublishedVersion();
            $cl->hasDraftVersion = $ar->hasDraftVersion();
            $cl->document_detail_status = $ar->getStatus();
			if($data->__model == 'metafad.opac.models.Model')
			{
				$cl->fields = $ar->fields;
			}

            $data->document = json_encode($cl);
        }
    }

    public function deleteAll($model)
    {
        $option = array('query' => 'document_type_t:'.$model);
        $this->raiseEventToSolrListener('deleteAll', null, $option);
    }

    public function reindexAll($model, $removeFirst=true)
    {
        if ($removeFirst) {
            $this->deleteAll($model);
        }

        $it = org_glizy_objectFactory::createModelIterator($model)
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'));
        foreach ($it as $ar) {
            $data = (object)$ar->getValuesAsArray();
            $data->__model = $model;
            $data->__id = $ar->document_id;

            $this->appendDocumentToData($data, $ar);
            $this->sendDataToSolr($data);
        }

        if ($this->queueSize > 1) {
            $this->commit();
        }
    }
}
