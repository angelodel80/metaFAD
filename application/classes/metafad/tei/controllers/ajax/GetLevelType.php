<?php
class metafad_tei_controllers_ajax_GetLevelType extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id, $typeId)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }
        
        if (!$id) {
            $id = 0;
        }

        if ($typeId) {
            $it = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.TeiType')
                ->load('selectType', array(':typeId' => $typeId));
        } else {
            $it = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.TeiType')
                ->load('allTypes');
        }

        $result = array();

        foreach ($it as $ar) {
            $result[] = array(
                'typeId' => $ar->tei_type_key,
                'typeName' => $ar->tei_type_name,
                'routing' => __Routing::makeUrl('archiviMVC', array(
                    'id' => $id,
                    'pageId' =>  $ar->tei_type_pageId,
                    'sectionType' => $ar->tei_type_key,
                    'action' => 'edit'
                ))
            );
        }

        $this->directOutput = true;
        return $result;
    }
}