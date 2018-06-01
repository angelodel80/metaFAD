<?php
class metafad_common_views_renderer_ShortField extends org_glizycms_contents_views_renderer_DocumentTitle
{
    function renderCell( $key, $value, $row )
    {
      if(sizeof($value) > 1)
      {
        $string = '';
        $count = 1;
        foreach ($value as $v) {
          if($count == sizeof($value) && $count > 2)
          {
            if(preg_match('/\\d/', $v))
            {
              $string .= '-';
            }
          }
          $string .= $v;
          $count++;
        }
      }
      else
      {
        $string = is_array($value) ? $value[0] : $value;
      }
      $pp = (strlen($string) >= 50) ? '...' : '';
      return mb_substr($string,0,50,'UTF-8').$pp;
    }
}
