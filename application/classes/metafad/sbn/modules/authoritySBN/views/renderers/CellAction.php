<?php
class metafad_sbn_modules_authoritySBN_views_renderers_CellAction extends org_glizycms_contents_views_renderer_AbstractCellEdit
{

    function renderCell($key, $value, $row)
    {
        $this->loadAcl($value);
        $output = $this->renderEditButton($value, $row);
        return $output;
    }


    protected function renderEditButton($key, $row)
    {
        $output = '<div class="chViewerButtons">';
        if ($this->canView && $this->canEdit) {
            $output .= __Link::makeLinkWithIcon( 'actionsMVC',
                                                            'btn btn-success btn-flat fa fa-eye',
                                                            array(
                                                                'title' => __T('GLZ_RECORD_EDIT'),
                                                                'action' => 'show','id' => $row->id),NULL);
        }
        $output .= '</div>';

        return $output;
    }


}
