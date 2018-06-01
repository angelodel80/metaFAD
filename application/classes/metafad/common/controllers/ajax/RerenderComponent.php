<?php

/**
 * Class metafad_common_controllers_ajax_RerenderComponent
 * Controller ajax banale per ricevere il componente refreshato con lo stato aggiornato.
 */
class metafad_common_controllers_ajax_RerenderComponent extends metafad_common_controllers_ajax_CommandAjax
{

    /**
     * Prende il corpo della POST e restituisce un array con i seguenti 3 valori:
     * <ul>
     * <li>sendOutput: preso dal campo JSON omonimo nella __Request::get() o 'linkedImages' di default</li>
     * <li>sendOutputState: preso dal campo JSON omonimo nella __Request::get() o 'edit' di default</li>
     * <li>sendOutputFormat: preso dal campo JSON omonimo nella __Request::get() o 'html' di default</li>
     * </ul>
     *
     * Questo funziona perché quei 3 valori sono speciali per Glizy, vedi:
     * org_glizy_application_Application,line:475, metodo processAjaxCallController
     * @return array
     */
    public function execute()
    {
        return array(
            'sendOutput' => __Request::get("sendOutput") ?: 'linkedImages',
            'sendOutputState' => __Request::get("sendOutputState") ?: 'edit',
            'sendOutputFormat' => __Request::get("sendOutputFormat") ?: 'html'
        );
    }
}