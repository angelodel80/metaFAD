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

        if ($instituteKey != '*') {
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
}