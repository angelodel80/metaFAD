<?php

/**
 * This file is part of the GLIZY framework.
 * Copyright (c) 2005-2012 Daniele Ugoletti <daniele.ugoletti@glizy.com>
 *
 * For the full copyright and license information, please view the COPYRIGHT.txt
 * file that was distributed with this source code.
 */
class metafad_sbn_modules_sbnunimarc_views_components_Input extends org_glizy_components_HtmlFormElement
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
        $this->defineAttribute('defaultValue', false, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('cssClass', false, NULL, COMPONENT_TYPE_STRING);
        $this->defineAttribute('cssClassLabel', false, __Config::get('glizy.formElement.cssClassLabel'), COMPONENT_TYPE_STRING);
        $this->defineAttribute('disabled', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('label', false, NULL, COMPONENT_TYPE_STRING);
        $this->defineAttribute('readOnly', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('value', false, NULL, COMPONENT_TYPE_STRING);
        $this->defineAttribute('required', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('title', false, '', COMPONENT_TYPE_STRING);

        // call the superclass for validate the attributes
        parent::init();
    }

    /**
     * Process
     *
     * @return    boolean    false if the process is aborted
     * @access    public
     */
    function process()
    {
        $this->_content = $this->getAttribute('value');
        if (is_object($this->_content)) {
            $contentSource = &$this->getAttribute('value');
            $this->_content = $contentSource->loadContent($this->getId(), $this->getAttribute('bindTo'));
        } else if (is_null($this->_content)) {
            $this->_content = $this->_parent->loadContent($this->getId(), $this->getAttribute('bindTo'));
        } else {
            $this->_content = html_entity_decode($this->_content);
        }
        if (empty($this->_content)) {
            $this->_content = $this->getAttribute('defaultValue');

            if (method_exists($this->_parent, 'setFilterValue')) {
                $bindTo = $this->getAttribute('bindTo');
                $this->_parent->setFilterValue(!empty($bindTo) ? $bindTo : $this->getId(), $this->_content);
            }
        }
    }

    /**
     * Render
     *
     * @return    void
     * @access    public
     */
    function render_html()
    {
        $attributes = array();
        $attributes['id'] = $this->getId();
        $attributes['name'] = $this->getOriginalId();
        $attributes['disabled'] = $this->getAttribute('disabled') ? 'disabled' : '';
        $attributes['readonly'] = $this->getAttribute('readOnly') ? 'readonly' : '';
        $attributes['title'] = $this->getAttributeString('title');

        if (empty($attributes['title'])) {
            $attributes['title'] = $this->getAttributeString('label');
        }
        $attributes['class'] =  'unimarc ' . $this->getAttribute('cssClass');
        $attributes['class'] .= (!empty($attributes['class']) ? ' ' : '') . ($this->getAttribute('required') ? 'required' : '');

        if ($this->_content && (count($this->_content)) != 1) {
            $count = 0;
            foreach ($this->_content as $content) {
                $attributes['name'] = $this->getOriginalId() . '_' . $count;
                //$attributes['value'] = $content;
                if($count == 0){
                    $output = '<div ' . $this->_renderAttributes($attributes) . '/>' . $content . '</div>';
                    $this->outputCodeWithLabel($output);
                } else{
                    $output = '<div ' . $this->_renderAttributes($attributes) . '/>' . $content . '</div>';
                    $this->addOutputCode($this->applyItemTemplate('<label for="input_html" class="col-sm-2 control-label "> </label>', $output));
                }
                $count++;
            }
        } else {
            //$attributes['value'] = $this->encodeOuput(is_string($this->_content) ? $this->_content : json_encode($this->_content))
            if(is_array($this->_content)){
                $array = $this->_content;
                $output = '<div ' . $this->_renderAttributes($attributes) . '/>' . $array[0] . '</div>';
            } else{
                $output = '<div ' . $this->_renderAttributes($attributes) . '/>' . $this->_content . '</div>';
            }
            $this->outputCodeWithLabel($output);
        }
    }

    private function outputCodeWithLabel($output)
    {
        $label = $this->getAttributeString('label') ?: '';
        if ($label) {
            $cssClassLabel = $this->getAttribute('cssClassLabel');
            $cssClassLabel .= ($cssClassLabel ? ' ' : '') . ($this->getAttribute('required') ? 'required' : '');
            if ($this->getAttribute('wrapLabel')) {
                $label = org_glizy_helpers_Html::label($this->getAttributeString('label'), $this->getId(), true, $output, array('class' => $cssClassLabel), false);
                $output = '';
            } else {
                $label = org_glizy_helpers_Html::label($this->getAttributeString('label'), $this->getId(), false, '', array('class' => $cssClassLabel), false);
            }
        }
        $this->addOutputCode($this->applyItemTemplate($label, $output));
    }


}