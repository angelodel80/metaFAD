<?php

/**
 */
class metafad_common_helpers_VisibilityHelper
{
    public function isVisible($visibility){
        return ($visibility === null || $visibility === "" || $visibility === "rd" || $visibility === "rv" || $visibility === "rdv");
    }

    /**
     * Restituisce true se e solo se le due visibilità sono affini
     * @param $visibility
     * @param $visibility2
     * @return bool
     */
    public function compareVisibilities($visibility, $visibility2){
        return $this->isVisible($visibility) === $this->isVisible($visibility2);
    }

    /**
     * Setta a rd/rvd la visibilità
     * @param $visibility
     * @param $model
     * @return string
     */
    public function activateVisibility($visibility, $model){
        return !$this->isVisible($visibility) ? $this->toggleVisibility($visibility, $model) : $visibility;
    }

    /**
     * Setta ad r la visibilità
     * @param $visibility
     * @param $model
     * @return string
     */
    public function deactivateVisibility($visibility, $model){
        return $this->isVisible($visibility) ? $this->toggleVisibility($visibility, $model) : $visibility;
    }

    public function isFlagActive($visibility, $flag){
        return strpos($visibility, $flag) !== false;
    }

    /**
     * Restituisce r, se dato rd o rvd.
     * Restituisce rd se dato r e si parla di una CA
     * Restituisce rdv se dato r e si parla di una UA/UD
     * @param $old
     * @param $model
     * @return string
     */
    public function toggleVisibility($old, $model){
        $visible = $model != "archivi.models.ComplessoArchivistico" ? "rdv" : "rd";

        if ($old === "0" || $old === 0){
            return $visible;
        }

        $old = ($old ?: $visible);

        if ($old === "rd" || $old === "rdv" || $old === "rv") {
            return "r";
        } else if ($old == "r") {
            return $visible;
        } else {
            return $old;
        }
    }

    /**
     * Restituisce la visibilità con il flag cambiato di stato:
     * toggle r di rv = v
     * toggle d di r = rd
     * toggle r di rdv = dv
     * ...
     * @param $old
     * @param $flag
     * @return mixed|string
     */
    public function toggleSingleFlag($old, $flag){
        if ($old === "0" || $old === 0) {
            return "$flag";
        }

        if ($flag !== "r" && $flag !== "v" && $flag !== "d"){
            return $old;
        }

        $old = $old ?: "rdv";

        if (strpos($old, $flag) !== false) {
            return str_replace($flag, "", $old) ?: "0";
        } else if ($flag === "r") {
            return "r{$old}";
        } else if ($flag === "d") {
            return $old === "rv" ? "rdv" : ($old === "r" ? "rd" : "dv");
        } else if ($flag === "v") {
            return "{$old}v";
        } else {
            return $old;
        }
    }

}