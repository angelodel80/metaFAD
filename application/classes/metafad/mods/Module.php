<?php
class metafad_mods_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'mods';
        $moduleVO->name = 'MODS';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.mods';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '
<glz:Page parentId="gestione-dati-bibliografico" id="mods" pageType="metafad.mods.views.Admin" value="{i18n:MODS}" icon="" adm:acl="*"/>
';
        $moduleVO->canDuplicated = false;
        $moduleVO->hasDictionaries = true;

        org_glizy_Modules::addModule( $moduleVO );
    }
}