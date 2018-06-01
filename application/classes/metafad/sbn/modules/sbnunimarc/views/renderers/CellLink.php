<?php

class metafad_sbn_modules_sbnunimarc_views_renderers_CellLink extends org_glizycms_contents_views_renderer_AbstractCellEdit
{

    function renderCell($key, $value, $row)
    {
        $output = '<a href="' . __Link::makeURL( 'actionsMVC',
                array(
                    'title' => __T('GLZ_RECORD_EDIT'),
                    'action' => 'show','id' => $row->id)) . '" target="_blank">' . $row->id . '</a>';
        return $output;
    }
}
