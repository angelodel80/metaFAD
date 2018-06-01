<?php
class metafad_teca_STRUMAG_rest_controllers_List extends org_glizy_rest_core_CommandRest
{
    function execute($search)
    {
        $this->checkPermissionForBackend();
        
        $it = __objectFactory::createModelIterator('metafad.teca.STRUMAG.models.Model');

        if ($search) {
            $it->where('title', '%'.$search.'%', 'ILIKE');
        }

        $result = array();

        foreach ($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'title' => $ar->title,
            );
        }

        return $result;
    }
}
