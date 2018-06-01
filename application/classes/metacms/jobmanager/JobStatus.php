<?php
class metacms_jobmanager_JobStatus
{
    const NOT_STARTED = 'NOT_STARTED';
    const RUNNING = 'RUNNING';
    const COMPLETED = 'COMPLETED';
    const ERROR = 'ERROR';
    
    public static $description = array (
                metacms_jobmanager_JobStatus::NOT_STARTED => 'Non eseguito', 
                metacms_jobmanager_JobStatus::RUNNING => 'Esecuzione in corso', 
                metacms_jobmanager_JobStatus::COMPLETED => 'Eseguito',
                metacms_jobmanager_JobStatus::ERROR => 'Errore'
            );

    public static function getDescription($status)
    {
        return self::$description[$status];
    }
}