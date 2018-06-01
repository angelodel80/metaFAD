<?php
class metafad_teca_MAG_views_components_FilePicker extends org_glizycms_mediaArchive_views_components_FilePicker
{
    function init()
    {
        parent::init();
    }

	function render()
	{
		$id = $this->getAttribute('id');
        $pageId = $this->getAttribute('pageId');
	    $onlyFolder = $this->getAttribute('onlyFolder');
        $onlyFirstLevel = $this->getAttribute('onlyFirstLevel');
        $customPath = __Config::get('metafad.MAG.folder');

		$this->_application->_rootComponent->addOutputCode(org_glizy_helpers_CSS::linkCSSfile(  __Paths::get('APPLICATION_TEMPLATE').'css/customStorageBrowser.css' ), 'head' );

		$storageBrowserUrl = 'index.php?pageId='.$pageId.'&onlyFolder='.$onlyFolder.'&onlyFirstLevel='.$onlyFirstLevel.'&customPath='.$customPath;
        $title = $this->getAttribute('title');

		$output = <<<EOD
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#modalDiv").dialog({
				modal: true,
				autoOpen: false,
				draggable: true,
				resizeable: true,
				title: '$title'
			    });

  function openFilePicker() {
    var w = Math.min( jQuery( window ).width() - 50, 900 );
		var h = jQuery( window ).height() - 50;

		$("#modalDiv").dialog("option", { height: h, width: w } );
		$("#modalDiv").dialog("open" );
		if ( $("#modalIFrame").attr('src') == "" )
		{
			$("#modalIFrame").attr('src', '$storageBrowserUrl');
		}
	}
    jQuery( "#$id" ).click(openFilePicker);
	jQuery( "#mediaFilePicker" ).click(openFilePicker);
});

function custom_storageBrowserSelect( path )
{
	jQuery( "#$id" ).val( path ).trigger('change');
	$("#modalDiv").dialog("close");

	if ( window.filePicker )
	{
		window.filePicker( path );
	}
}

function setCopyToCMS( value )
{
    jQuery( "#copyToCMS" ).val( value ? 'true' : 'false' );
}
</script>
EOD;
        $required = $this->getAttribute('required') == 'true' ? 'required' : '';

        $output .= '<div class="form-group">';
        $output .= '  <label for="argumentId" class="col-sm-2 control-label '.$required.'">'.$this->getAttribute('label').'</label>';
        $output .= '  <div class="col-sm-10">';
        $output .= '    <input type="hidden" name="copyToCMS" id="copyToCMS" value="false"/>';
        $output .= '    <div class="col-sm-10" style="padding-left: 0px;"><input type="text" name="'.$id.'" id="'.$id.'" value="" readonly="true" class="form-control '.$required.'"/></div>';
        $output .= '    <div class="col-sm-2"><input id="mediaFilePicker" type="button" value="Seleziona" class="btn"/></div>';
        $output .= '  </div>';
        $output .= '</div>';
		$output .= '<div id="modalDiv" style="display: none; margin: 0; padding: 0; overflow: hidden;"><iframe src="" id="modalIFrame" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" title="Seleziona Media"></iframe></div>';

		$this->addOutputCode($output);

	}

}
