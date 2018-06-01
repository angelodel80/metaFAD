<?php
class archivi_controllers_History extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('visible');
    }
}