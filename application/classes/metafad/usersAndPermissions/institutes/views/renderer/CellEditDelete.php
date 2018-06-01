<?php
class metafad_usersAndPermissions_institutes_views_renderer_CellEditDelete extends org_glizycms_contents_views_renderer_CellEditDelete
{
    function renderCell($key, $value, $row)
    {
        if ($row->institute_key != '*') {
            $this->loadAcl($key);
            $output = $this->renderEditButton($key, $row).
                      $this->renderDeleteButton($key, $row);
            return $output;
        }
    }
}