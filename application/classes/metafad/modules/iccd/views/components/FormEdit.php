<?php
class metafad_modules_iccd_views_components_FormEdit extends org_glizycms_views_components_FormEdit
{
    function render_html_onEnd($value='')
	{
	    parent::render_html_onEnd();
	    
        $this->_application->addLightboxJsCode();

        $childJs1 = org_glizy_ObjectFactory::createComponent('org.glizy.components.JSscript', $this->_application, $this, 'glz:JSscript', 'js1');
        $childJs1->setAttribute('folder', 'metafad/modules/iccd/views/js');
        $this->addChild($childJs1);

        $childJs2 = org_glizy_ObjectFactory::createComponent('org.glizy.components.JSscript', $this->_application, $this, 'glz:JSscript', 'js2');
        $childJs2->setAttribute('folder', 'metafad/teca/MAG/js');
        $this->addChild($childJs2);

	    $this->initChilds();
	    $childJs1->render();
	    $childJs2->render();
	}
}
