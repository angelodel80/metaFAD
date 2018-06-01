<?php
class metafad_modules_thesaurus_views_components_RenderLevel extends org_glizy_components_Component
{
    function init()
    {
        parent::init();
    }

    function render()
    {
        $output = '
		<span class="level"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" value="tutti" onClick="clickLevel($(this));"></input></span>
        <span class="level"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" value="1" onClick="clickLevel($(this));"></input></span>
		<span class="level"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" value="2" onClick="clickLevel($(this));"></input></span>
		<span class="level"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" value="3" onClick="clickLevel($(this));"></input></span>
		<span class="level"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" value="4" onClick="clickLevel($(this));"></input></span>
		<span class="level"><input type="button" style="border:none; padding: 0px; background:none; box-shadow: none;" value="5" onClick="clickLevel($(this));"></input></span>';
                  
        
        $this->addOutputCode($output);
    }
}