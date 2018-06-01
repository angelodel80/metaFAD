<?php
class metafad_usersAndPermissions_institutes_views_components_Institute extends org_glizy_components_Component
{
    function render()
    {
        $instituteName = metafad_usersAndPermissions_Common::getInstituteName();
        
        $output .= <<<EOD
<label>Istituto: $instituteName</label>
EOD;
        $this->addOutputCode($output);
    }
}