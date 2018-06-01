<?php
class archivi_controllers_ajax_GetFieldValueFromId extends metafad_common_controllers_ajax_CommandAjax
{

    /**
     * Tenta un accesso alla proprietà desiderata. L'array indica una cosa come "obj->ar[0]->ar[1]->...->ar[n]"
     * @param $obj
     * @param $properties
     * @param null $default
     * @return mixed|null
     */
    private function softAccess($obj, $properties, $default = null){
        $obj = json_decode(json_encode($obj));
        if (!is_array($properties) || !is_object($obj)){
            return null;
        }

        $i = -1;
        $n = count($properties);
        $got = $obj;
        while($got !== null && ++$i < $n){
            $propname = $properties[$i];
            if (!is_string($propname) || (!is_object($got) && !is_array($got))){
                $got = null;
            } else if (is_object($got) && property_exists($got, $propname)) {
                $got = $got->$propname;
            } else if (is_array($got) && count($got) && is_object($got[0]) && property_exists($got[0], $propname)) {
                //TODO uno solo?
                $got = $got[0]->$propname;
            } else {
                $got = null;
            }
        }

        return $got === null ? $default : $got;
    }

    public function execute($id, $model, $textfield)
    {
        $result = $this->checkPermissionForBackend('visible');
        if (is_array($result)) {
            return $result;
        }

        $ar = __ObjectFactory::createModel($model);
        $ar->load($id, 'PUBLISHED_DRAFT');

        $data = $ar ? $ar->getRawData() : new stdClass();

        $ret = array();
        $ret['result'] = $this->softAccess($data, explode("->", $textfield), null);
        return $ret;
    }
}