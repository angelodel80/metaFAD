<?php
class archivi_views_renderer_CellSelectEditDraftDelete extends metafad_common_views_renderer_CellEditDraftDelete
{
    private $classes = array(
        'archivi.models.ComplessoArchivistico',
        'archivi.models.UnitaArchivistica',
        'archivi.models.UnitaDocumentaria'
    );

    private function getPageTypeFromModelName($modelname){
        return strtolower("archivi-".end(explode(".", $modelname)));
    }

    private function beautifySectionType($string){
        switch ($string){
            case "sub-fondo":
                return "Sub-fondo";
                break;
            case "collezione-raccolta":
                return "Collezione/Raccolta";
                break;
            default:
                return ucfirst(str_replace("-", " ", $string));
        }
    }

    protected function renderSelectButton($key, $row)
    {
        $it2 = org_glizy_ObjectFactory::createModelIterator('archivi.models.Model')
            ->load('getParent', array(':parent' => $key, ':languageId' => org_glizy_ObjectValues::get('org.glizy', 'languageId')));
        $isLeaf = $it2->count() == 0;

        $output = '';
        if ($this->canView && in_array($row->document_type_t[0], $this->classes) && !$isLeaf) {
            $output .= __Link::makeLinkWithIcon('archiviMVC',
                __Config::get('glizy.datagrid.action.detailCssClass'),
                array(
                    'title' => "Seleziona ". $this->beautifySectionType($row->livelloDiDescrizione),
                    'id' => $key,
                    'action' => 'listDetail',
                    'pageId' => $this->getPageTypeFromModelName($row->document_type_t[0]),
                ));
        }
        return $output;
    }

    protected function renderEditDraftButton($key, $row, $enabled = true)
    {
        $output = '';
        if ($this->canView && $this->canEditDraft) {
            $output = __Link::makeLinkWithIcon(
                'archiviMVC',
                __Config::get('glizy.datagrid.action.editDraftCssClass').($enabled ? '' : ' disabled'),
                array(
                    'title' => __T('GLZ_RECORD_EDIT_DRAFT'),
                    'id' => $key,
                    'action' => 'editDraft',
                    'sectionType' => $row->livelloDiDescrizione_s,
                    'pageId' => $this->getPageTypeFromModelName($row->document_type_t[0]),
                    'cssClass' => ($enabled ? '' : ' disabled-button')
                )
            );
        }

        return $output;
    }

    function renderEditButton($key, $row, $enabled){
        $output = '';
        if ($this->canView && $this->canEdit) {
            $output = __Link::makeLinkWithIcon(
                'actionsMVC',
                __Config::get('glizy.datagrid.action.editCssClass').($enabled ? '' : ' disabled'),
                array(
                    'title' => __T('GLZ_RECORD_EDIT'),
                    'id' => $key,
                    'action' => 'edit',
                    'sectionType' => $row->livelloDiDescrizione_s,
                    'pageId' => $this->getPageTypeFromModelName($row->document_type_t[0]),
                    'cssClass' => ($enabled ? '' : ' disabled-button')
                )
            );
        }

        return $output;
    }

    function renderDeleteButton($key, $row) {
        $output = '';
        if ($this->canView && $this->canDelete) {
            $params = array(
                'title' => __T('GLZ_RECORD_DELETE'),
                'id' => $key,
                'model' => $row->document_type_t[0],
                'pageId' => $this->getPageTypeFromModelName($row->document_type_t[0]),
                'action' => 'delete'
            );

            $addParam = array();

            if (__Request::get('action') == 'listDetail') {
                $addParam = array('listDetail' => __Request::get('parent'));
            }

            $output .= __Link::makeLinkWithIcon(
                'actionsMVCDelete',
                __Config::get('glizy.datagrid.action.deleteCssClass'),
                $params,
                __T('GLZ_RECORD_MSG_DELETE'),
                $addParam
            );
        }
        
        return $output;
    }

    function renderVisibilityButton($key, $row, $visibility, $helper){
        $output = '';
        if ($this->canView && in_array($row->document_type_t[0], $this->classes)) {
            $vis = !$helper->isVisible($visibility?:"") ? "hide" : "show";
            $actionVis = $vis === "hide" ? "show" : "hide";
            $output .= __Link::makeLinkWithIcon(
                'archiviMVCToggleFEVisibility',
                __Config::get("glizy.datagrid.action.{$vis}CssClass"),
                array(
                    'title' => __T($actionVis.'Visibility'),
                    'id' => $key,
                    'model' => $row->document_type_t[0],
                    'pageId' => $this->getPageTypeFromModelName($row->document_type_t[0]),
                    'action' => 'togglefevisibility'
                ),
                __T('ToggleVisibility_Dialog')
            );
        }

        return $output;
    }

    function renderCell($key, $value, $row, $columnName, $doc)
    {
        $visHelper = __ObjectFactory::createObject('metafad_common_helpers_VisibilityHelper');

        $this->loadAcl($key);

        $output = $this->renderSelectButton($key, $doc);

        $output .= $this->renderEditButton($key, $doc, $row->hasPublishedVersion).
            $this->renderEditDraftButton($key, $doc, $row->hasDraftVersion).
            $this->renderDeleteButton($key, $doc).
            $this->renderVisibilityButton($key, $doc, $doc->visibility_s, $visHelper);

        return $output;
    }
}


