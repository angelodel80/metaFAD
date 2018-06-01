<?php

/**
 * Class metafad_modules_importer_helpers_AbstractBatch
 */
abstract class metafad_modules_importer_helpers_AbstractBatch extends metacms_jobmanager_service_JobService
{
    /**
     * Nota bene: progress va da 0 a 100
     * @param $evt
     */
    public function mainRunnerProgress($evt){
        $data = $evt->data;
        if (key_exists('progress', $data)){
            $this->updateProgress(floor($data['progress']));
        }
        if (key_exists('message', $data)){
            $this->setMessage($data['message']);
        }

//        $progress = round($data['progress'], 2);
//        echo "Progress {$progress}%: {$data['message']}\r\n<br>\r\n";
        $this->save();
    }

    /**
     * Effetti:
     * <br>
     * il job ascolta l'evento mainRunnerProgress
     * <br>
     * i limiti di tempo e memoria vengono estesi di molto (tempo infinito, memoria 2GB).
     * <br>
     * viene aggiunto un error handler per segnalare errori PHP
     */
    public function run(){
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->addEventListener('mainRunnerProgress', $this);
        set_error_handler(function($num, $str, $file, $line){
            throw new Error(
                "Errore PHP n°$num: $str\r\n" .
                "$file:$line"
            );
        });
    }
}