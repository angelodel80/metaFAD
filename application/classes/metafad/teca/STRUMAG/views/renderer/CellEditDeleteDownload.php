<?php

class metafad_teca_STRUMAG_views_renderer_CellEditDeleteDownload extends org_glizycms_contents_views_renderer_AbstractCellEdit
{

    protected function renderDownloadButton($key, $row)
    {
        $output = '';
        if ($this->canView && $this->canEdit) {
            $output .= __Link::makeLinkWithIcon('actionsMVC',
                __Config::get('glizy.datagrid.action.downloadCssClass'),
                array(
                    'title' => 'Download',
                    'id' => $key,
                    'model' => $row->getClassName(false),
                    'action' => 'download'
                ));
        }

        return $output;
    }



    function renderCell($key, $value, $row)
    {
        $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
        //$this->loadAcl($key);
        $output = $this->renderEditButton($key, $row) .
                $this->renderDeleteButton($key, $row);
                //$this->renderDownloadButton($key, $row);
        return $output;
    }
}

