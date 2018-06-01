<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 07/11/16
 * Time: 11.05
 */
class metafad_gestioneDati_boards_models_proxy_ICCDProxy extends metafad_common_models_proxy_SolrQueueProxy
{
    /**
     * @param $objData stdClass
     * @return array
     */
    public function saveDraft($objData)
    {
        $objData->isValid = 0;

        $result = $this->saveData($objData, true);

        return $result;
    }

    /**
     * @param $objData
     * @param bool $tryDraft
     * @return array
     */
    public function save($objData, $tryDraft = false)
    {
        $objData->isValid = 0;

        $result = $this->saveData($objData, false);

        if (key_exists("errors", $result) && $tryDraft){
            return $this->saveDraft($objData);
        }

        //Salvataggio su FE solo in caso di scheda PUBLISHED e solo per POLO_NAPOLI
        //ed escludo i template da questo
        if (__Config::get('metafad.be.hasFE') == 'true' && !$objData->isTemplate) {
            $evtFe = array('type' => 'insertRecordFE', 'data' => array('data' => $objData, 'option' => array('commit' => true)));
            $this->dispatchEvent($evtFe);

            //Indicizzo anche su metaindice
            $d = org_glizy_objectFactory::createModel($objData->__model);
            $solrModel = $d->getFESolrDocument();
            if ($solrModel['feMapping']) {
                $metaindice = org_glizy_ObjectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
                $metaindice->mapping($objData, 'iccd');
            }
            else if($objData->__model == 'AUT300.models.Model' || $objData->__model == 'AUT400.models.Model'){
              $metaindice = org_glizy_ObjectFactory::createObject('metafad.solr.helpers.MetaindiceHelper');
              $metaindice->mapping($objData,'iccdaut');
            }
        }

        return $result;
    }

    /**
     * @param $objData stdClass
     * @param $isDraft
     * @return array
     */
    protected function saveData($objData, $isDraft)
    {
        //Creazione indice identificativo univoco
        $uniqueIccdIdProxy = org_glizy_ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.UniqueIccdIdProxy');
        $objData->uniqueIccdId = $uniqueIccdIdProxy->createUniqueIccdId($objData);

        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $result = $contentproxy->saveContent($objData, __Config::get('glizycms.content.history'), $isDraft === true);

        if ($result['__id']) {
            $result = array('set' => $result);
        } else {
            return array('errors' => $result);
        }
        $objData->__id = $result['set']['__id'];

        $relationsProxy = org_glizy_ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.RelationsProxy');
        $relationsProxy->processRelations($objData);

        //Eventuale link a SBN ed a SBN AUT
        $sbnToIccdProxy = org_glizy_ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.model.proxy.SbnToIccdProxy');
        if($objData->BID && !$isDraft)
        {
          $sbnToIccdProxy->updateSbnToIccd('id',$objData->__id,$objData->BID,'metafad.sbn.modules.sbnunimarc.model.Model');
          $sbnToIccdProxy->updateSbnToIccdSolr($objData->BID,$objData->__id,'sbn');
        }
        if($objData->VID && !$isDraft)
        {
          $sbnToIccdProxy->updateSbnToIccd('id',$objData->__id,$objData->VID,'metafad.sbn.modules.authoritySBN.model.Model');
          $sbnToIccdProxy->updateSbnToIccdSolr($objData->VID,$objData->__id,'sbnaut');
        }

        $this->appendDocumentToData($objData);

        parent::sendDataToSolr($objData, array('commit' => true));
        return $result;
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

    public function delete($id = null, $model)
    {
        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);

        //Necessario a cancellare anche da eventuale indice FE e metaindice
        $this->deleteFromFE($id);

        $relationsProxy = org_glizy_ObjectFactory::createObject( 'metafad.gestioneDati.boards.models.proxy.RelationsProxy' );
        $relationsProxy->deleteRelations($id);

        if (__Config::get('metafad.be.hasSBN') == 'true')
        {
            $sbnToIccdProxy = org_glizy_ObjectFactory::createObject('metafad.sbn.modules.sbnunimarc.model.proxy.SbnToIccdProxy');
            $sbnToIccdProxy->deleteSbnToIccdSolr($id,'sbn','metafad.sbn.modules.sbnunimarc.model.Model');
            $sbnToIccdProxy->deleteSbnToIccdSolr($id,'sbnaut','metafad.sbn.modules.authoritySBN.model.Model');
            $sbnToIccdProxy->updateSbnToIccd('linkedIccd','',$id,'metafad.sbn.modules.sbnunimarc.model.Model');
            $sbnToIccdProxy->updateSbnToIccd('linkedIccd','',$id,'metafad.sbn.modules.authoritySBN.model.Model');
        }

        if ($id) {
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $contentproxy->delete($id, $model);
        }
    }

    public function deleteFromFE($id = null)
    {
      if(__Config::get('metafad.be.hasFE') == 'true')
      {
        $evt2 = array('type' => 'deleteRecord', 'data' => array('id'=>$id,'option' => array('url' => __Config::get('metafad.solr.iccd.url'))));
        $this->dispatchEvent($evt2);
        $evt3 = array('type' => 'deleteRecord', 'data' => array('id'=>$id,'option' => array('url' => __Config::get('metafad.solr.metaindice.url'))));
        $this->dispatchEvent($evt3);
        $evt4 = array('type' => 'deleteRecord', 'data' => array('id'=>$id,'option' => array('url' => __Config::get('metafad.solr.metaindiceaut.url'))));
        $this->dispatchEvent($evt4);
        $evt5 = array('type' => 'deleteRecord', 'data' => array('id'=>$id,'option' => array('url' => __Config::get('metafad.solr.iccdaut.url'))));
        $this->dispatchEvent($evt5);
      }
    }
}
