<?php
class AUT300_Module
{
    static function registerModule()
    {
        glz_loadLocale('AUT300');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'AUT300';
        $moduleVO->name = __T('AUT300.views.FrontEnd');
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'AUT300';
        $moduleVO->pageType = 'AUT300.views.FrontEnd';
        $moduleVO->model = 'AUT300.models.Model';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = 'http://www.metafadcms.it';
        $moduleVO->iccdModuleType = 'AUT';
        $moduleVO->siteMapAdmin = '
<glz:Page id="AUT300" value="{i18n:AUT300.views.FrontEnd}" pageType="AUT300.views.Admin" parentId="gestione-dati/authority/iccd" adm:acl="*"/>
<glz:Page id="AUT300_preview" pageType="AUT300.views.AdminPreview" parentId="" adm:acl="*" /><glz:Page pageType="AUT300.views.AdminPopup" id="AUT300_popup" visible="true" parentId="" />';
        $moduleVO->canDuplicated = false;
        $moduleVO->isICCDModule = true;
        $moduleVO->isAuthority = true;

        org_glizy_Modules::addModule( $moduleVO );
    }
}