<?php
class metafad_ecommerce_controllers_ajax_Save extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data, $draft=false)
    {
        $result = $this->checkPermissionAndInstitute('publish', $data);
        if (is_array($result)) {
            return $result;
        }

        $this->directOutput = true;
        $decodeData = json_decode($data);
        $ar = org_glizy_objectFactory::createModel($decodeData->__model);
        if ($decodeData->__id) {
            $ar->load($decodeData->__id);
        }

        foreach(json_decode($data) as $key => $value) {
            if ($key == '__id' || $key == '__model') {
                continue;
            }
            if (is_array($value)) {
                $ar->$key = json_encode($value);
            } else {
                $ar->$key = $value;
            }
        }

        $ar->instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();

        $result = array();

        $result['set']['__id']= $ar->save();

        return $result;
    }

    protected function createModel($id = null, $model)
    {
        $document = org_glizy_objectFactory::createModel($model);
        if ($id) {
            $document->load($id);
        }
        return $document;
    }

}
