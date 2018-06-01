<?php
class metafad_modules_logs_views_components_SearchLog extends org_glizy_components_SearchFilters
{

	function process()
	{
		$this->sessionEx 	= new org_glizy_SessionEx($this->getId());
		$this->_command		= org_glizy_Request::get($this->getId().'_command');
		if ($this->_command=='RESET')
		{
			$this->sessionEx->removeAll();
		}

        $this->processChilds();
	}

	function getFilters()
	{
		$tempFilters = array();
		if ( !empty( $this->_filters[ 'filterDescription' ] ) )
		{
			// $tempFilters[] = "log_message LIKE '%".$this->_filters[ 'filterDescription' ]."%'";
			$tempFilters['log_message'] = $this->_filters[ 'filterDescription' ];
		}

        if ( $this->_filters[ 'dateFrom' ] != "" )
		{
            $tmp = explode( '/', $this->_filters[ 'dateFrom' ] );
			$tempFilters[] = 'DATEDIFF( "' .$tmp[2].'-'.$tmp[1].'-'.$tmp[0].'", log_date ) <= 0';
		}

        if ( $this->_filters[ 'dateTo' ] != "" )
		{
            $tmp2 = explode( '/', $this->_filters[ 'dateTo' ] );
			$tempFilters[] = 'DATEDIFF( "'. $tmp2[2].'-'.$tmp2[1].'-'.$tmp2[0] .'", log_date ) >= 0';
		}
        else
        {
            $tempFilters[] = "DATEDIFF( NOW(), log_date ) >= 0";
        }

        if ( !empty( $this->_filters[ 'filterLevel' ] ) )
		{
			$tempFilters[ 'log_level' ] = $this->_filters[ 'filterLevel' ];
		}

		return $tempFilters;
	}

}
