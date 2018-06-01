<?php
class archivi_controllers_EditDraft extends metafad_common_controllers_Command
{
    public function getStdLvl(){
        $arr = array(
            "archivi-complessoarchivistico" => "fondo",
            "archivi-unitaarchivistica" => "unita",
            "archivi-unitadocumentaria" => "documento-principale",
        );

        return $arr[__Request::get("pageId")];
    }

    public function execute($id, $sectionType, $type, $templateID, $parentId)
    {
        if ($id) {
            // read the module content
            $c = $this->view->getComponentById('__model');
            $model = $c->getAttribute('value');
            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $model, 'DRAFT');
            
            // se non esiste la versione DRAFt
            if (!$data['document_id']) {
                $url = __Link::makeUrl('archiviMVC', array(
                    'action' => 'edit'
                ));
                org_glizy_helpers_Navigation::gotoUrl($url);
            }

            if (__Request::get('action') == 'editDraft') {
                $this->checkPermissionAndInstitute('editDraft', $data['instituteKey'], 'DRAFT');
            }

            $data['__id'] = $id;
            $data['acronimoSistema'] = 'PDN';
            $data['modificaPersona'] = $this->user->firstName.' '.$this->user->lastName;
            $data['modificaData'] = new org_glizy_types_DateTime();

            if ($model == 'archivi.models.UnitaDocumentaria' || $model == 'archivi.models.UnitaArchivistica') {
                $arParent = org_glizy_objectFactory::createModel('archivi.models.Model');
                $arParent->load($data['parent']['id'], 'PUBLISHED_DRAFT');
                $link = __Link::makeLink('archiviMVC', array(
                    'pageId' => $arParent->pageId,
                    'action' => 'listDetail',
                    'id' => $arParent->getId(),
                    'label' => __T(end(explode('.', $model)))
                ));
                $evt = array('type' => GLZ_EVT_PAGETITLE_UPDATE, 'data' => $link);
                $this->dispatchEvent($evt);

                $this->view->getComponentById('linkedImages')->setAttribute('model', $model);
                __Session::set('idLinkedImages', $id);
            }

            if ($sectionType) {
                $data['livelloDiDescrizione'] = $data['livelloDiDescrizione'] ?: $this->getStdLvl();
            }

            $this->view->setData($data);
            if(!$data['isTemplate'] || $data['isTemplate'] != 1){
                $this->setComponentsVisibility('templateTitle', false);
            }
            $type = $data['type'];
        } else {
            $data = array('type' => $type);
            if($parentId){
                $parent = new stdClass();
                $parent->id = intval($parentId);
                $arParent = org_glizy_objectFactory::createModel('archivi.models.Model');
                if ($arParent->load($parentId, 'PUBLISHED_DRAFT')) {
                    if ($arParent->getRawData()->title && $arParent->getRawData()->type) {
                        $parent->text = $arParent->title . " (" . $arParent->type . ")";
                    }
                }
                $parent->path = '';

                // POLODEBUG-219 - ereditarietà del primo soggetto produttore
                if (!empty($arParent->produttori[0])) {
                    $data['produttori'] = array($arParent->produttori[0]);
                }

                $data['parent'] = $parent;
            }
            
            $data['acronimoSistema'] = 'PDN';
            $data['inserimentoPersona'] = $this->user->firstName.' '.$this->user->lastName;
            $data['inserimentoData'] = new org_glizy_types_DateTime();
            $data['modificaPersona'] = $this->user->firstName.' '.$this->user->lastName;
            $data['modificaData'] = new org_glizy_types_DateTime();
            if ($templateID != '0' && $templateID != '') {
                $c = $this->view->getComponentById('__model');
                $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
                $data = $contentproxy->loadContent($templateID, $c->getAttribute('value'));
                $data['parent'] = $parent;
                unset($data['isTemplate']);
                unset($data['templateTitle']);
                $this->setComponentsVisibility('templateTitle', false);
            } else if ($templateID == '0') {
                $data['isTemplate'] = 1;
                $this->setComponentsVisibility('templateTitle', true);
            } else {
                $this->setComponentsVisibility('templateTitle', false);
            }

            if ($sectionType){
                $data['livelloDiDescrizione'] = $data['livelloDiDescrizione'] ?: $this->getStdLvl();
            }

            $this->view->setData($data);
        }

        if ($type=='fondo') {
            $this->setComponentsVisibility('parent', false);
        }
        $this->setComponentsVisibility('tabs', false);
    }
}
