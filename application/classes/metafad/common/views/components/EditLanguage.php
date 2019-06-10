<?php
class metafad_common_views_components_EditLanguage extends org_glizycms_views_components_EditLanguage
{
    function init()
    {
        parent::init();
    }

    function process()
    {
        parent::process();
    }

    function checkSwicth()
    {
        if (!is_null(org_glizy_Request::get('switchLanguage'))) {
            $language = org_glizy_Request::get('switchLanguage');            
            $this->_application->switchEditingLanguage($language);
        }
    }
}

class metafad_common_views_components_EditLanguage_render extends org_glizy_components_render_Render
{
    function getDefaultSkin()
    {
        $skin = <<<EOD
<div tal:attributes="id id; class Component/cssClass" tal:condition="Component/records">
	<span class="label" tal:content="Component/label" />
    <div class="btn-group">
        <a tal:attributes="data-target php: '#' . id . 'menu'" data-toggle="dropdown" class="btn dropdown-toggle action-link">
            <i class="fa fa-chevron-down"></i> 
            <span tal:omit-tag="" tal:content="Component/current" />
        </a>
        <div tal:attributes="id php: id . 'menu'">
            <ul class="dropdown-menu right">
            	<li tal:repeat="item Component/records" tal:content="structure item" />
            </ul>
        </div>
    </div>
</div>
EOD;
        return $skin;
    }
}