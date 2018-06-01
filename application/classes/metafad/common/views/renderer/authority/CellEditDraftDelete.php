<?php
class metafad_common_views_renderer_authority_CellEditDraftDelete extends metafad_common_views_renderer_CellEditDraftDelete
{
    function renderCell($key, $value, $row, $columnName, $doc)
    {
        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        if ($doc->instituteKey_s == $instituteKey || $instituteKey == '*') {
            return parent::renderCell($key, $value, $row, $columnName, $doc);
        } else {
            $action = $row->hasPublishedVersion ? 'show' : 'showDraft';
            return __Link::makeLinkWithIcon(
                'actionsMVC',
                'btn btn-success btn-flat fa fa-eye',
                array(
                    'title' => __T('GLZ_RECORD_EDIT'),
                    'action' => $action, 'id' => $doc->id),
                    NULL
                );
        }
    }
}
