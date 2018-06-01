<?php
class AUT400_controllers_ajax_FindTerm extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($fieldName, $model, $term, $id)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $it = org_glizy_objectFactory::createModelIterator('AUT400.models.Model');

        if ($id) {
          $it->where('document_id',$id);
        }

        if ($term != '') {
            $it->where('AUTN', '%'.$term.'%', 'ILIKE');
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->AUTN.' - '.$ar->AUTH,
                'values' => $ar->getValuesAsArray(false, false, false, false)
            );
        }

        $this->directOutput = false;
        return $result;
    }
}