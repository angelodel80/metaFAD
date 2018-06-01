<?php
class metafad_teca_DAM_views_components_DAM extends org_glizy_components_Component
{
    function init(){
        parent::init();
    }

    function render(){
        $src = __Config::get('metafad.tecadam').'&instance='.__Config::get('gruppometa.dam.instance');
        $output =<<<EOD
<iframe id="iframe-dam" src="$src" frameborder="0" style="display: block; height: 100vh; width: 100%;"></iframe>
<script>
    var setIframeDamHeight = function(){
        var iframeDamHeight =  window.innerHeight - $("#iframe-dam").position().top;
        $("#iframe-dam").css("height", iframeDamHeight + "px");
    };

    setIframeDamHeight();

    $(window).on("resize",function(){
        setIframeDamHeight();
    });
</script>
EOD;

        $this->addOutputCode($output);
    }
}
