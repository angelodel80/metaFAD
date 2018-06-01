<?php
set_time_limit (0);

class metafad_teca_STRUMAG_models_proxy_StruMagProxy extends GlizyObject
{

    protected $application;

    function __construct()
    {
        $this->application = org_glizy_ObjectValues::get('org.glizy', 'application');
    }

    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = __ObjectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model');
        $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');

        if (metafad_usersAndPermissions_Common::getInstituteKey() != '*'){
            $it->where("instituteKey", metafad_usersAndPermissions_Common::getInstituteKey());
        }

        if ($term != '') {
            if(is_numeric($term)){
                $it->where('document_id', $term);
            } else{
                $it->where('title', '%'.$term.'%', 'ILIKE');
            }
        }

        $result = array();
        $size = 0;
        foreach($it as $ar) {
            $decodeRelatedMag = json_decode($ar->MAG);
            $existsDocRel = $document->load($decodeRelatedMag->id);
            if(!$existsDocRel) {
                $result[] = array(
                    'id' => $ar->getId(),
                    'text' => $ar->title
                );

                if(++$size >= 25) break;
            }
        }

        return $result;
    }

    public function modify($id, $data)
    {
        if ($this->validate($data)) {

            $document = $this->createModel($id, 'metafad.teca.STRUMAG.models.Model');

            foreach ($data as $key => $value) {
                $document->$key = $value;
            }

            try {
                return $document->publish(null, null);
            } catch (org_glizy_validators_ValidationException $e) {
                return $e->getErrors();
            }
        } else {
            // TODO
        }
    }

    public function validate($data)
    {
        return true;
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

    public function delete($id, $detachMag=true)
    {
        $this->detachStruMag($id);

        $model = 'metafad.teca.STRUMAG.models.Model';
        $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
        $content = $contentproxy->loadContent($id, $model);

        if ($detachMag) {
            $magProxy = __ObjectFactory::createObject('metafad.teca.MAG.models.proxy.MagProxy');

            $idMag = json_decode($content['MAG'])->id;
            if ($idMag) {
                $doc = new stdClass();
                $doc->relatedStru = "";
                $magProxy->modify($idMag, $doc);
            }
        }

        $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia');
        $result = $dam->search($content['title']);

        if (!empty($result)) {
            // cancellazione container dal DAM
            $dam->deleteContainer($result[0]->id, true);
        }

        $models = array(
            'archivi.models.UnitaArchivistica',
            'archivi.models.UnitaDocumentaria',
            'metafad.sbn.modules.sbnunimarc.model.Model'
        );

        $contentproxy->delete($id, $model);

        $evt = array('type' => 'deleteRecord', 'data' => $id);
        $this->dispatchEvent($evt);
    }

    protected function detachStruMag($id)
    {
        $models = array(
            'archivi.models.UnitaArchivistica',
            'archivi.models.UnitaDocumentaria',
            'metafad.sbn.modules.sbnunimarc.model.Model'
        );

        foreach ($models as $model) {
            $it = org_glizy_objectFactory::createModelIterator($model)
                ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
                ->where('linkedStruMag', $id);

            foreach ($it as $ar) {
                $ar->linkedStruMag = null;
                $ar->saveCurrentPublished();
            }
        }
    }
}