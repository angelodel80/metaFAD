<?php
class BIB400_Module
{
    static function registerModule()
    {
        glz_loadLocale('BIB400');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'BIB400';
        $moduleVO->name = __T('BIB400.views.FrontEnd');
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'BIB400';
        $moduleVO->pageType = 'BIB400.views.FrontEnd';
        $moduleVO->model = 'BIB400.models.Model';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = 'http://www.metafadcms.it';
        $moduleVO->iccdModuleType = 'BIB';
        $moduleVO->siteMapAdmin = '
<glz:Page id="BIB400" value="{i18n:BIB400.views.FrontEnd}" pageType="BIB400.views.Admin" parentId="gestione-dati/authority/iccd" adm:acl="*"/>
<glz:Page id="BIB400_preview" pageType="BIB400.views.AdminPreview" parentId="" adm:acl="*" /><glz:Page pageType="BIB400.views.AdminPopup" id="BIB400_popup" visible="true" parentId="" />';
        $moduleVO->canDuplicated = false;
        $moduleVO->isICCDModule = true;
        $moduleVO->isAuthority = true;

        org_glizy_Modules::addModule( $moduleVO );
    }
}