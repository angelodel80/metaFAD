<?php
class metafad_ecommerce_controllers_Edit extends metafad_common_controllers_Command
{
    public function execute($id, $templateID)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            __Request::set('model', $c->getAttribute('value'));
            $proxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ActiveRecordProxy');
            $data = $proxy->load($id, $c->getAttribute('value'));

			if($c->getAttribute('value') !== 'metafad.ecommerce.orders.models.Model'){
            	$this->checkPermissionAndInstitute('edit', $data['instituteKey']);
			}
			else {
				$this->checkPermissionForBackend('visible');
			}

            $data['license_stream'] = json_decode($data['license_stream']);

            $this->view->setData($data);
            $this->view->getComponentById('__id')->setAttribute('value',$id);
        }
    }
}
