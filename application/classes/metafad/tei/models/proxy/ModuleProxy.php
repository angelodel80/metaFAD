<?php
set_time_limit(0);

class metafad_tei_models_proxy_ModuleProxy extends metafad_common_models_proxy_SolrQueueProxy
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
        $result = $this->proxy->validate($data, $data->__model);
        if ($result === true) {
            $data->isValid = 1;
            $result = $this->proxy->saveContent($data, true);

            $this->appendDocumentToData($data);
            $this->sendDataToSolr($data, true);

            if ($result['__id']) {
                return array('set' => $result);
            } else {
                return array('errors' => $result);
            }
        } else {
            return array('errors' => $result);
        }
    }

    public function load($id, $model, $status='PUBLISHED')
    {
        return $this->proxy->loadContent($id, $model);
    }

    /**
     * Esegue il salvataggio
     * @param $data StdClass
     * @return mixed Restituisce $result
     */
    public function save($data)
    {
        $isDraft = false;
        $data->isValid = 0;
        $data->root = (!$data->parent) ? 'true' : 'false';

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

            $ar = __ObjectFactory::createModel('metafad.tei.models.Model');
            $ar->load($data->__id);
            $res['set']['text'] = $ar->getTitle();
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
     * Esegue quel che avrebbe eseguito la metafad_tei_controllers_ajax_SaveDraft::execute()
     * @param $data stdClass
     * @param bool $invertRelation (Default TRUE) serve per chiamare il proxy di inversione delle relazioni
     * @return array|null
     */
    public function saveDraft($data, $invertRelation = true)
    {
        $isDraft = true;
        $data->isValid = 0;
        $data->root = (!$data->parent) ? 'true' : 'false';

        return $this->saveProcedure($data, $isDraft);
    }

    public function delete($id, $recurse = false, $control = false)
    {
        $this->stack[] = $id;
        $ret = array($id);
        $it =
            $recurse === true ?
                org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Manoscritto')
                    ->where('parent', $id)
                    ->allTypes()
                :
                array();

        foreach ($it as $ar) {
            if (!in_array($ar->getId(), $this->stack)) {
                $ret = array_merge($ret, $this->delete($ar->getId(), $recurse));
            }
        }

        $this->deleteItem($id, $control);
        //echo "Deleted item $id\n<br>\n";
        array_pop($this->stack);
        return $ret;
    }

    private function deleteItem($id, $control = false)
    {
        if ($control === true && $this->control($id)) {
            return false;
        } else {
            $evt = array('type' => 'deleteRecord', 'data' => $id);
            $this->dispatchEvent($evt);

            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $contentproxy->delete($id, 'metafad.tei.models.Manoscritto');

            return true;
        }
    }

    public function control($id)
    {
        $it = __ObjectFactory::createModelIterator('metafad.tei.models.Manoscritto');

        $found = false;
        foreach ($it as $ar) {
            $parentId = $ar->getRawData()->parentId;
            if ($parentId && $parentId == $id) {
                $found = true;
            }
        }

        return $found;
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams = null)
    {
        $it = org_glizy_ObjectFactory::createModelIterator($model);
            //->setOptions(array('type' => 'PUBLISHED_DRAFT'));

        /*if ($term) {
            $it->where('acronimoSistema', '%'.$term.'%', 'ILIKE');
        }*/

        $result = array();
        foreach ($it as $ar) {
            $title = $ar->getTitle();
            if (!$term || strpos($title, $term) !== false){
                $result[] = array(
                    'id' => $ar->getId(),
                    'text' => $title
                );
            }
        }
        return $result;
    }
}
