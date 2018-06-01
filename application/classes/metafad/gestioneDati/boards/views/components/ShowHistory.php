<?php
class metafad_gestioneDati_boards_views_components_ShowHistory extends org_glizy_components_ComponentContainer
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('model', true, '', COMPONENT_TYPE_STRING);
        $this->defineAttribute('controllerName', false, 'metafad.gestioneDati.boards.controllers.*', COMPONENT_TYPE_STRING);

        parent::init();
    }

    function render()
    {
        $id = __Request::get('id');
        $model = $this->getAttribute('model');
        $canRallback = true;
        $ajaxUrl = str_replace('&action=', '&model='.$model.'&action=', $this->getAjaxUrl());


        $it = org_glizy_objectFactory::createModelIterator($model);
        $it->select('doc_detail.document_detail_id', 'doc_detail.document_detail_modificationDate', 'u.*')
            ->join($it::DOCUMENT_TABLE_ALIAS, __Config::get('DB_PREFIX').'users_tbl', 'u',
                  $it->expr()->eq($it::DOCUMENT_DETAIL_TABLE_ALIAS.'.'.$it::DOCUMENT_DETAIL_FK_USER, 'u.user_id'))
            ->where("document_id", $id)
            ->orderBy('document_detail_modificationDate', 'DESC')
            ->allStatuses();

        $output = '<table class="table table-bordered table-striped">'
                    .'<thead><tr><th>'.__T('Updated from').'</th><th width="140">'.__T('Update date').'</th></tr></thead>'.
                    '<tbody>';
        $i = 0;
        foreach ($it as $ar) {
            $output .= '<tr>'.
            '<td><input type="radio" name="history_a" value="'.$ar->document_detail_id.'" /> '.
            '<input type="radio" name="history_b" value="'.$ar->document_detail_id.'" /> '.
            $ar->user_firstName.' '.$ar->user_lastName.'</td>'.
             '<td nowrap>'.$ar->document_detail_modificationDate.' '.
            ($i>0 && $canRallback? '<a title="Rollback" href="#" class="js-history-rollback" data-version="'.$ar->document_detail_id.'"><i class="fa fa-exchange btn-icon"></i> </a>' : '').
            '</td>'.
            '</tr>';

            if ($i==20) break;
            $i++;
        }

        $output .= '</tbody>'.
                    '</table>'.
                    '<div id="diff"></div>'.
                    $this->renderJSCode($id, $ajaxUrl);

        $this->addOutputCode($output);
    }

    private function renderJSCode($id, $ajaxUrl)
    {
        $output = <<<EOD
<script>
$(function(){
  var \$btn = \$('input.js-glizycms-history');

  function getSelectedValues()
  {
    var a = \$('input[name=history_a]:checked').val();
    var b = \$('input[name=history_b]:checked').val();
    if (a && b && a!=b) {
      return {a: a, b: b};
    }
    return false;
  }
  function checkDisabled() {
    var sel = getSelectedValues();
    \$btn.attr('disabled', sel===false);
  }

  \$('input').on('ifChanged', function (event) { \$(event.target).trigger('change'); });

  \$('input[name=history_a]').change(function(){
    checkDisabled();
  });
  \$('input[name=history_b]').change(function(){
    checkDisabled();
  });

  \$btn.click(function(e){
      e.preventDefault();
      var sel = getSelectedValues();
      if (sel!==false) {
        $.ajax({
            'url': '{$ajaxUrl}ShowHistory',
            'data': sel,
            'dataType': 'html',
            'success': function(data) {
              if (data!=='{"status":false}') {
                $("#diff").html(data);
                window.scrollTo(0, $("#diff").offset().top);
              }
            }
        });
      }
  });

  \$('a.js-history-rollback').click(function(e){
    e.preventDefault();
    if (confirm('Sei sicuro di ritornare a questa versione?')) {
      $.ajax({
            'url': '{$ajaxUrl}RollbackHistory',
            'data': {id: $id, vid: \$(this).data('version')},
            'success': function(data) {
              location.reload();

            }
        });
    }
  });

  checkDisabled();
});
</script>
EOD;
        return $output;
    }
}
