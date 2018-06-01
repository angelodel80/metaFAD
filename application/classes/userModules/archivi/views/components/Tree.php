<?php
/**
 * This file is part of the GLIZY framework.
 * Copyright (c) 2005-2011 Daniele Ugoletti <daniele.ugoletti@glizy.com>
 *
 * For the full copyright and license information, please view the COPYRIGHT.txt
 * file that was distributed with this source code.
 */

class archivi_views_components_Tree  extends org_glizy_components_Component
{
    /**
     * Init
     *
     * @return  void
     * @access  public
     */
    public function init()
    {
        $this->defineAttribute('title', false, '{i18n:glizycms.Site Structure}',    COMPONENT_TYPE_STRING);
        $this->defineAttribute('startId', false, 0,    COMPONENT_TYPE_INTEGER);
        $this->defineAttribute('path', false, '',    COMPONENT_TYPE_STRING);
        $this->defineAttribute('selectId', false, 0,    COMPONENT_TYPE_INTEGER);

        // call the superclass for validate the attributes
        parent::init();
    }


    public function process() {
        $this->_content =  new archivi_views_components_TreeVO();
        $this->_content->title = $this->getAttribute('title');
        $this->_content->ajaxUrl = $this->getAjaxUrl().'&controllerName=archivi.controllers.ajax.GetTree&';
        $this->_content->startId = $this->getAttribute('startId');
        $this->_content->path = $this->getAttribute('path');
        $this->_content->selectId = $this->getAttribute('selectId');
    }

    public function render($outputMode=NULL, $skipChilds=false) {
        parent::render($outputMode, $skipChilds);
        if (!org_glizy_ObjectValues::get('org.glizycms.js', 'jsTree', false))
        {
            org_glizy_ObjectValues::set('org.glizycms.js', 'jsTree', true);
            $this->addOutputCode( org_glizy_helpers_CSS::linkStaticCSSfile( 'jquery/fancytree/dist/skin-bootstrap/ui.fancytree.min.css' ) );
            $this->addOutputCode( org_glizy_helpers_JS::linkStaticJSfile( 'jquery/fancytree/dist/jquery.fancytree-all.js' ) );
        }
    }
}

class archivi_views_components_TreeVO
{
    public $title;
    public $ajaxUrl;
    public $startId;
}

class archivi_views_components_Tree_render extends org_glizy_components_render_Render
{
    function getDefaultSkin()
    {
        $skin = <<<EOD
<div id="treeview">
    <div id="treeview-title" tal:condition="Component/title">
        <h3 tal:content="Component/title"></h3>
    </div>
    <div id="treeview-inner">
        <div id="js-glizycmsSiteTree" tal:attributes="data-ajaxurl Component/ajaxUrl; data-start Component/startId; data-path Component/path; data-selectid Component/selectId"></div>
    </div>
</div>
EOD;
        return $skin;
    }
}
