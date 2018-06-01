<?php
class archivi_controllers_popup_ajax_FindTerm extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($fieldName, $model, $term, $id)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $it = org_glizy_objectFactory::createModelIterator($model);

        if ($id) {
            $it->where('document_id',$id);
        }

        if ($term != '') {
            $it->where('intestazione', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->intestazione,
                'values' => array()
            );
        }

        return $result;
    }
}