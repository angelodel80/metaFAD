<?php
class metafad_Metafad
{
    static $application;

    public static function init()
    {
        glz_loadLocale('metafad');

        $log = org_glizy_log_LogFactory::create( 'DB', array(), __Config::get( 'metafad.log.level' ), __Config::get('metafad.log.group'));

        self::$application = org_glizy_ObjectValues::get('org.glizy', 'application');
    }


    public static function logOperation($msg, $group = '')
    {
        self::$application->dispatchEventByArray( GLZ_LOG_EVENT, array('level' => GLZ_LOG_SYSTEM,
            'group' => $group,
            'message' => $msg ));
    }

    public static function logAction($msg, $group = '')
    {
        self::$application->dispatchEventByArray( GLZ_LOG_EVENT, array('level' => GLZ_LOG_INFO,
            'group' => $group,
            'message' => $msg ));
    }

    public static function logError($msg, $group = '')
    {
        self::$application->dispatchEventByArray( GLZ_LOG_EVENT, array('level' => GLZ_LOG_ERROR,
            'group' => $group,
            'message' => $msg ));
    }
}