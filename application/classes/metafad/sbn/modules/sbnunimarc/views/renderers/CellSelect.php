<?php
class metafad_sbn_modules_sbnunimarc_views_renderers_CellSelect extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    function renderCell($key, $value, $row)
    {
        $url = __Request::get('kardexParam');
        
        $value = str_replace('"', '##', json_encode($row->linkedStruMag));
        $fascicoloId = $row->annata.';'.$row->volume.';'.$row->numerazione;

        $attributes = array(
            'name' => 'linkedStruMag-'.$fascicoloId,
            'title' => 'Metadato strutturale collegato',
            'type' => 'text',
            'data-value' => $value,
            'data-type' => 'selectStruMagKardex',
            'data-proxy' => 'metafad.teca.STRUMAG.models.proxy.StruMagProxy',
            'data-proxy_params' => '{##url##:##'.$url.'##,##fascicolo##:##'.$fascicoloId.'##}',
            'data-selected_callback' => 'metafad.sbn.modules.sbnunimarc.controllers.ajax.AttachStruMag',
            'data-detach_callback' => 'metafad.sbn.modules.sbnunimarc.controllers.ajax.DetachStruMag',
            'data-return_object' => 'true'
        );

        $instituteProxy = __ObjectFactory::createObject('metafad.usersAndPermissions.institutes.models.proxy.InstitutesProxy');
        $vo = $instituteProxy->getInstituteVoByKey(metafad_usersAndPermissions_Common::getInstituteKey());
        $institutePrefix = $vo->institute_prefix;

        preg_match('/biblioteca=(..)/', $url, $m);
                
        if ($m[1] != $institutePrefix) {
            $attributes['disabled'] = 'disabled';
        }
        
        $output = org_glizy_helpers_Html::renderTag('input', $attributes, true);
        return $output;
    }
}
