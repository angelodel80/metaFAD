<?php
class archivi_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model, $recurse = false)
    {
        $this->checkPermissionForBackend('delete');

        $archiveProxy = __ObjectFactory::createObject('archivi.models.proxy.ArchiviProxy');

        $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        if ($it->count() == 0){
            $archiveProxy->delete($id);
        } else if ($recurse) {
            $archiveProxy->delete($id, $recurse);
        } else {
            $this->logAndMessage( "il livello di descrizione selezionato contiene schede figlie, non è possibile cancellarlo senza prima aver cancellato tutti gli elementi subordinati", '', GLZ_LOG_ERROR);
        }

        $parentId = __Request::get('listDetail');

        if ($parentId) {
            $arParent = org_glizy_objectFactory::createModel('archivi.models.Model');
            $arParent->load($parentId, 'PUBLISHED_DRAFT');
            
            $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
                ->load('getParent', array(':parent' => $parentId, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        
            $params = array(
                'pageId' => $arParent->pageId,
                'action' => 'listDetail',
                'id' => $arParent->getId()
            );

            $route = 'archiviMVC';

            // se parent ha ancora figli si viene rediretti verso di lui, altrimenti al nonno
            if ($it->count() == 0) {
                if ($arParent->parent) {
                    $arParent->load($arParent->parent['id'], 'PUBLISHED_DRAFT');
                    $params = array(
                        'pageId' => $arParent->pageId,
                        'action' => 'listDetail',
                        'id' => $arParent->getId()
                    );
                } else {
                    $params = array(
                        'pageId' => $arParent->pageId
                    );
                    $route = 'link';
                }
            }
            
            $url = __Link::makeUrl($route, $params);
            
            org_glizy_helpers_Navigation::gotoUrl($url);
        } else {
            org_glizy_helpers_Navigation::goHere();
        }
    }
}
