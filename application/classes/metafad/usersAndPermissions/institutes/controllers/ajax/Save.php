<?php
class metafad_usersAndPermissions_institutes_controllers_ajax_Save extends org_glizycms_contents_controllers_activeRecordEdit_ajax_Save
{
    public function execute($data)
    {
        $data = json_decode($data);
        $data->institute_key = metafad_usersAndPermissions_Common::getInstituteKeyByName($data->institute_name);
        return parent::execute($data);
    }
}