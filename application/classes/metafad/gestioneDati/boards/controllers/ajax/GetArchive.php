<?php
class metafad_gestioneDati_boards_controllers_ajax_GetArchive extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($id,$model)
    {
        $data = json_encode(array('__id' => $id, '__model' => $model));
        $result = $this->checkPermissionAndInstitute('visible', $data);
        if (is_array($result)) {
            return $result;
        }

        $ar = org_glizy_ObjectFactory::createModel($model);
        $ar->load($id, 'PUBLISHED_DRAFT');

        $autore = (property_exists($ar, 'cognomeNome') && property_exists($ar, 'cognomeNome')) ? $ar->autore_denominazione.' - '.$ar->autore_denominazione: null;
        $titolo = $ar->denominazione ? : $ar->titoloAttribuito;
        return array(
            'FNTA' => $autore,
            'FNTT' => $titolo,
            'FNTD' => $ar->cronologia[0]->estremoCronologicoTestuale,
            'FNTS' => $ar->segnaturaAttuale,
            'FNTI' => $ar->codiceIdentificativoSistema
        );
    }
}
