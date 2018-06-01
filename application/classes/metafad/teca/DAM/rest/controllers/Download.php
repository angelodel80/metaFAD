<?php
set_time_limit (0);

class metafad_teca_DAM_rest_controllers_Download extends org_glizy_rest_core_CommandRest
{
    function execute($instance)
    {
        if (__Request::get('bytestreamName') == 'original') {
            $this->checkPermissionForBackend();
        }


        $url = metafad_teca_DAM_Common::getDamBaseUrlLocalWithQueryString();

        if (!__Config::get('metafad.dam.hasDownload')) {
            $url = metafad_teca_DAM_Common::getDamBaseUrlLocal().__Request::get('instance').'/resize/'.__Request::get('mediaId').'/original?h=*&w='.__Config::get('gruppometa.dam.maxResizeWidth');
        }

        $method = __Request::getMethod();
        $postBody = __Request::getBody();

        $request = org_glizy_objectFactory::createObject('org.glizy.rest.core.RestRequest', $url, $method, $postBody, 'application/json');
        $request->setTimeout(1000);
        $request->setAcceptType('application/json');
        $request->execute();

        while (ob_get_level()) {
            ob_end_clean();
        }

        foreach ($request->getResponseHeaders() as $header) {
            if (strpos($header, 'Set-Cookie')!==false) continue;
            header($header);
        }

        echo $request->getResponseBody();
        exit;
    }
}
