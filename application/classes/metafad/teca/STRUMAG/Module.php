<?php

class metafad_teca_STRUMAG_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.teca.STRUMAG';
        $moduleVO->name = 'Metadati strutturati';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.teca.STRUMAG';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '<glz:Page parentId="teca" id="teca-strumag" pageType="metafad.teca.STRUMAG.views.Admin" value="{i18n:Metadati strutturali}" icon="fa fa-angle-double-right no-rot" adm:acl="*"/>';
        $moduleVO->canDuplicated = false;

        org_glizy_Modules::addModule($moduleVO);
    }
}