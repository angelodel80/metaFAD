<?php
class metafad_usersAndPermissions_institutes_views_components_InstituteSelect extends org_glizy_components_Component
{
    function process()
    {
        $proxy = __ObjectFactory::createObject('metafad.usersAndPermissions.relations.models.proxy.RelationsProxy');
        $institutes = $proxy->getInstitutesOfCurrentUser();

        $this->_content['institutes'] = array();

        foreach ($institutes as $institute) {
            $vo = array(
                'title' => $institute['text'],
                'url' => __Routing::makeUrl('metafad.usersAndPermissions.instituteSelect', array(
                    'instituteKey' => $institute['key']
                ))
            );
            $this->_content['institutes'][] = $vo;
        }
    }
}