<?php
class archivi_views_components_PageTitle extends org_glizy_components_PageTitle
{
    function process()
    {
        $this->_content = $this->getAttribute('value');
    }
}