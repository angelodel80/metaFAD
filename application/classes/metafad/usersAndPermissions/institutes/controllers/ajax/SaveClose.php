<?php
class metafad_usersAndPermissions_institutes_controllers_ajax_SaveClose extends metafad_usersAndPermissions_institutes_controllers_ajax_Save
{
    public function execute($data)
    {
        $result = parent::execute($data);

        if ($result['errors']) {
            return $result;
        }

        return array('url' => $this->changeAction(''));
    }
}