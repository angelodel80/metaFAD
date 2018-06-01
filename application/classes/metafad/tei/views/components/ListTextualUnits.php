<?php
class metafad_tei_views_components_ListTextualUnits extends org_glizy_components_Component
{
    function process()
    {
        $it = org_glizy_ObjectFactory::createModelIterator('metafad.tei.models.UnitaTestuale')
            ->setOptions(array('type' => 'PUBLISHED_DRAFT'))
            ->where('parent', __Request::get('id'));

        $this->_content['titles'] = array();

        foreach ($it as $ar) {
            $this->_content['titles'][] = array(
                'title' => ($ar->titolo ? $ar->titolo : 'Scheda senza titolo')
            );
        }

        $this->_content['parentId']  = __Request::get('id');
    }
}