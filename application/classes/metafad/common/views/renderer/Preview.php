<?php
class metafad_common_views_renderer_Preview extends org_glizycms_contents_views_renderer_DocumentTitle
{
    function renderCell( $key, $value, $row )
    {
      if($value)
      {
        $viewerHelper = org_glizy_objectFactory::createObject('metafad.viewer.helpers.ViewerHelper');
        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        $dam = $viewerHelper->initializeDam($viewerHelper->getKey($instituteKey));
        return '<img src="'.metafad_teca_DAM_Common::replaceUrl($dam->streamUrl($value, 'thumbnail')).'" />';
      }
      else
      {
        return 'N.D.';
      }
    }
}
