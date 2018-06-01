<?php
class metafad_ecommerce_licenses_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.ecommerce.licenses';
        $moduleVO->name = 'Licenses';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.ecommerce.licenses';
        $moduleVO->model = 'metafad.ecommerce.licenses.models.Model';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page pageType="metafad.ecommerce.licenses.views.Admin" icon="fa fa-angle-double-right no-rot" parentId="ecommerce" id="ecommerce-licences" value="{i18n:Licenze}" adm:acl="*"/>';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule( $moduleVO );

    }
}
