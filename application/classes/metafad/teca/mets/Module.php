<?php
class metafad_teca_mets_Module
{
    static function registerModule()
    {
      glz_loadLocale('metafad.teca.mets');

        $moduleVO = org_glizy_Modules::getModuleVO();
        $moduleVO->id = 'metafad.teca.mets';
        $moduleVO->name = 'METS';
        $moduleVO->description = '';
        $moduleVO->version = '1.0.0';
        $moduleVO->classPath = 'metafad.teca.mets';
        $moduleVO->model = 'metafad.teca.mets.models.Model';
        $moduleVO->pageType = '';
        $moduleVO->author = 'META srl';
        $moduleVO->authorUrl = 'http://www.gruppometa.it';
        $moduleVO->pluginUrl = '';
        $moduleVO->siteMapAdmin = '
        <glz:Page parentId="teca" id="mets" value="{i18n:Gestione METS}" icon="fa fa-angle-double-right" adm:acl="*" adm:aclPageTypes="teca-mets,teca-metsimport,img_popup_mets,audio_popup_mets,video_popup_mets" select="*">
          <glz:Page pageType="metafad.teca.mets.views.Admin" id="teca-mets" value="{i18n:Mostra lista}" visible="true"/>
          <glz:Page pageType="metafad.teca.mets.views.AdminImport" id="teca-metsimport" value="{i18n:Importa}" visible="true" />
        </glz:Page>
        <glz:Page pageType="metafad.teca.mets.views.ImgPopup" id="img_popup_mets" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.mets.views.AudioPopup" id="audio_popup_mets" visible="true" parentId="" />
        <glz:Page pageType="metafad.teca.mets.views.VideoPopup" id="video_popup_mets" visible="true" parentId="" />';
        $moduleVO->canDuplicated = false;

		if(__Config::get('metafad.be.hasMets'))
		{
			org_glizy_Modules::addModule($moduleVO);
		}

    }
}
