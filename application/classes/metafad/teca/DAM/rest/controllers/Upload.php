<?php
set_time_limit (0);

class metafad_teca_DAM_rest_controllers_Upload extends org_glizy_rest_core_CommandRest
{
    function execute($instance)
    {
        $this->checkPermissionForBackend();

        $uploadsDir = __Config::get('gruppometa.dam.upload.folder');
        if ($instance && isset($_FILES["file"])) {
            $unique = uniqid();
            @mkdir($uploadsDir);
            $moveFileResult = move_uploaded_file($_FILES['file']['tmp_name'], $uploadsDir . $unique . '_' . $_FILES["file"]['name']);
            if (!$moveFileResult || $moveFileResult == false) {
                return array('http-status' => 400);
            }
            return $unique . '_' . $_FILES["file"]['name'];
        }
        return array('http-status' => 400, 'message' => 'file not uploaded');
    }
}