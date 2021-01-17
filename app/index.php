<?php
session_start();

define('STORAGE_PATH', 'receivers.txt');

$types = ['Brief', 'Paket'];
$countries = [];
foreach ($types as $t) {
  $new_countries = str_getcsv(trim(utf8_encode(file_get_contents("20210112-Annahmestopp-{$t}International.csv"))), "\n");
  $countries = array_unique(array_merge($countries, $new_countries));
}
try {
  collator_sort(collator_create('de'), $countries);
} catch (Throwable $t) {
  sort($countries);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['email']) || !isset($_POST['country']) || !isset($_POST['type'])) {
    die('Missing form data.');
  }
  if (strpos($_POST['email'], '@') === false) {
    die('@ in email missing.');
  }
  if (!in_array($_POST['country'], $countries, true)) {
    die('Invalid country.');
  }
  if ($_POST['type'] !== 'all' && !in_array($_POST['type'], $types, true)) {
    die('Invalid type.');
  }
  $email = trim($_POST['email']);
  $country = $_POST['country'];
  $type = $_POST['type'];
  foreach (($type === 'all') ? $types : [$type] as $t) {
    $line = implode("\t", [$email, $country, $t]);
    file_put_contents(STORAGE_PATH, "{$line}\n", FILE_APPEND | LOCK_EX);
  }
  $_SESSION['success'] = 'true';
  header('Location: .');
  exit();
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>corona-post</title>
  <meta name="description" content="Wann kann ich mein Brief oder Packl wieder ins Ausland schicken?">
  <meta name="author" content="Daniel Geymayer">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta property="og:image" content="https://apps.geymayer.com/corona-post/favicon.png">
  <link rel="apple-touch-icon" sizes="16x16" href="favicon.png">
  <link rel="icon" type="image/png" href="favicon.png" sizes="16x16">
  <link rel="shortcut icon" href="favicon.png">
  <style>
    body { padding: 10px; }
    input, select { padding: 5px; margin-bottom: 10px; }
    button { padding: 5px; background-color: yellow; }
    h1 { margin-top: 0; }
  </style>
  <!-- Matomo -->
  <script type="text/javascript">
    var _paq = window._paq = window._paq || [];
    /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
      var u="https://matomo.geymayer.com/";
      _paq.push(['setTrackerUrl', u+'matomo.php']);
      _paq.push(['setSiteId', '7']);
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
      g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
    })();
  </script>
  <!-- End Matomo Code -->
</head>
<body>
  <h1>corona-post</h1>
  <h2>Wann kann ich mein Brief oder Packl wieder ins Ausland schicken?</h2>
  <p>
    Wegen Corona is teilweise gerade schwer mit der Post was zu verschicken.<br>
    Wenn du dich in die Mailingliste einträgst wirst verständigt sobald's wieder geht.
  </p>
  <form method="post">
    <input type="email" name="email" placeholder="Dei E-Mail bitte hier" autocomplete="email" required>
    <br>
    <select name="country" required>
      <option value="">Wohin soll's gehen?</option>
      <?php foreach ($countries as $country): ?>
      <option value="<?= $country ?>"><?= $country ?></option>
      <?php endforeach ?>
    </select>
    <br>
    <select name="type" required>
      <option value="all">Brief & Packl</option>
      <?php foreach ($types as $t): ?>
      <option value="<?= $t ?>"><?= $t ?></option>
      <?php endforeach ?>
    </select>
    <br>
    <button type="submit">Gib Bescheid, wenn's so weit is!</button>
  </form>
  <?php if (isset($_SESSION['success'])): unset($_SESSION['success']); ?>
  <p><b>Daunksche, kriagst a Mail wenn's soweit is!</b></p>
  <?php endif ?>
  <p>
    E-Mail-Adressen werden eh kloa DSGVO-konform in keiner Weise weitergegeben.<br>
    Nachdem die Mail verschickt worden is, wird die Adresse auch wieder gelöscht.<br>
    Auf dass ich de Seiten do bald wieder einrexen kann! 🍻
  </p>
  <p><a href="https://github.com/dag0310/corona-post" target="_blank">GitHub-Repo</a></p>
</body>
</html>