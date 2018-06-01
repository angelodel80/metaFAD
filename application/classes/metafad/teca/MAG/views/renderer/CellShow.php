<?php
class metafad_teca_MAG_views_renderer_CellShow extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    protected function renderShowButton($key, $row, $enabled = true)
    {
        $output = '';
        if ($this->canView && $this->canEdit) {
            $output = __Link::makeLinkWithIcon(
                'actionsMVC',
                __Config::get('glizy.datagrid.action.showOnlyCssClass').($enabled ? '' : ' disabled'),
                array(
                    'pageId' => 'tecamag',
                    'title' => 'Visualizza',
                    'id' => $key,
                    'action' => 'edit',
                    'cssClass' => ($enabled ? '' : ' disabled-button')
                )
            );
        }

        return $output;
    }

    function renderCell($key, $value, $row, $columnName)
    {
        parent::renderCell($key, $value, $row, $columnName);

        $output = $this->renderShowButton($key, $row, true);

        return $output;
    }
}
