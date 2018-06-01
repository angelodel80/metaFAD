<?php
class archivi_Module
{
    static function registerModule()
    {
        glz_loadLocale('archivi');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'archivi';
        $moduleVO->name = 'Archivi';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'archivi';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '
<glz:Page parentId="gestione-dati" id="gestione-dati/archivi" pageType="" value="{i18n:Archivi}" icon="fa fa-angle-double-right" adm:acl="*" />
<glz:Page parentId="gestione-dati/archivi" id="archivi-ComplessoArchivistico" pageType="archivi.views.AdminComplessoArchivistico" value="{i18n:Complessi}" adm:acl="*"/>
<glz:Page parentId="gestione-dati/archivi" id="archivi-SchedaStrumentoRicerca" pageType="archivi.views.AdminSchedaStrumentoRicerca" value="{i18n:Strumenti}" adm:acl="*"/>
<glz:Page parentId="gestione-dati/archivi" id="archivi-FonteArchivistica" pageType="archivi.views.AdminFonteArchivistica" value="{i18n:Fonti Archivistiche}" adm:acl="*"/>
<glz:Page parentId="gestione-dati/archivi" id="archivi-UnitaArchivistica" pageType="archivi.views.AdminUnitaArchivistica" value="{i18n:Unità archivistica}" adm:acl="*" hide="true"/>
<glz:Page parentId="gestione-dati/archivi" id="archivi-UnitaDocumentaria" pageType="archivi.views.AdminUnitaDocumentaria" value="{i18n:Unità documentaria}" adm:acl="*" hide="true"/>
<glz:Page parentId="gestione-dati/authority/archivi" id="archivi-ProduttoreConservatore" pageType="archivi.views.AdminProduttoreConservatore" value="{i18n:Entità}" adm:acl="*"/>
<glz:Page parentId="gestione-dati/authority/archivi" id="archivi-SchedaBibliografica" pageType="archivi.views.AdminSchedaBibliografica" value="{i18n:Fonti Bibliografiche}" adm:acl="*"/>
<glz:Page parentId="gestione-dati/authority/archivi/voci-indice" id="archivi-Antroponimi" pageType="archivi.views.AdminAntroponimi" value="{i18n:Antroponimi}" adm:acl="*"/>
<glz:Page id="archivi-Antroponimi_popup" parentId="" pageType="archivi.views.AdminAntroponimi_popup"/>
<glz:Page parentId="gestione-dati/authority/archivi/voci-indice" id="archivi-Enti" pageType="archivi.views.AdminEnti" value="{i18n:Enti}" adm:acl="*"/>
<glz:Page id="archivi-Enti_popup" parentId="" pageType="archivi.views.AdminEnti_popup"/>
<glz:Page parentId="gestione-dati/authority/archivi/voci-indice" id="archivi-Toponimi" pageType="archivi.views.AdminToponimi" value="{i18n:Toponimi}" adm:acl="*"/>
<glz:Page id="archivi-Toponimi_popup" parentId="" pageType="archivi.views.AdminToponimi_popup"/>

<glz:Page parentId="gestione-dati/authority/archivi" id="gestione-dati/authority/archivi/voci-indice" pageType="" value="{i18n:Voci d\'Indice}" icon="fa fa-angle-double-right" adm:acl="*" />
';
        $moduleVO->canDuplicated = false;
        //Formato: archivi-<IdSezioneMinuscolo>@<NomeModello>
        $moduleVO->subPageTypes = array('archivi-complessoarchivistico@Complessi', 'archivi-schedastrumentoricerca@Strumenti', 'archivi-fontearchivistica@Fonti Archivistiche', 'archivi-unitaarchivistica@Unità archivistica', 'archivi-unitadocumentaria@Unità documentaria', 'archivi-produttoreconservatore@Entità', 'archivi-schedabibliografica@Fonti Bibliografiche', 'archivi-antroponimi@Antroponimi', 'archivi-enti@Enti', 'archivi-toponimi@Toponimi');
        $moduleVO->modelList = array("archivi.models.ComplessoArchivistico" ,"archivi.models.SchedaStrumentoRicerca" ,"archivi.models.FonteArchivistica" ,"archivi.models.UnitaArchivistica" ,"archivi.models.UnitaDocumentaria" ,"archivi.models.ProduttoreConservatore" ,"archivi.models.SchedaBibliografica" ,"archivi.models.Antroponimi" ,"archivi.models.Enti" ,"archivi.models.Toponimi" ,);
        org_glizy_Modules::addModule( $moduleVO );
    }
}
