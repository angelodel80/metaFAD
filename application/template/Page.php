<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title><?php print($docTitle); ?></title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

        <?php print($head); ?>

        <!-- bootstrap 3.0.2 -->
        <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- FontAwesome 4.3.0 -->
        <link href="http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.css" rel="stylesheet" type="text/css" />
        <!-- Theme style -->
        <link href="css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
        <link href="css/skins/skin-blue-light.min.css" rel="stylesheet" type="text/css" />
        <link href="js/plugins/iCheck/square/blue.css" rel="stylesheet" type="text/css" />
        <link href="css/styles.min.css?v=1.1" rel="stylesheet" type="text/css" />
    </head>
    <body class="sidebar-mini skin-blue-light">
        <div class="wrapper">
        <header class="main-header">
            <a href="<?php print GLZ_HOST;?>" class="logo">
                <img class="logo-lg" src="img/logo_metafad.png" alt="METAFAD" />
                <img class="logo-mini" src="img/logo_metafad_mini.png" alt="METAFAD"/>
            </a>
            <!-- Header Navbar: style can be found in header.less -->
            <nav class="navbar navbar-static-top" role="navigation">
                <?php print($navigation); ?>

                <div class="pull-right">
                    <?php print(@$languageMenu); ?>
                </div>

                <!-- Sidebar toggle button-->
                <?php if (@$leftSidebar) { ?>
                <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </a>
                <?php } ?>
            </nav>
        </header>

        <?php if (@$leftSidebar) { ?>
        <aside class="main-sidebar">
            <section class="sidebar">
                <?php print($leftSidebar); ?>
            </section>
        </aside>
        <?php } ?>
            
        <div class="content-wrapper <?php if (!@$leftSidebar) echo 'margin-left-0'?>">
            <section class="content-header clearfix">
                <div class="pull-right">
                    <?php print($actions); ?>
                </div>
                <?php print($pageTitle); ?>
            </section>

            <?php if(!$dam){?>
                <?php if (isset($treeview)) { ?>
                    <section class="content content-fix">
                        <div class="col-md-4">
                            <?php print($treeview); ?>
                        </div>
                        <div id="container" class="with-treeview col-md-8">
                            <div id="container-inner" class="container-fluid">
                                <?php print($content); ?>
                            </div>
                        </div>
                    </section>
                <?php } else {?>
                    <section class="content">
                        <?php print($content); ?>
                    </section>
                <?php } ?>
            </section>
            <?php }?>
            <?php print($dam); ?>
        </div>
    </div>

        <script src="js/bootstrap.min.js" type="text/javascript"></script>
        <script src="js/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
        <script src="js/plugins/jQuery-Knob/dist/jquery.knob.min.js" type="text/javascript"></script>
        <script src="js/AdminLTE/app.min.js" type="text/javascript"></script>
        <script src="js/app.js" type="text/javascript"></script>
        <?php print($tail); ?>
        <script src="../../static/jquery/jquery-lazyload/jquery.lazyload.js"></script>
    </body>
</html>
