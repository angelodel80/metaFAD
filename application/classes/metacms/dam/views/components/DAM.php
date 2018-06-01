<?php
class metacms_dam_views_components_DAM extends org_glizy_components_Component
{
    function init(){
        $this->defineAttribute('damParams', false, '?setState=true', COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render(){
        $url = __Config::get('gruppometa.dam_fe.url').$this->getAttribute('damParams');

        if (__Session::exists('siteId')) {
            $url .= '&externalFilters='.urlencode('[{"publications_id":"'.__Session::get('siteId').'"}]');
            $url .= '&addDatastream='.urlencode('[{"Publication":{"publications_id":["'.__Session::get('siteId').'"]}}]');
        }

        if (__Request::exists('mediaType') && strtoupper(__Request::get('mediaType'))!='ALL') {
             $url .= '&constantFilters='.urlencode('[{"type":"'.__Request::get('mediaType').'"}]');
        }

        $output = <<<EOD
<iframe id="iframe-dam" class="iframe-fullpage" src="{$url}" frameborder="0" scrolling="no"></iframe>
<script>
$(function(){
    var setIframeSize = function(){
        var w = window.innerWidth;
        var h = window.innerHeight - $("#iframe-dam").position().top;
        $("#iframe-dam").css({"width": w, "height": h + "px"});
    };
    $(window).on("resize",setIframeSize);
    $("footer").hide();
    window.top.$('#modalIFrame').attr('scrolling', 'no');
    setIframeSize();
});
</script>
EOD;

        $this->addOutputCode($output);
    }
}