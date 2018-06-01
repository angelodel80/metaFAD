<?php
class SchedaOA300_Module
{
    static function registerModule()
    {
        glz_loadLocale('SchedaOA300');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'SchedaOA300';
        $moduleVO->name = __T('SchedaOA300.views.FrontEnd');
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'SchedaOA300';
        $moduleVO->pageType = 'SchedaOA300.views.FrontEnd';
        $moduleVO->model = 'SchedaOA300.models.Model';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = 'http://www.metafadcms.it';
        $moduleVO->iccdModuleType = 'OA';
        $moduleVO->siteMapAdmin = '
<glz:Page id="SchedaOA300" value="{i18n:SchedaOA300.views.FrontEnd}" pageType="SchedaOA300.views.Admin" parentId="gestione-dati/patrimonio" adm:acl="*"/>
<glz:Page id="SchedaOA300_preview" pageType="SchedaOA300.views.AdminPreview" parentId="" adm:acl="*" />
<glz:Page id="SchedaOA300_export" value="{i18n:SchedaOA300.views.FrontEnd}" pageType="SchedaOA300.views.AdminExport" parentId="export/patrimonio" icon="fa fa-angle-double-right" adm:acl="*" />';
        $moduleVO->canDuplicated = false;
        $moduleVO->isICCDModule = true;
        $moduleVO->isAuthority = false;

        org_glizy_Modules::addModule( $moduleVO );
    }
}