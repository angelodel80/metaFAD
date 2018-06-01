<?php
class metafad_ecommerce_requests_controllers_Edit extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        if ($id) {
            $c = $this->view->getComponentById('__model');
            $data = org_glizy_objectFactory::createModelIterator($c->getAttribute('value'))
                            ->where('request_id',$id)->first();

            $this->checkPermissionAndInstitute('edit', $data->instituteKey);

            $c2 = $this->view->getComponentById('requestObjects');
            $c2->setAttribute('record_id',$data->request_object_id);

            $userId = $data->request_FK_user_id;
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $user = $contentProxy->loadContent($userId, 'metafad.ecommerce.requests.models.FEUsers');

            $this->view->getComponentById('userEmail')->setAttribute('value',$user['user_email']);
            $this->view->getComponentById('userPhone')->setAttribute('value',$user['user_phone']);

            $this->view->getComponentById('__id')->setAttribute('value',$id);
        }
    }
}
