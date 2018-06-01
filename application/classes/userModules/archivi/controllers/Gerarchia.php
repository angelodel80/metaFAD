<?php
class archivi_controllers_Gerarchia extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        if ($id) {
            $path = array();
            $ar = org_glizy_ObjectFactory::createModel('archivi.models.Model');
            if ($ar->load($id, 'PUBLISHED_DRAFT')) {
                while (is_object($ar->parent)) {
                    $parent = $ar->parent->id;
                    $path[] = $parent;
                    $ar->emptyRecord();
                    if (!$ar->load($parent, 'PUBLISHED_DRAFT')) {
                        break;
                    }
                }
            }

            $path = array_reverse($path);
            array_shift($path);
            $this->setComponentsAttribute('treeview', 'selectId', $id);
            $this->setComponentsAttribute('treeview', 'path', json_encode($path));
        } else {
            $this->setComponentsAttribute(array('stateHistory'), 'draw', false);
        }
        $this->setComponentsVisibility('tabs', true);
    }
}