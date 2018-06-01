<?php
class archivi_controllers_ajax_GetLevelType extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id, $typeId)
    {
        $this->directOutput = true;
        
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        if (!$id) {
            $id = 0;
        }

        if ($typeId) {
            $queryName = 'selectType';

            if ($typeId == 'documento-principale') {
                $queryName = 'selectTypeUD';
            } else if ($typeId == 'unita-documentaria' || $typeId == 'documento-allegato') {
                return array();
            } 

            $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.ArchiveType')
                ->load($queryName, array(':typeId' => $typeId));
        } else {
            $it = org_glizy_ObjectFactory::createModelIterator('archivi.models.ArchiveType')
                ->load('allTypes');
        }

        $result = array();

        foreach ($it as $ar) {
            $result[] = array(
                'typeId' => $ar->archive_type_key,
                'typeName' => $ar->archive_type_name,
                'routing' => __Routing::makeUrl('archiviMVC', array(
                    'id' => $id,
                    'pageId' =>  $ar->archive_type_pageId,
                    'sectionType' => $ar->archive_type_key,
                    'action' => 'edit'
                ))
            );
        }

        return $result;
    }
}