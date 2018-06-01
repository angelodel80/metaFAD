<?php

class metafad_teca_MAG_Module
{
    static function registerModule()
    {
        glz_loadLocale('metafad.teca.MAG');
        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.teca.MAG';
        $moduleVO->name = 'MAG';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.teca.MAG';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '
        <glz:Page id="export-mag" parentId="export" pageType="metafad.teca.MAG.views.AdminExport" value="{i18n:MAG}" icon="fa fa-angle-double-right" adm:acl="*"/>

        <glz:Page parentId="teca" id="teca-MAG" value="{i18n:Gestione MAG}" icon="fa fa-angle-double-right" adm:acl="*" adm:aclPageTypes="tecaMAG,tecaMAGlist,tecaMAGimport,tecaMAGexport,img_popup,audio_popup,video_popup,doc_popup,ocr_popup,dis_popup,metadata_popup" select="*">
          <glz:Page pageType="metafad.teca.MAG.views.Admin" id="tecaMAG" value="{i18n:Mostra lista}" visible="true"/>
          <glz:Page pageType="metafad.teca.MAG.views.Admin" id="tecaMAGimport" value="{i18n:Importa}" visible="true" url="tecaMAG/import/"/>
        </glz:Page>
        
        <glz:Page pageType="metafad.teca.MAG.views.ImgPopup" id="img_popup" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.MAG.views.AudioPopup" id="audio_popup" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.MAG.views.VideoPopup" id="video_popup" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.MAG.views.DocPopup" id="doc_popup" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.MAG.views.OcrPopup" id="ocr_popup" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.MAG.views.DisPopup" id="dis_popup" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.MAG.views.MetadataPopup" id="metadata_popup" visible="true" parentId="" />
        ';
        $moduleVO->canDuplicated = false;

		if(__Config::get('metafad.be.hasMag'))
		{
			org_glizy_Modules::addModule($moduleVO);
		}
    }
}
