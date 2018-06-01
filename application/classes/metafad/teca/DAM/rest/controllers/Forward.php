<?php
set_time_limit (0);

class metafad_teca_DAM_rest_controllers_Forward extends org_glizy_rest_core_CommandRest
{
    function execute($instance)
    {
        $this->checkPermissionForBackend();

        $url = metafad_teca_DAM_Common::getDamBaseUrlLocalWithQueryString();
        $method = __Request::getMethod();
        $postBody = __Request::getBody();

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', $url, $method, $postBody, 'application/json');
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();

        $responseInfo = $request->getResponseInfo();

        $responseBody = str_replace('\\/', '/',  $request->getResponseBody());
        $responseBody = str_replace(metafad_teca_DAM_Common::getDamBaseUrlLocal(), metafad_teca_DAM_Common::getDamBaseUrl(), $responseBody);

        $resultDecoded = json_decode($responseBody);
        $result = array(
            'http-status' => $responseInfo['http_code'],
            $resultDecoded ? $resultDecoded : $responseBody
        );

        return $result;
    }
}
