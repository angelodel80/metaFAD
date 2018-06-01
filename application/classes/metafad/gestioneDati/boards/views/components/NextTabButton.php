<?php

class metafad_gestioneDati_boards_views_components_NextTabButton extends org_glizy_components_Component
{
	function render()
    {
        $output = <<<EOD
<a class="btn btn-flat tabButton js-nextTabButton"><span class="fa fa-angle-double-right"></span></a>
<script>
$(function(){
    var el = $('a.js-nextTabButton').click(function(e){
        e.preventDefault();
        $('a[data-toggle="tab-next"]').click();
    })
    var updateNav = function() {
        var activePanel = $('#innerTabs_content div[class="tab-pane active"]');
        el.toggleClass('disabled', !activePanel.next().hasClass('tab-pane'));
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

