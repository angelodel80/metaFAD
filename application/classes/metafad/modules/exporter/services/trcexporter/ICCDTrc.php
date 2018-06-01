<?php
class metafad_modules_exporter_services_trcexporter_ICCDTrc extends GlizyObject{

    static function getTRCHeader($version, $filename, $workCount, $dirname) {
        $gap=22-strlen($filename)-4;
        $o = $version[0] . '.' . substr($version, 1) . $filename . str_repeat(" ", $gap) . date('dmY') .sprintf("%08d", $workCount). "\r\n";
        $o .= "                                                                                \r\n";
        $o .= "                                                                                \r\n";

        return $o;
    }

	static function line($field, $value = '')
	{
	    $key_length = strlen($field);
	    return "$field:" . (!empty($value) ? metafad_modules_exporter_services_trcexporter_ICCDTrc::spaceGenerator(7, $key_length, 2) . metafad_modules_exporter_services_trcexporter_ICCDTrc::getNewlinedText($value) : '') . "\r\n"; //MZ aggiunto \r
	}

	static function getNewlinedText($text)
	{
    $text=str_replace(array("\n\r", "\n", "\r"), " ", $text);
    if (strlen($text) > 74) {
			$splitted = str_split($text, 74);
			$text = $splitted[0];

			array_shift($splitted);
			foreach ($splitted as $s) {
				$text .= "\r\n" . '      ' . $s; //MZ aggiunto \r
			}
		}

		return $text;
	}

	static function spaceGenerator($total, $value_length, $offset = 0)
	{
		return str_repeat(" ", $total - ($value_length + $offset));
	}
}

?>
