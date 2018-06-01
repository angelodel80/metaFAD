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
        <link href="css/styles.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" media="print" href="css/print.css">
    </head>
    <body class="sidebar-mini skin-blue-light">
        <div class="wrapper">




        <div class="content-wrapper popup">
            <section class="content">
                <?php print($content); ?>
            </section>
        </div>
    </div>

        <script src="js/bootstrap.min.js" type="text/javascript"></script>
        <script src="js/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
        <script src="js/AdminLTE/app.min.js" type="text/javascript"></script>
        <!-- <script src="js/app.js" type="text/javascript"></script> -->
        <?php print($tail); ?>

    </body>
</html>
