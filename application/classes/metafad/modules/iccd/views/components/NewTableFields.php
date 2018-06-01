<?php
/**
 * @copyright Copyright(c) 2005-2009 Ministero per i beni e le attività culturali. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * Museo & Web CMS is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 * @author		Daniele Ugoletti <daniele@ugoletti.com>, Gruppo Meta <http://www.gruppometa.it>
 * @package		Museo&Web CMS
 * @category	Component
 */


class metafad_modules_iccd_views_components_NewTableFields extends org_glizy_components_Component
{
	protected $data;

    function init()
    {
        $this->defineAttribute('data', false, NULL, COMPONENT_TYPE_STRING);

        parent::init();
    }

    function process()
    {
    	$this->data = json_decode($this->getAttribute('data'));
    }

	function render()
	{
		$output = '<table id="editTable" class="modulesBuilderTable table table-striped">';
		$output .= '<tbody>';
		$output .= '<tr id="row_0">';
		$output .= '<td width="10"><img src="application/templates/images/dragHandler.gif" /></td>';
		$output .= '<td>';
		$output .= '<select id="' . $this->getAttribute('id') . '" name="fieldType[]">';

        foreach ((array)$this->data as $key => $value)
        {
            if($value != '')
            {
                if($key == $this->getAttribute('defaultValue'))
                    $selected = 'selected="selected"';
                else
                    $selected = '';

                $output .= '<option ' . $selected . ' value="' . $key . '">' . $key . ' - ' . $value . '</option>';
            }
        }
		$output .= '</select>';

		$output .= '<td style="text-align: center">';
		$output .= '<input type="checkbox" name="fieldSearch[]" value="true" />';
		$output .= '</td>';
		$output .= '<td style="text-align: center">';
		$output .= '<input type="checkbox" name="fieldListSearch[]" value="true"/>';
		$output .= '</td>';
		$output .= '<td style="text-align: center">';
		$output .= '<img class="delete" src="application/templates/images/icon_delete.gif" />';
		$output .= '</td>';
		$output .= '</tr>';
		$output .= '</tbody>';
		$output .= '<tfoot>';
		$output .= '<tr>';
		$output .= '<td colspan="8" class="newTableFoot"><a id="newTableBtnAdd" href="#">Aggiungi campo</a></td>';
		$output .= '</tr>';
		$output .= '</tfoot>';
		$output .= '<thead>';
		$output .= '<th></th>';
		$output .= '<th>' . __T('Campo') . '</th>';
		$output .= '<th>' . __T('Ricerca') . '</th>';
		$output .= '<th>' . __T('Lista ricerca') . '</th>';
		$output .= '<th></th>';
		$output .= '</tr>';
		$output .= '</thead>';
		$output .= '</table>';
		$output .= '<input type="hidden" name="fieldKey" value="id" />';

		$output .= <<<EOF
<script type="text/javascript">
$(function(){
	var nextRow = 1;

	var setOrder = function() {
		$("#editTable tr img.delete").show();
		$("#editTable tr img.delete").first().hide();

		var orderList = [];
		$("#editTable tbody tr" ).each( function (index, element) {
			orderList.push( element.id );
		});
		$("#fieldOrder").val(orderList.join(','));
	}

	var fixHelper = function(e, ui) {
		ui.children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	};

	$("#editTable tbody").sortable({
		helper: fixHelper,
		stop: function( event, ui ) {
			setOrder();
		}
	});

	$("#newTableBtnAdd").click(function(e){
		e.preventDefault();
		var firstRow = $('#row_0');
		var newRow = firstRow.clone();
		var id = "row_" + nextRow;
		newRow.attr('id', "row_" + nextRow);
		nextRow++;
		newRow.find('input:text').each(function(index, element){
			$(element).val('');

		});

		newRow.find('input:checkbox').each(function(index, element){
			$(element).val(true);
			$(element).removeAttr('checked');
		});

		newRow.find("img.delete").show();
		firstRow.parent().append(newRow);
	});

	$("#editTable").on("click", "img.delete", function(e){
		e.preventDefault();

		if ( confirm( 'Sei sicuro di cancellare il campo?' ) ) {
			$(this).parent().parent().remove();
			setOrder();
		}
	})

});
</script>
EOF;

		$this->addOutputCode($output);
	}

}
