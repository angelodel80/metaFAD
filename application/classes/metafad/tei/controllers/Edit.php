<?php
class metafad_tei_controllers_Edit extends metafad_common_controllers_Command
{
    public function execute($id, $sectionType, $type, $templateID, $parentId)
    {
        if ($id) {
            // read the module content
            $c = $this->view->getComponentById('__model');
            __Request::set('model', $c->getAttribute('value'));

            $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentproxy->loadContent($id, $c->getAttribute('value'));
            $data['__id'] = $id;

            $this->checkPermissionAndInstitute('edit', $data['instituteKey']);

            $this->view->setData($data);
            if(!$data['isTemplate'] || $data['isTemplate'] != 1){
                $this->setComponentsVisibility('templateTitle', false);
            }

            if ($data['sectionType'] == 'manoscritto-unitario') {
                $this->setComponentsVisibility('sommario', false);
            }

            if ($data['sectionType'] == 'manoscritto-composito') {
                $this->setComponentsVisibility('textualUnits', false);
            }

            $type = $data['type'];
        }
        else {
            $data = array('type' => $type);
            if($parentId){
                $parent = new stdClass();
                $parent->id = intval($parentId);
                $record = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
                if ($record->load($parentId)) {
                    if ($record->getRawData()->title && $record->getRawData()->type) {
                        $parent->text = $record->title . " (" . $record->type . ")";
                    }
                }
                $parent->path = '';
            }
            $data['parent'] = $parent;
            if ($templateID != '0' && $templateID != '') {
                $c = $this->view->getComponentById('__model');
                $contentproxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
                $data = $contentproxy->loadContent($templateID, $c->getAttribute('value'));

                $this->checkPermissionAndInstitute('edit', $data['instituteKey']);

                $data['parent'] = $parent;
                unset($data['isTemplate']);
                unset($data['templateTitle']);
                $this->setComponentsVisibility('templateTitle', false);
            } else if ($templateID == '0') {
                $data['isTemplate'] = 1;
                $this->setComponentsVisibility('templateTitle', true);
            } else{
                $this->setComponentsVisibility('templateTitle', false);
            }

            if ($sectionType) {
                $data['sectionType'] = $sectionType;
            }

            if ($sectionType== 'manoscritto-unitario') {
                $this->setComponentsVisibility('sommario', false);
            }

            if ($sectionType == 'manoscritto-composito') {
                $this->setComponentsVisibility('textualUnits', false);
            }

            unset($data['pageId']);
            $this->view->setData($data);
        }
    }
}