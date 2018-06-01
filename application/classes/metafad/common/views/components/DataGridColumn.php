<?php
class metafad_common_views_components_DataGridColumn extends org_glizy_components_DataGridColumn
{
    /**
     * Init
     *
     * @return    void
     * @access    public
     */
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('checkbox',  false, false, COMPONENT_TYPE_STRING);

        // call the superclass for validate the attributes
        parent::init();
    }

    function getProperties()
    {
        $properties = parent::getProperties();
        $properties['checkbox'] = $this->getAttribute('checkbox');
        return $properties;
    }
}