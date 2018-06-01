<?php
class metafad_opac_controllers_Edit extends org_glizycms_contents_controllers_moduleEdit_Edit
{
    public function execute($id, $templateID)
    {
        $linkedFields = $this->view->getComponentById('linkedFields');
        if ($id) {
            $c = $this->view->getComponentById('__model');
            __Request::set('model', $c->getAttribute('value'));
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $data = $contentProxy->loadContent($id, $c->getAttribute('value'));
            $data['__id'] = $id;
            $linkedFields->setAttribute('data','type=selectfrom;multiple=false;add_new_values=false;proxy=metafad.opac.models.proxy.OpacFieldProxy;return_object=false;proxy_params={##section##:##'.$data['section'].'##,##archive##:##'.$data['archiveType'].'##}');
            $this->view->setData($data);
        }
        else
        {
          $linkedFields->setAttribute('data','type=selectfrom;multiple=false;add_new_values=false;proxy=metafad.opac.models.proxy.OpacFieldProxy;return_object=false;proxy_params={##section##:##bibliografico##}');
        }
    }
}
