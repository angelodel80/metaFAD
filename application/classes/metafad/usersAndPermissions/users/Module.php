<?php
class metafad_usersAndPermissions_users_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.usersAndPermissions.users';
        $moduleVO->name = 'Utenti';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.usersAndPermissions.users';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule($moduleVO);
    }
}