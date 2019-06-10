<?php
class metafad_common_controllers_RedirectFromExport extends metafad_common_controllers_Command
{
    public function execute()
    {
        if(__Request::get('action') != 'export')
        {
            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            org_glizy_helpers_Navigation::gotoUrl(str_replace('_export', '', $url));
        }
    }
}