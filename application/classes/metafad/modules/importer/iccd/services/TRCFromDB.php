<?php

class metafad_modules_importer_iccd_services_TRCFromDB extends metafad_modules_importer_iccd_services_TRCBase
{
    private $db = array();
    private $reimportAll = false;

    function __construct($type, $version, $moduleName, $dbOpt, $reimportAll = false)
    {
        parent::__construct($type, $version, $moduleName);

        $this->reimportAll = $reimportAll === true;

        $this->db = array(
            "host" => __Config::get('DB_HOST'),
            "user" => __Config::get('DB_USER'),
            "password" => __Config::get('DB_PSW'),
            "dbName" => 'catalogopolofi_rub'
        );

        if (is_array($dbOpt)){
            $this->db = $dbOpt;
        }

        if (
            !key_exists("host", $this->db) ||
            !key_exists("user", $this->db) ||
            !key_exists("password", $this->db) ||
            !key_exists("dbName", $this->db)
        ){
            throw new Exception("La construct di TRCFromDB si aspetta un array con quattro chiavi: 'host', 'user', 'password', 'dbName'");
        }

        $this->readFromDB();
    }


    public function readFromDB()
    {
        $this->slog("------------Inizio nuova importazione------------");
        $this->slog("Lettura delle schede " . $this->type . $this->version . " in corso...");

        $this->setRepeatablesAndWithChildren($this->struct);

        $mysqli = new mysqli($this->db['host'], $this->db['user'], $this->db['password'], $this->db['dbName']);

        //Ottengo la struttura iniziale
        $tempStruct = $this->getTempStruct($mysqli);
        $mysqli->close();

        //MZ salvataggio stato per immftan
        file_put_contents('ftan.txt', serialize($tempStruct));

        $trcStruct = array();
        //Ottengo una struttura simile al TRC
        foreach ($tempStruct as $record)
            $trcStruct[] = $this->getSimilarTrcStruct($record, $this->struct);

        $result = array();
        foreach ($trcStruct as $r)
            foreach ($r as $v)
                $result[] = $v;

        //Ottengo gli oggetti da salvare
        $this->records = $this->getTrcRecords($result);

        $this->slog("Trovate " . count($this->records) . " schede");

    }


    //Importatore da DB: Ottengo le schede in un formato iniziale e temporaneo che fa da supporto alle elaborazioni successive
    private function getTempStruct(mysqli $mysqli)
    {
        $tables = $this->getTables();
        $works = array();

        if ($this->reimportAll){
            $mysqli->query("UPDATE og SET imported = '0' WHERE imported = '1'");
        }

        $res = $mysqli->query("SELECT * FROM og WHERE TSK = '" . $this->type . "' AND imported = '0' ORDER BY NAC LIMIT 500");

        if ($res->num_rows) {
            while ($row = $res->fetch_assoc()) {
                $key = $row['NAC'];
                $work = array();

                $work = array_merge($work, $row);

                foreach ($tables as $tableName => $tableChildren) {
                    if ($tableName == 'og') {
                        foreach ($tableChildren as $tableChildrenName) {
                            $tableValues = explode('_', $tableChildrenName);
                            $fieldName = strtoupper($tableValues[1]);

                            $res2 = $mysqli->query("SELECT * FROM $tableChildrenName WHERE OGNAC = '$key'");
                            if ($res2->num_rows)
                                while ($row2 = $res2->fetch_assoc())
                                    $work[$fieldName][] = $row2[$fieldName];
                        }

                        continue;
                    }

                    $uTableName = strtoupper($tableName);

                    $res2 = $mysqli->query("SELECT * FROM $tableName WHERE OGNAC = '$key'");
                    if ($res2->num_rows) {
                        $work[$uTableName] = array();

                        while ($row2 = $res2->fetch_assoc()) {
                            $child = $row2;

                            if (!empty($tableChildren)) {
                                $parentKey = $row2['NAC'];

                                foreach ($tableChildren as $tableChildrenName) {
                                    $tableValues = explode('_', $tableChildrenName);
                                    $parentTableName = strtoupper($tableValues[0]);
                                    $fieldName = strtoupper($tableValues[1]);

                                    $res3 = $mysqli->query("SELECT * FROM $tableChildrenName WHERE NAC$parentTableName = '$parentKey'");

                                    if ($res3->num_rows) {
                                        $child[$fieldName] = array();

                                        while ($row3 = $res3->fetch_assoc())
                                            $child[$fieldName][] = $row3[$fieldName];
                                    }
                                }
                            }

                            $work[$uTableName][] = $child;
                        }
                    }
                }

                $lcres = $mysqli->query("SELECT * FROM lc WHERE NAC = " . $work['LCNAC']);
                if ($lcres->num_rows) {
                    $lcrow = $lcres->fetch_assoc();
                    foreach ($lcrow as $k => $v)
                        $work[$k] = $v;
                }

                $works[] = $work;

                $mysqli->query("UPDATE og SET imported = '1' WHERE NAC = '" . $row['NAC'] . "'");
            }
        }

        return $works;
    }


    //Importatore da DB: ottengo la lista delle tabelle dalle quali recupero le schede da importare
    private function getTables()
    {
        return array(
            'og' => array('og_cdgi', 'og_misv', 'og_mtc', 'og_roz', 'og_fur', 'og_cdgs', 'og_aat'),
            'aut' => array(),
            'agg' => array(),
            'edt' => array(),
            'inv' => array(),
            'isr' => array(),
            'stm' => array(),
            'rof' => array(),
            'ddc' => array('ddc_ddcm', 'ddc_ddcn'),
            'cmp' => array('cmp_cmpn'),
            'aln' => array(),
            'esp' => array(),
            'isp' => array(),
            'nvc' => array(),
            'rse' => array(),
            'stt' => array(),
            'la' => array(),
            'fnt' => array(),
            'bib' => array(),
            'cmm' => array('cmm_cmmn'),
            'atb' => array('atb_atbm'),
            'dt' => array('dt_dtm', 'dt_adt'),
            'rei' => array(),
            'mst' => array(),
            'fta' => array(),
            'cop' => array(),
            'rm' => array('rm_dmm'),
            'rs' => array('rs_rstn', 'rs_rstr')
        );
    }

}
