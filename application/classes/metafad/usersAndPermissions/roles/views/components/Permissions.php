<?php
class metafad_usersAndPermissions_roles_views_components_Permissions extends org_glizy_components_Input
{
    protected static $ACTIONS = array('all', 'edit', 'editDraft', 'new', 'delete', 'publish', 'visible');

    function process()
    {
        parent::process();
        if (is_string($this->_content)) {
            $this->_content = unserialize($this->_content);
            if (!$this->_content) $this->_content = array();
        }
    }

    function render()
    {
        $output = <<<EOD
        <div class="table-container">
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th></th>
            <th>Consenti tutto</th>
            <th>Modifica</th>
            <th>Modifica bozza</th>
            <th>Nuovo</th>
            <th>Cancellazione</th>
            <th>Pubblicazione</th>
            <th>Visualizzazione</th>
        </tr>
    </thead>
    <tbody>
EOD;
        $cssClass = '';
        $perms = array('a', 'm', 'b', 'n', 'c', 'p', 'v');
        $row = 0;

        $permissionService = org_glizy_ObjectFactory::createObject(__Config::get('PERMISSION_CLASS'), __Config::get('PERMISSION_PARAMS'));

        foreach ($permissionService as $permission) {
            $cssClass = $cssClass == 'odd' ? 'even' : 'odd';
            $output .= '<tr class="'.$cssClass.'"><td>'.$permission->label.'</td>';
            $acl = $permission->acl;

            if ($acl == '*') {
                $v = '1111111';
            } else {
                $v = '';
                $acl = array_flip(explode(',', $acl));
                foreach ($perms as $p) {
                    $v .= isset($acl[$p]) ? '1' : '0';
                }
            }

            $id = $permission->id;
            $output .= $this->drawCheckox($id, $v, $row, 0);
            $output .= $this->drawCheckox($id, $v, $row, 1);
            $output .= $this->drawCheckox($id, $v, $row, 2);
            $output .= $this->drawCheckox($id, $v, $row, 3);
            $output .= $this->drawCheckox($id, $v, $row, 4);
            $output .= $this->drawCheckox($id, $v, $row, 5);
            $output .= $this->drawCheckox($id, $v, $row, 6);

            if ($permission->aclPageTypes) {
                $output .= '<input type="hidden" name="aclPageTypes['.$id.']" value="'.$permission->aclPageTypes.'" />';
            }

            $output .= '</tr>';
            $row++;
        }

        $output .= '</tbody></table></div>';
        $this->addOutputCode($output);
    }

    private function drawCheckox($id, $flags, $row, $pos)
    {
        $checked = @$this->_content[$id][self::$ACTIONS[$pos]] ? 'checked="checked"' : ''; 
        $id = 'permissions['.$id.']['.self::$ACTIONS[$pos].']';
        if ($flags{$pos}=='1') {
            $output = '<td style="text-align: center">';
            $output .= '<input type="checkbox" id="'.$id.'" name="'.$id.'" value="1" '.$checked.' data-type="checkbox"/>';
            $output .= '</td>';
        } else {
            $output = '<td></td>';
        }

        return $output;
    }
}