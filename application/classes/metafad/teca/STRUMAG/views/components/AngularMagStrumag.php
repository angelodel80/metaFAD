<?php
class metafad_teca_STRUMAG_views_components_AngularMagStrumag extends org_glizy_components_Component
{
    function init(){
        $this->defineAttribute('ecommerce',    false,    false,     COMPONENT_TYPE_BOOLEAN);
        parent::init();
    }

    function render(){
      $id = (__Request::get('id')) ? '?id='.__Request::get('id') :  '' ;
      $ecommerce = ($this->getAttribute('ecommerce')) ? '&state=ecommerce' : '';
      if($id == '') {
        $frameUrl = __Config::get('metafad.strumag').'?instituteKey='.metafad_usersAndPermissions_Common::getInstituteKey().$ecommerce;
      }
      else {
        $frameUrl = __Config::get('metafad.strumag').$id.'&instituteKey='.metafad_usersAndPermissions_Common::getInstituteKey().$ecommerce;
      }

      $output = '<iframe id="iframe-dam" src="'.$frameUrl.'" frameborder="0" style="display: block; height: 100vh; width: 100%;"></iframe>';
      $output .= '<script>
                          var iframeDamHeight =  window.innerHeight - $("#iframe-dam").position().top;
                          $("#iframe-dam").css("height", iframeDamHeight + "px");
                          var setIframeDamHeight = function(){
                          var iframeDamHeight =  window.innerHeight - $("#iframe-dam").position().top;
                          $("#iframe-dam").css("height", iframeDamHeight + "px");
                          };
                          $(window).on("resize",function(){setIframeDamHeight()});
                  </script>';
      $this->addOutputCode($output);
        // $output = '<link rel="stylesheet" href="static/meta_fad_strumag/bower_components/bootstrap/dist/css/bootstrap.css" />
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/angular-ui-select/dist/select.min.css" />
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/jquery-ui/themes/base/jquery-ui.min.css" />
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/fancytree/dist/skin-bootstrap/ui.fancytree.min.css">
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/font-awesome/css/font-awesome.min.css" />
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/angular-advanced-searchbox/dist/angular-advanced-searchbox.min.css">
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/fancytree/dist/skin-bootstrap/ui.fancytree.min.css">
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/pnotify/pnotify.core.css">
        //     <link rel="stylesheet" href="static/meta_fad_strumag/bower_components/angucomplete-alt/angucomplete-alt.css">
        //     <link rel="stylesheet" href="static/meta_fad_strumag/app/css/app.css">
        //     <div ng-app="fadStrumag">
        //         <div class="fadStrumag container-fluid">
        //           <div id="main">
        //             <div class="container-fluid">
        //               <div ng-include="\'static/meta_fad_strumag/app/views/strumag.html\'" ng-controller="StrumagCtrl"></div>
        //             </div>
        //           </div>
        //         </div>
        //     </div>
        //     <!-- build:js(.) scripts/vendor.js -->
        //     <!-- bower:js -->
        //     <script src="static/meta_fad_strumag/bower_components/jquery/dist/jquery.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/jquery-ui/jquery-ui.min.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular/angular.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-animate/angular-animate.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-cookies/angular-cookies.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-dragdrop/src/angular-dragdrop.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-resource/angular-resource.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-route/angular-route.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-sanitize/angular-sanitize.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-touch/angular-touch.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/less/dist/less.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-ui-utils/ui-utils.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-ui-select/dist/select.min.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-bootstrap/ui-bootstrap.min.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/fancytree/dist/jquery.fancytree-all.min.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-advanced-searchbox/dist/angular-advanced-searchbox-tpls.min.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/pnotify/pnotify.core.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/pnotify/pnotify.confirm.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-pnotify/src/angular-pnotify.js"></script>
        //     <script src="static/meta_fad_strumag/app/js/app.js"></script>
        //     <script src="static/meta_fad_strumag/app/js/controllers/strumag.js"></script>
        //     <script src="static/meta_fad_strumag/app/js/viewer.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/videogular/videogular.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/videogular-controls/vg-controls.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/videogular-overlay-play/vg-overlay-play.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/videogular-poster/vg-poster.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/videogular-buffering/vg-buffering.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angucomplete-alt/angucomplete-alt.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angular-ui-sortable/sortable.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/angularUtils-pagination/dirPagination.js"></script>
        //     <script src="static/meta_fad_strumag/bower_components/ng-context-menu/dist/ng-context-menu.js"></script>';
        // $this->addOutputCode($output);
    }
}
