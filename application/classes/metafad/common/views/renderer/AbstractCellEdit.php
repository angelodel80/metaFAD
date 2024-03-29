<?php
abstract class metafad_common_views_renderer_AbstractCellEdit extends org_glizy_components_render_RenderCell
{
    protected $canView = true;
    protected $canEdit = true;
    protected $canEditDraft = true;
    protected $canDelete = true;

    protected function loadAcl($key)
    {
        // TODO: posstare questa parte di codice in un classe comune
        // e gestire in modo simile quando sono attivi i ruoli e quando no
        $key = explode('-', $key);
        $key = end($key);
        
        $pageId = $this->application->getPageId();
        if (__Config::get('ACL_ROLES')) {
            if (!$this->user->acl($pageId, 'all')) {
                $this->canView = $this->user->acl($pageId, 'visible');
                $this->canEdit = $this->user->acl($pageId, 'edit');
                $this->canEditDraft = $this->user->acl($pageId, 'editDraft');
                $this->canDelete = $this->user->acl($pageId, 'delete');

                if ($this->canView) {
                    $ar = org_glizy_objectFactory::createModel('org.glizycms.contents.models.DocumentACL');
                    $ar->load($key);

                    if ($ar->__aclEdit) {
                        $roles = explode(',', $ar->__aclEdit);
                        $this->canEdit = $this->canDelete = $this->user->isInRoles($roles);
                    }
                }
            }
        } else {
            $this->canView = $this->user->acl($pageId, 'visible');
            $this->canEdit = $this->user->acl($pageId, 'edit');
            $this->canEditDraft = $this->user->acl($pageId, 'editDraft');
            $this->canDelete = $this->user->acl($pageId, 'delete');
        }
    }

    protected function renderEditButton($key, $row, $enabled = true)
    {
        $key = explode('-',$key);
        $key = end($key);

        $output = '';
        if ($this->canView && $this->canEdit) {
            $output = __Link::makeLinkWithIcon(
                'actionsMVC',
                __Config::get('glizy.datagrid.action.editCssClass').($enabled ? '' : ' disabled'),
                array(
                    'title' => __T('GLZ_RECORD_EDIT'),
                    'id' => $key,
                    'action' => 'edit',
                    'cssClass' => ($enabled ? '' : ' disabled-button')
                )
            );
        }

        return $output;
    }

    protected function renderPreviewButton($key, $row, $enabled = true)
    {
        $key = explode('-', $key);
        $key = end($key);

        $output = '';
        if ($this->canView && $this->canEdit) {
            $output = '<input id="'.$key.'" name="'.$key.'" class="btn btn-flat js-glizycms-preview" type="button" value="Anteprima" data-action="preview">';
        }

        return $output;
    }

    protected function renderEditDraftButton($key, $row, $enabled = true)
    {
        $key = explode('-', $key);
        $key = end($key);

        $output = '';
        if ($this->canView && $this->canEditDraft) {
            $output = __Link::makeLinkWithIcon(
                'actionsMVC',
                __Config::get('glizy.datagrid.action.editDraftCssClass').($enabled ? '' : ' disabled'),
                array(
                    'title' => __T('GLZ_RECORD_EDIT_DRAFT'),
                    'id' => $key,
                    'action' => 'editDraft',
                    'cssClass' => ($enabled ? '' : ' disabled-button')
                )
            );
        }

        return $output;
    }

    protected function renderDeleteButton($key, $row)
	{
        $key = explode('-', $key);
        $key = end($key);

        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon(
                'actionsMVCDelete',
                __Config::get('glizy.datagrid.action.deleteCssClass'),
                array(
                    'title' => __T('GLZ_RECORD_DELETE'),
                    'id' => $key,
                    'model' => $row->className,
                    'action' => 'delete'
                ),
                __T('GLZ_RECORD_MSG_DELETE')
            );
        }

		return $output;
	}

    protected function renderDeleteSimpleButton($key, $row,$model)
    {
        $key = explode('-', $key);
        $key = end($key);

        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= __Link::makeLinkWithIcon(
                'actionsMVCDelete',
                __Config::get('glizy.datagrid.action.deleteCssClass'),
                array(
                  'title' => __T('GLZ_RECORD_DELETE'),
                  'id' => $key,
                  'model' => $model,
                  'action' => 'delete'
                ),
                __T('GLZ_RECORD_MSG_DELETE')
            );
        }

        return $output;
    }

    protected function renderVisibilityButton($key, $row)
    {
        $key = explode('-', $key);
        $key = end($key);
        
        $output = '';
        if ($this->canView && $this->canEdit) {
            $output .= __Link::makeLinkWithIcon(
                'actionsMVCToggleVisibility',
                __Config::get($row->isVisible() ? 'glizy.datagrid.action.showCssClass' : 'glizy.datagrid.action.hideCssClass'),
                array(
                    'title' => $row->isVisible() ? __T('Hide') : __T('Show'),
                    'id' => $key,
                    'model' => $row->className,
                    'action' => 'togglevisibility'
                )
            );
        }

        return $output;
    }

    protected function renderCheckBox($key, $row)
	{
        $key = explode('-', $key);
        $key = end($key);

        $output = '';
        if ($this->canView && $this->canDelete) {
            $output .= '<input name="check[]" data-id="'.$row->getId().'" type="checkbox">';
        }

		return $output;
	}

	public function renderCell($key, $value, $row)
    {
        $this->loadAcl($key);
    }
}
