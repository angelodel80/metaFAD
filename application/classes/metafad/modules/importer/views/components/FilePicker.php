<?php
class metafad_modules_importer_views_components_FilePicker extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('pageId', false, 'StorageBrowser', COMPONENT_TYPE_STRING);
        $this->defineAttribute('title', false, 'Seleziona File', COMPONENT_TYPE_STRING);
        $this->defineAttribute('onlyFolder', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('onlyFirstLevel', false, false, COMPONENT_TYPE_BOOLEAN);
        $this->defineAttribute('customPath',  false, '', COMPONENT_TYPE_STRING);

        // call the superclass for validate the attributes
        parent::init();
    }

    function render()
    {
        $id = $this->getAttribute('id');
        $pageId = $this->getAttribute('pageId');
        $onlyFolder = $this->getAttribute('onlyFolder');
        $onlyFirstLevel = $this->getAttribute('onlyFirstLevel');
        $customPath = $this->getAttribute('customPath');

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

</script>
EOD;
        $required = $this->getAttribute('required') == 'true' ? 'required' : '';

        $output .= '<div class="form-group">';
        $output .= '  <label for="argumentId" class="col-sm-2 control-label '.$required.'">'.$this->getAttribute('label').'</label>';
        $output .= '  <div class="col-sm-10">';
        $output .= '    <input type="text" name="'.$id.'" id="'.$id.'" placeHolder="Seleziona" readonly="true" class="form-control '.$required.'"/>';
        $output .= '  </div>';
        $output .= '</div>';
        $output .= '<div id="modalDiv" style="display: none; margin: 0; padding: 0; overflow: hidden;"><iframe src="" id="modalIFrame" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" title="Seleziona Media"></iframe></div>';

        $this->addOutputCode($output);

    }

}