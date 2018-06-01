<?php

class metafad_usersAndPermissions_users_views_renderer_CellEditDeleteDetail extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    protected function renderDetailButton($key, $row)
    {
        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon('actionsMVC',
                __Config::get('glizy.datagrid.action.detailCssClass'),
                array(
                    'title' => 'Dettaglio',
                    'id' => $key,
                    'action' => 'detail'
                ));
        }
        return $output;
    }

    function renderCell($key, $value, $row)
    {
        $this->loadAcl($key);
        $output = $this->renderEditButton($key, $row) .
            $this->renderDeleteButton($key, $row) .
            $this->renderDetailButton($key, $row);
        return $output;
    }
}

