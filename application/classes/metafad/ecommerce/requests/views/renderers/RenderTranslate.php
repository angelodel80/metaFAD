<?php
class metafad_ecommerce_requests_views_renderers_RenderTranslate extends org_glizycms_contents_views_renderer_DocumentTitle
{
    function renderCell( $key, $value, $docStore, $columnName)
    {
      return __T($value);
    }
}
