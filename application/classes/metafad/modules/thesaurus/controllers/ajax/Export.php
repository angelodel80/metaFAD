<?php

class metafad_modules_thesaurus_controllers_ajax_Export extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute()
    {
        $result = $this->checkPermissionForBackend('edit');
        if (is_array($result)) {
            return $result;
        }
         
    $thesaurus_id = __Request::get('id');
    //Importo le librerie
    glz_importApplicationLib('PHPExcel/Classes/PHPExcel.php');
    glz_importApplicationLib('PHPExcel/Classes/PHPExcel/Writer/Excel2007.php');
    $model = org_glizy_ObjectFactory::createModelIterator( 'metafad.modules.thesaurus.models.ThesaurusDetails' )
             ->where('thesaurusdetails_FK_thesaurus_id',$thesaurus_id);
    $final = array();
    $appoggio = array();
    //Estraggo dalla tabella thesaurusdetails_tbl tutti i record del dizionario
    foreach ($model as $value) {
      $appoggio[] = $value->thesaurusdetails_value;
      $appoggio[] = $value->thesaurusdetails_key;
      $appoggio[] = $value->thesaurusdetails_level;
      $appoggio[] = $value->thesaurusdetails_parent;
      $final[] = $appoggio;
      $appoggio = array();
    }

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);

    //Creo header delle colonne
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', "Valore");
    $objPHPExcel->getActiveSheet()->SetCellValue('B1', "Chiave");
    $objPHPExcel->getActiveSheet()->SetCellValue('C1', "Livello");
    $objPHPExcel->getActiveSheet()->SetCellValue('D1', "Figlio di");

    //Salvo valori nel file xls, sostituendo l'id del padre con la relativa chiave
    $count = 2;
    foreach ($final as $f) {
      $findParent = org_glizy_ObjectFactory::createModelIterator( 'metafad.modules.thesaurus.models.ThesaurusDetails' )->where('thesaurusdetails_id', $f[3])->first();
      $objPHPExcel->getActiveSheet()->SetCellValue('A'.$count, $f[0]);
      $objPHPExcel->getActiveSheet()->SetCellValue('B'.$count, $f[1]);
      $objPHPExcel->getActiveSheet()->SetCellValue('C'.$count, $f[2]);
      $objPHPExcel->getActiveSheet()->SetCellValue('D'.$count, $findParent->thesaurusdetails_key);
      $count++;
    }

    //Salvataggio
    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    $objWriter->save('cache/export.xls');

    return 'cache/export.xls';
  }
}
