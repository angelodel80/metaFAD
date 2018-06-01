<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title><?php print($docTitle); ?></title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        <!-- bootstrap 3.0.2 -->
        <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- FontAwesome 4.3.0 -->
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <!-- Theme style -->
        <link href="css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
        <link href="js/plugins/iCheck/square/blue.css" rel="stylesheet" type="text/css" />
        <link href="css/styles.min.css" rel="stylesheet" type="text/css" />
        <?php print($head); ?>
    </head>
    <body class="login-page">
        <div class="login-box">
          <div class="login-logo">
            <img src="img/logo_metafad_login.png" alt="METAFAD"/>
          </div><!-- /.login-logo -->
          <div class="login-box-body">
            <p class="login-box-msg">Inserisci username e password</p>
            <form method="post">
                <?php if ($error) {?>
                    <p class="alert alert-danger" role="alert"><?php print($error) ?></p>
                <?php }?>

              <div class="form-group has-feedback">
                <input type="text" name="loginform_LoginId" class="form-control" placeholder="User ID"/>
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
              </div>
              <div class="form-group has-feedback">
                <input type="password" name="loginform_Password" class="form-control" placeholder="Password"/>
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
              </div>
              <div class="row">
                <div class="col-xs-8">
                  <div class="checkbox icheck">
                    <label>
                      <input type="checkbox"> Resta collegato
                    </label>
                  </div>
                </div><!-- /.col -->
                <div class="col-xs-4">
                  <button type="submit" class="btn btn-primary btn-block btn-flat">Entra</button>
                </div><!-- /.col -->
              </div>
            </form>
        </div><!-- /.login-box -->

        <script src="js/bootstrap.min.js" type="text/javascript"></script>
        <script src="js/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
        <script src="js/app.js" type="text/javascript"></script>
        <?php print($tail); ?>
    </body>
</html>
