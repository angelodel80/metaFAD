<?php

class metafad_workflow_processes_views_renderer_CellStartEditDeleteVisibleCopyCopyWithoutValue extends org_glizycms_contents_views_renderer_AbstractCellEdit
{
    protected function renderStartButton($key, $row)
    {
        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon('actionsMVC',
                __Config::get('glizy.datagrid.action.startCssClass'),
                array(
                    'title' => 'Avvia processo',
                    'id' => $key,
                    'action' => 'togglestart'
                ));
        }
        return $output;
    }

    protected function renderCopyButton($key, $row)
    {
        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon('actionsMVC',
                __Config::get('glizy.datagrid.action.copyCssClass'),
                array(
                    'title' => 'Copia',
                    'id' => $key,
                    'action' => 'togglecopy'
                ));
        }
        return $output;
    }

    protected function renderCopyWithoutValueButton($key, $row)
    {
        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon('actionsMVC',
                __Config::get('glizy.datagrid.action.copyWithoutValueCssClass'),
                array(
                    'title' => 'Crea istanza',
                    'id' => $key,
                    'action' => 'togglecopywithoutvalue'
                ));
        }
        return $output;
    }

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
        $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
        //$this->loadAcl($key);
        if ($document->load($key) && !$document->getRawData()->status) {
            $output = $this->renderStartButton($key, $row) .
                $this->renderEditButton($key, $row) .
                $this->renderDeleteButton($key, $row) .
                $this->renderCopyWithoutValueButton($key, $row) .
                $this->renderCopyButton($key, $row) .
                $this->renderDetailButton($key, $row);
        } else {
            $output = $this->renderVisibilityButton($key, $row) .
                $this->renderCopyWithoutValueButton($key, $row) .
                $this->renderCopyButton($key, $row) .
                $this->renderDetailButton($key, $row);
        }
        return $output;
    }
}

