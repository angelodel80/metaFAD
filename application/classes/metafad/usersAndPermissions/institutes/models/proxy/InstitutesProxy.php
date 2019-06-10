<?php
class metafad_usersAndPermissions_institutes_models_proxy_InstitutesProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $it = __ObjectFactory::createModelIterator('metafad.usersAndPermissions.institutes.models.Model');

        if ($term != '') {
            $it->where('institute_name', '%'.$term.'%', 'ILIKE');
        }

        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();

        $application = org_glizy_ObjectValues::get('org.glizy', 'application');

        if ($instituteKey != '*' && $application->_user->id !== 1) {
            $it->where('institute_key', $instituteKey);
        }

        $result = array();

        foreach($it as $ar) {
            $result[] = array(
                'id' => $ar->getId(),
                'text' => $ar->institute_name,
                'key' => $ar->institute_key
            );
        }

        return $result;
    }

    public function getInstituteVoById($instituteId)
    {
        $ar = org_glizy_objectFactory::createModel('metafad.usersAndPermissions.institutes.models.Model');
        $ar->load($instituteId);
        return $ar->getValues();
    }

    public function getInstituteVoByKey($instituteKey)
    {
        $ar = org_glizy_objectFactory::createModel('metafad.usersAndPermissions.institutes.models.Model');
        $ar->find(array('institute_key' => $instituteKey));
        return $ar->getValues();
    }

    public function getOtherInstitutesList($instituteKey)
    {
        $list = array();
        $it = org_glizy_objectFactory::createModelIterator('metafad.usersAndPermissions.institutes.models.Model')
                ->where('institute_key',$instituteKey,'<>');
        foreach($it as $ar)
        {
            array_push($list, $ar->institute_key);
        }
        return $list;
    }
}