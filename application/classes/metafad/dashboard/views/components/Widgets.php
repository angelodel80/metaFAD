<?php
class metafad_dashboard_views_components_Widgets extends org_glizy_components_Component
{
    public function process()
    {
        $now = explode(' ', strftime("%B %e %d %A", time()));
        $this->_content = array();
        $it = org_glizy_ObjectFactory::createModelIterator('metafad.workflow.instanceActivities.models.Model');
        $this->_content['activitiesAssigned'] = $it->count();
        //TODO rimozione cablato
        $this->_content['sheetsAssigned'] = 30;

        if(__Config::get('metafad.be.hasEcommerce') == 'true')
        {
          $userId = $this->_application->getCurrentUser()->id;
          __Session::set('currentUser',$userId);
          $requests = org_glizy_ObjectFactory::createModelIterator('metafad.ecommerce.requests.models.Model');
          $this->_content['requestsAssigned'] = $requests
                                                ->where('request_operator_id',$userId)
                                                ->where('request_state','toRead')
                                                ->count();
        }

        $this->_content['orders'] = 0;
        $this->_content['month'] = glz_encodeOutput($now[0]);
        $this->_content['day'] = glz_encodeOutput($now[1]);
        $this->_content['dayWeek'] = glz_encodeOutput($now[2]);
        $this->_content['time'] = glz_encodeOutput($now[3]);
    }
}
