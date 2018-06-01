<?php
class metafad_teca_DAM_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.teca.DAM';
        $moduleVO->name = 'Metadati strutturati';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.teca.DAM';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="teca" id="teca-dam" pageType="metafad.teca.DAM.views.Admin" value="{i18n:DAM}" icon="fa fa-angle-double-right no-rot" adm:acl="*" />';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule($moduleVO);
    }
}