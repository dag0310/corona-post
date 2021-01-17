<?php
session_start();

define('STORAGE_PATH', 'receivers.txt');

function write_to_receivers_file($email, $country, $type, $messages) {
  if (empty($country)) {
    return $messages;
  }
  $line = implode("\t", [$email, $country, $type]);
  if (strpos(file_get_contents(STORAGE_PATH), $line) === false) {
    file_put_contents(STORAGE_PATH, "{$line}\n", FILE_APPEND | LOCK_EX);
    $messages[] = "Daunksche, kriegst a Mail für $country ($type) wenn's soweit is!";
  } else {
    $messages[] = "Ein Eintrag für $country ($type) ist für dich bereits in der Liste.";
  }
  return $messages;
}

$letter_countries = str_getcsv(trim(utf8_encode(file_get_contents("20210112-Annahmestopp-BriefInternational.csv"))), "\n");
$package_countries = str_getcsv(trim(utf8_encode(file_get_contents("20210112-Annahmestopp-PaketInternational.csv"))), "\n");
try {
  collator_sort(collator_create('de'), $letter_countries);
  collator_sort(collator_create('de'), $package_countries);
} catch (Throwable $t) {
  sort($letter_countries);
  sort($package_countries);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['email']) || strpos($_POST['email'], '@') === false) {
    $_SESSION['message'] = 'Ungültige E-Mail-Adresse.';
  } else if (empty($_POST['letter_country']) && empty($_POST['package_country'])) {
    $_SESSION['message'] = 'Bitte zumindest ein Brief- oder Paket-Land auswählen!';
  } else if (!empty($_POST['letter_country']) && !in_array($_POST['letter_country'], $letter_countries, true)) {
    $_SESSION['message'] = 'Ungültiges Brief-Land.';
  } else if (!empty($_POST['package_country']) && !in_array($_POST['package_country'], $package_countries, true)) {
    $_SESSION['message'] = 'Ungültiges Paket-Land.';
  } else {
    $messages = write_to_receivers_file(trim($_POST['email']), $_POST['letter_country'], 'Brief', $messages);
    $messages = write_to_receivers_file(trim($_POST['email']), $_POST['package_country'], 'Paket', $messages);
    $_SESSION['message'] = implode('<br><br>', $messages);
  }
  header('Location: .');
  exit;
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>corona-post</title>
  <meta name="description" content="Wann kann mein Brief oder Paket mit der Post wieder ins Ausland verschickt werden?">
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
  <h2>Wann kann mein Brief oder Paket mit der Post wieder ins Ausland verschickt werden?</h2>
  <p>
    <a href="https://www.post.at/p/c/liefereinschraenkungen-coronavirus" target="_blank">
      Wegen Corona is teilweise gerade schwer mit der Post was zu verschicken.
    </a>
    Wenn du dich in die Mailingliste einträgst wirst du verständigt sobald's wieder geht.
    <b>Wenn sich dein Zielland in keiner der Listen unten befindet, ist es vermutlich nicht gesperrt.</b>
  </p>
  <?php if (isset($_SESSION['message'])): ?>
  <p style="background-color: blue; color: white; padding: 10px;"><b><?= $_SESSION['message'] ?></b></p>
  <?php unset($_SESSION['message']); endif ?>
  <form method="post">
    <input type="email" name="email" placeholder="Deine E-Mail bitte ..." autocomplete="email" required>
    <br>
    <select name="letter_country">
      <option value="">gesperrtes Land für Brief?</option>
      <?php foreach ($letter_countries as $country): ?>
      <option value="<?= $country ?>"><?= $country ?></option>
      <?php endforeach ?>
    </select>
    <br>
    <select name="package_country">
      <option value="">gesperrtes Land für Paket?</option>
      <?php foreach ($package_countries as $country): ?>
      <option value="<?= $country ?>"><?= $country ?></option>
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
</body>
</html>