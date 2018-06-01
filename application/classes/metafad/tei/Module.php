<?php
class metafad_tei_Module
{
    static function registerModule()
    {
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'tei';
        $moduleVO->name = 'TEI';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.tei';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '
<glz:Page parentId="gestione-dati-bibliografico" id="tei-Manoscritto" pageType="metafad.tei.views.AdminManoscritto" value="{i18n:Manoscritti}" icon="" adm:acl="*"/>
<glz:Page parentId="gestione-dati-bibliografico" id="tei-UnitaCodicologica" pageType="metafad.tei.views.AdminUnitaCodicologica" value="{i18n:Unità codicologica}" icon="" adm:acl="*" hide="true"/>
<glz:Page parentId="gestione-dati-bibliografico" id="tei-UnitaTestuale" pageType="metafad.tei.views.AdminUnitaTestuale" value="{i18n:Unità testuale}" icon="" adm:acl="*" hide="true"/>
';
        $moduleVO->canDuplicated = false;
        $moduleVO->subPageTypes = array('tei-Manoscritto@Manoscritto', 'tei-UnitaCodicologica@Unità codicologica', 'tei-UnitaTestuale@Unità testuale');
        org_glizy_Modules::addModule( $moduleVO );
    }
}