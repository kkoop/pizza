<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= K_PRODUCT_NAME ?> - Error 404</title>
  <link href="<?=K_BASE_URL?>/css/style.css" rel="stylesheet">
</head>

<body>
  <!-- Fixed navbar -->
  <nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand" href="<?=K_BASE_URL?>/">
          <img src="<?=K_BASE_URL?>/img/Logo.svg" onerror="this.onerror=null; this.src='<?=K_BASE_URL?>/img/Logo.png'" alt="logo">
        </a>
      </div>
    </div>
  </nav>

  <div class="container" role="main">
    <div class="col-sm-6 col-sm-push-6">
      <img src="<?=K_BASE_URL?>/img/Logo_broken.svg">
    </div>
    <div class="col-sm-6 col-sm-pull-6">
      <h1>Fehler 404 - Nicht gefunden</h1>
      <p>Die angeforderte Seite wurde nicht gefunden.
    </div>
  </div>
</body>
</html>
