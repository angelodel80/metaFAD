<?php
class metafad_teca_MAG_views_renderer_DocumentTitle extends GlizyObject
{
    function renderCell( $key, $value, $row )
    {
      return $value[0]->BIB_dc_title_value;
    }
}
