<?php
class metafad_tei_controllers_ajax_GetTree extends metafad_common_controllers_ajax_CommandAjax
{
    private $selectedId;

    public function execute($id = null, $getRoot = false)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();

        $this->selectedId = $id;
        $this->directOutput = true;
        $tree = array();
        if ($getRoot == 'true') {
            $trees = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Model')
                    ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
                    ->where('root','true')
                    ->orderBy('document_id');

            if ($instituteKey != '*') {
                $trees->where('instituteKey', $instituteKey);
            }

            foreach ($trees as $t) {
                $tree[] = $this->loadTree($t->document_id, new stdClass());
            }
        } else {
            if ($id) {
                $tree[] = $this->loadTree($id, new stdClass());
            } else {
                $node = new StdClass();
                $node->id = (int) $id;
                $node->active = true;
                $node->title = '(Non noto): id = ';
                $node->expanded = true;
                $node->children = array();

                $tree[] = $node;
            }
        }
        return $tree;
    }

    public function loadTree($id, $subTree)
    {
        $ar = org_glizy_ObjectFactory::createModel('metafad.tei.models.Model');
        $ar->load($id, 'PUBLISHED_DRAFT');
        $tree = $this->createNodeFromAr($id, $ar);

        $it = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Model')
            ->load('getParent', array(':parent' => $id, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

        $arChild = org_glizy_ObjectFactory::createModel('metafad.tei.models.Model');

        foreach ($it as $ar2) {
            if ($ar2->getId() == $subTree->id) {
                $child = $subTree;
            } else {
                $arChild->load($ar2->getId(), 'PUBLISHED_DRAFT');
                $child = $this->createNodeFromAr($arChild->getId(), $arChild, false);

                $it2 = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.Model')
                    ->load('getParent', array(':parent' => $arChild->getId(), ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));

                if ($it2->count()) {
                    $child->folder = true;
                    $child->lazy = true;
                }
            }

            $tree->children[] = $child;
        }

        $tree->folder = !empty($tree->children);

        if ($ar->parent && $ar->parent['id']) {
            return $this->loadTree($ar->parent['id'], $tree);
        }

        return $tree;
    }

    protected function createNodeFromAr($id, $ar, $expanded = true)
    {
        $pageId = $ar->pageId ?: "tei-".strtolower(str_replace("metafad.tei.models.", "", $ar->document_type));

        $tree = new stdClass();
        $tree->id = (int) $id;
        $tree->active = $this->selectedId == $id;
        $tree->title = $ar->getTitle();
        $tree->type = $ar->getTeiType();
        $tree->expanded = $expanded === true;
        $tree->children = array();
        $tree->canAdd = $ar->canAdd();
        $tree->canEdit = $ar->hasPublishedVersion();
        $tree->canEditDraft = $ar->hasDraftVersion();

        $tree->routingEdit = __Routing::makeUrl('archiviMVC', array(
            'id' => $id,
            'pageId' => $pageId,
            'sectionType' => $ar->getTeiType(),
            'action' => 'edit'
        ));

        $tree->routingEditDraft = __Routing::makeUrl('archiviMVC', array(
            'id' => $id,
            'pageId' => $pageId,
            'sectionType' => $ar->getTeiType(),
            'action' => 'editDraft'
        ));

        return $tree;
    }
}
