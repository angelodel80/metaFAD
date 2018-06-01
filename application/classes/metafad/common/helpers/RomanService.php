<?php

/**
 * Class metafad_common_importer_utilities_RomanService
 */
class metafad_common_helpers_RomanService
{
    protected static $table = array();

    protected static function initTable(){
        if (!count(self::$table)){
            self::$table = array(
                'M' => 1000,
                'CM' => 900,
                'D' => 500,
                'CD' => 400,
                'C' => 100,
                'XC' => 90,
                'L' => 50,
                'XL' => 40,
                'X' => 10,
                'IX' => 9,
                'V' => 5,
                'IV' => 4,
                'I' => 1
            );
        }

        return self::$table;
    }

    static function integerToRomanic($integer)
    {
        if ($integer > 4999 || $integer < 0){
            return $integer;
        }

        $table = self::initTable();

        $return = '';
        while ($integer > 0) {
            foreach ($table as $rom => $arb) {
                if ($integer >= $arb) {
                    $integer -= $arb;
                    $return .= $rom;
                    break;
                }
            }
        }

        return $return;
    }

    static function romanToInteger($string)
    {
        $string = strtoupper($string);
        if (!preg_match("/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/", $string)){
            return $string;
        }

        $table = self::initTable();

        $roman = $string;
        $result = 0;

        foreach ($table as $key => $value) {
            while (strpos($roman, $key) === 0) {
                $result += $value;
                $roman = substr($roman, strlen($key));
            }
        }

        return $result;
    }
}