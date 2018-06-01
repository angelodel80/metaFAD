<?php

class metafad_gestioneDati_boards_views_components_PreviousTabButton extends org_glizy_components_Component
{
	function render()
    {
        $output = <<<EOD
<a class="btn btn-flat tabButton js-previousTabButton"><span class="fa fa-angle-double-left"></span></a>
<script>
$(function(){
    var el = $('a.js-previousTabButton').click(function(e){
        e.preventDefault();
        $('a[data-toggle="tab-prev"]').click();
    })
    var updateNav = function() {
        var activePanel = $('#innerTabs_content div[class="tab-pane active"]');
        el.toggleClass('disabled', !activePanel.prev().hasClass('tab-pane'));
    }

    $('#innerTabs').on('shown.bs.tab', function (e) {
        updateNav();
    });

    updateNav();
});
</script>
EOD;
        $this->addOutputCode($output);
    }

}
