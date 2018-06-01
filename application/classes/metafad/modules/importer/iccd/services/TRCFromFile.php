<?php
class metafad_modules_importer_iccd_services_TRCFromFile extends metafad_modules_importer_iccd_services_TRCBase
{

   function __construct($type, $version, $moduleName, $TRCFilePath, $TRCFile)
   {
     parent::__construct($type, $version, $moduleName);
     $this->read($TRCFilePath, $TRCFile);
   }


  public function read($dir, $trcFile)
  {
      $this->dir = $dir;

      $file = $dir . $trcFile;
      if (file_exists($file)) {
          $this->setRepeatablesAndWithChildren($this->struct);

          list($content, $result) = $this->getRightTrc($file);
          $result = explode("\n", $result);

          $this->records = $this->getTrcRecords($result);
          $this->slog(count($this->records)." Record letti dal file TRC");

          $content = file($file);
          $fl = $content[0];
          $type = rtrim(substr($fl,9,3)," ");

          return true;
      }

      return false;
  }


  private function getRightTrc($f)
  {
      $file = (!is_array($f) ? file($f) : $f);
      $content = array();
      $result = "";

      $nl = "\r\n";

      $c = 0;

      for ($i = 0; $i < count($file); $i++) {
          if (preg_match('/^[A-Z]{2,5}:/', $file[$i]) > 0) {
              if (strpos($content[$c - 1], $nl) === false) {
                  $content[$c - 1] .= $nl;
              }
              $content[$c] = $file[$i];

              $c++;
          } elseif (trim($file[$i]) != "") {
              $content[$c - 1] = str_replace(array($nl, "\n"), array("", "", ""), $content[$c - 1] . substr($file[$i], 6));
              $c++;
          }
      }

      for ($i = 0; $i < count($content); $i++)
          $result .= $content[$i];

      $content[$c] = "\nCD:";

      return array($content, str_replace("\n\r", "", $result));
  }

}
