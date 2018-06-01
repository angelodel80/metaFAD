<?php
class metafad_common_views_components_FormEdit extends org_glizycms_views_components_FormEdit
{
    public function render_html_onEnd($value='')
	{
	    parent::render_html_onEnd();

        if ($this->getAttribute('newCode')) {
            $this->_application->addLightboxJsCode();

            $childJs1 = org_glizy_ObjectFactory::createComponent('org.glizy.components.JSscript', $this->_application, $this, 'glz:JSscript', 'js1');
            $childJs1->setAttribute('folder', 'metafad/modules/iccd/views/js');
            $this->addChild($childJs1);

            $childJs2 = org_glizy_ObjectFactory::createComponent('org.glizy.components.JSscript', $this->_application, $this, 'glz:JSscript', 'js2');
            $childJs2->setAttribute('folder', 'metafad/teca/MAG/js');
            $this->addChild($childJs2);

            $childJs3 = org_glizy_ObjectFactory::createComponent('org.glizy.components.JSscript', $this->_application, $this, 'glz:JSscript', 'js3');
            $childJs3->setAttribute('folder', 'metafad/common/views/js');
            $this->addChild($childJs3);

    	    $this->initChilds();
    	    $childJs1->render();
    	    $childJs2->render();
    	    $childJs3->render();
        }
	}
}
