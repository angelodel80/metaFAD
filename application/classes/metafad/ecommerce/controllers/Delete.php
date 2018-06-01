<?php
class metafad_ecommerce_controllers_Delete extends metafad_common_controllers_Command
{
    public function execute($id, $model)
    {
        if ($id) {
            $proxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ActiveRecordProxy');
            $data = $proxy->load($id, $model);

            $this->checkPermissionAndInstitute('delete', $data['instituteKey']);

            $proxy->delete($id, $model);

            org_glizy_helpers_Navigation::goHere();
        }
    }
}
