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
    if (strpos(file_get_contents(STORAGE_PATH), $line) === false) {
      file_put_contents(STORAGE_PATH, "{$line}\n", FILE_APPEND | LOCK_EX);
    }
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
  <meta name="description" content="Wann kann mein Brief oder Paket wieder ins Ausland verschickt werden?">
  <meta name="author" content="Daniel Geymayer">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta property="og:image" content="https://apps.geymayer.com/corona-post/favicon.png">
  <link rel="apple-touch-icon" sizes="16x16" href="favicon.png">
  <link rel="icon" type="image/png" href="favicon.png" sizes="16x16">
  <link rel="shortcut icon" href="favicon.png">
  <style>
    * { box-sizing: border-box; }
    body { font-family: sans-serif; padding: 10px; max-width: 640px; }
    input, select { padding: 10px; margin-bottom: 10px; }
    button { padding: 10px; background-color: yellow; font-weight: bold; cursor: pointer; }
    input, select, button { height: 40px; width: 100%; max-width: 400px; }
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
  <h2>Wann kann mein Brief oder Paket von der Post wieder ins Ausland verschickt werden?</h2>
  <p>
    <a href="https://www.post.at/p/c/liefereinschraenkungen-coronavirus" target="_blank">
      Wegen Corona is teilweise gerade schwer mit der Post was zu verschicken.
    </a>
    Wenn du dich in die Mailingliste einträgst wirst du verständigt sobald's wieder geht.
  </p>
  <?php if (isset($_SESSION['success'])): unset($_SESSION['success']); ?>
  <p style="background-color: blue; color: white; padding: 10px;"><b>Daunksche, kriagst a Mail wenn's soweit is!</b></p>
  <?php endif ?>
  <form method="post">
    <input type="email" name="email" placeholder="Deine E-Mail bitte ..." autocomplete="email" required>
    <br>
    <select name="country" required>
      <option value="">Wohin soll's gehen?</option>
      <?php foreach ($countries as $country): ?>
      <option value="<?= $country ?>"><?= $country ?></option>
      <?php endforeach ?>
    </select>
    <br>
    <select name="type" required>
      <option value="all">Brief & Paket</option>
      <?php foreach ($types as $t): ?>
      <option value="<?= $t ?>"><?= $t ?></option>
      <?php endforeach ?>
    </select>
    <br>
    <button type="submit">Gib Bescheid, wenn's so weit is!</button>
  </form>
  <p>
    E-Mail-Adressen werden eh kloa DSGVO-konform in keiner Weise weitergegeben.<br>
    Nachdem die Mail an dich verschickt worden is, wird die Adresse auch wieder gelöscht.<br>
    Auf dass ich de Seiten do bald wieder einrexen kann! 🍻
  </p>
  <p><a href="https://github.com/dag0310/corona-post" target="_blank">GitHub-Repo</a></p>
  <p>Für Wünsche/Beschwerden findet man <a href="https://github.com/dag0310/" target="_blank">meine Mail-Adresse auf meinem GitHub-Profil</a></p>
</body>
</html>