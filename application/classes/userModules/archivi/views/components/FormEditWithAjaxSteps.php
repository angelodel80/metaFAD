<?php
class archivi_views_components_FormEditWithAjaxSteps extends org_glizycms_views_components_FormEdit
{
	public function render_html_onStart()
	{
        $this->addOutputCode(org_glizy_helpers_JS::linkCoreJSfile('progressBar/progressBar.js'));
        $this->addOutputCode(org_glizy_helpers_CSS::linkCoreCSSfile2('progressBar/progressBar.css'), 'head');

        $childJs1 = org_glizy_ObjectFactory::createComponent('org.glizy.components.JSscript', $this->_application, $this, 'glz:JSscript', 'js1');
        $childJs1->setAttribute('folder', 'archivi/js/FormWithAjaxSteps');
        $this->addChild($childJs1);

	    $this->initChilds();
        $childJs1->render();
        
        parent::render_html_onStart();

        $ajaxUrl = $this->getAttribute('controllerName') ? $this->getAjaxUrl() : '';

        $output = <<<EOD
<div id="progress_bar" class="js-glizycms-FormEditWithAjaxSteps ui-progress-bar ui-container" data-ajaxurl="$ajaxUrl">
  <div class="ui-progress" style="width: 0%;">
    <span class="ui-label" style="display:none;"><b class="value">0%</b></span>
  </div>
</div>
EOD;
        $this->addOutputCode($output);
	}
}
