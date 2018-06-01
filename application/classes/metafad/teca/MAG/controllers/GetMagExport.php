<?php
class metafad_teca_MAG_controllers_GetMagExport extends metafad_common_controllers_Command
{
    public function execute($exportName)
    {
        $this->checkPermissionForBackend('visible');
        
      $filePath = __Config::get('metafad.MAG.export.folder').'/'.md5($exportName).'.zip';
      if(file_exists($filePath) && is_file($filePath))
      {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
      }
      else
      {
        die('ERRORE: il file richiesto non esiste oppure il link è scaduto!');
      }
    }
}
