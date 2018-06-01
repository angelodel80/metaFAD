<?php
class metafad_usersAndPermissions_Common
{
    public static function setInstituteKey($instituteKey)
    {
        $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
        $data = $instituteProxy->getInstituteVoByKey($instituteKey);

        __Session::set('usersAndPermissions.instituteId', $data->institute_id);
        __Session::set('usersAndPermissions.instituteKey', $instituteKey);
        __Session::set('usersAndPermissions.instituteName', $data->institute_name);
        __Config::set('gruppometa.dam.instance', $instituteKey);
    }
    
    public static function getInstituteId()
    {
        return __Session::get('usersAndPermissions.instituteId');
    }

    public static function getInstituteKey()
    {
        return __Session::get('usersAndPermissions.instituteKey');
    }

    public static function getInstituteName()
    {
        return __Session::get('usersAndPermissions.instituteName');
    }

    public static function getInstituteKeyByName($instituteName)
    {
        $s = str_replace(
            array(",", '"', ' ', 'à', 'è', 'ì', 'ò', 'ù'),
            array('-', '-', '-', 'a', 'e', 'i', 'o', 'u'),
            strtolower($instituteName)
        );
        return $s;
    }
}