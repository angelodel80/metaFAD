<?php
class metafad_common_views_renderer_CellEditDraftDeleteNormalize extends metafad_common_views_renderer_AbstractCellEdit
{
    protected function renderNormalizeButton($key, $row)
	{
        $key = explode('-', $key);
        $key = end($key);
        
        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon(
                'actionsMVCNormalize',
                __Config::get('glizy.datagrid.action.normalizeCssClass'),
                array(
                    'title' => __T('Trasforma in versione completa'),
                    'id' => $key,
                    'model' => $row->className,
                    'action' => 'normalize' 
                ),
                __T('Sicuro di voler trasformare la scheda dalla versione semplificata alla completa?')
            );
        }

		return $output;
	}

    function renderCell($key, $value, $row)
    {
        parent::renderCell($key, $value, $row);

        $draft = ($row->hasPublishedVersion) ? $row->hasDraftVersion : true;

        $output = $this->renderEditButton($key, $row, $row->hasPublishedVersion).
                $this->renderEditDraftButton($key, $row, $draft).
                $this->renderDeleteButton($key, $row).
                $this->renderNormalizeButton($key, $row) ;

        return $output;
    }
}
