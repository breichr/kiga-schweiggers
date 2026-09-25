<?php
declare(strict_types=1);
require __DIR__ . '/inc/functions.php';

$c   = load_content();
$a   = $c['allgemein'] ?? [];
$z   = $c['zeiten'] ?? [];
$k   = $c['konzept'] ?? [];
$g   = $c['gruppen'] ?? [];
$f   = $c['feste'] ?? [];
$h   = $c['geschichte'] ?? [];
$r   = $c['rechtliches'] ?? [];
$name = $a['name'] ?? 'Kindergarten';

// Unterseiten Impressum / Datenschutz
$seite = $_GET['seite'] ?? '';
if (!in_array($seite, ['impressum', 'datenschutz'], true)) {
    $seite = '';
}

// Aktuelles: neueste zuerst
$beitraege = array_values(array_filter($c['aktuelles']['beitraege'] ?? [], fn($b) => trim($b['titel'] ?? '') !== ''));
usort($beitraege, fn($x, $y) => strcmp($y['datum'] ?? '', $x['datum'] ?? ''));

// Termine: nur heute und später, aufsteigend
$heute   = date('Y-m-d');
$termine = array_values(array_filter($c['termine']['eintraege'] ?? [], fn($t) => ($t['datum'] ?? '') >= $heute && trim($t['titel'] ?? '') !== ''));
usort($termine, fn($x, $y) => strcmp($x['datum'], $y['datum']));

$gruppen = array_values(array_filter($g['gruppen'] ?? [], fn($x) => trim($x['name'] ?? '') !== ''));
$bilder  = array_values(array_filter($c['galerie']['bilder'] ?? [], fn($x) => !empty($x['bild'])));
$feste   = array_filter(array_map('trim', explode("\n", (string) ($f['feste'] ?? ''))));
$v = @filemtime(__DIR__ . '/assets/style.css') ?: 1;
?><!doctype html>
<html lang="de-AT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $seite ? e(ucfirst($seite)) . ' – ' : '' ?><?= e($name) ?></title>
<meta name="description" content="<?= e(strip_tags($a['untertitel'] ?? '')) ?> – <?= e(str_replace("\n", ', ', $a['adresse'] ?? '')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='14' fill='%23F2B632'/></svg>">
<link rel="stylesheet" href="assets/style.css?v=<?= $v ?>">
</head>
<body>
<a class="skip" href="#inhalt">Zum Inhalt springen</a>

<?php if (trim($a['hinweis'] ?? '') !== ''): ?>
<div class="notice" role="status"><div class="wrap"><?= format_text($a['hinweis']) ?></div></div>
<?php endif; ?>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="./">
      <span class="brand-dots" aria-hidden="true"><i style="--c:#F2B632"></i><i style="--c:#3F78D1"></i><i style="--c:#B8528F"></i><i style="--c:#D2453A"></i></span>
      <span class="brand-name"><?= e($name) ?></span>
    </a>
    <?php if (!$seite): ?>
    <button class="nav-toggle" aria-expanded="false" aria-controls="hauptnav">Menü</button>
    <nav id="hauptnav" class="nav" aria-label="Hauptmenü">
      <a href="#aktuelles">Aktuelles</a>
      <a href="#gruppen">Gruppen &amp; Team</a>
      <a href="#zeiten">Öffnungszeiten</a>
      <a href="#konzept">Konzept</a>
      <a href="#feste">Feste &amp; Projekte</a>
      <a href="#geschichte">Geschichte</a>
      <?php if ($bilder): ?><a href="#bilder">Bilder</a><?php endif; ?>
      <a href="#kontakt" class="nav-cta">Kontakt</a>
    </nav>
    <?php else: ?>
    <nav class="nav nav-simple"><a href="./">Zur Startseite</a></nav>
    <?php endif; ?>
  </div>
</header>

<main id="inhalt">
<?php if ($seite): ?>
  <section class="section">
    <div class="wrap prose-page">
      <h1><?= $seite === 'impressum' ? 'Impressum' : 'Datenschutz' ?></h1>
      <div class="prose"><?= format_text($r[$seite] ?? '') ?></div>
    </div>
  </section>
<?php else: ?>

  <!-- Begrüßung -->
  <section class="hero">
    <div class="wrap hero-grid">
      <div class="hero-text">
        <h1><?= e($name) ?></h1>
        <p class="hero-sub"><?= e($a['untertitel'] ?? '') ?></p>
        <div class="prose hero-welcome"><?= format_text($a['willkommen'] ?? '') ?></div>
        <dl class="facts">
          <div><dt>Geöffnet</dt><dd><?= strip_tags(format_text($z['oeffnungszeiten'] ?? ''), '<strong><br>') ?></dd></div>
          <?php if (!empty($a['telefon'])): ?>
          <div><dt>Telefon</dt><dd><a href="<?= e(tel_link($a['telefon'])) ?>"><?= e($a['telefon']) ?></a></dd></div>
          <?php endif; ?>
        </dl>
      </div>
      <div class="hero-media">
        <?php if (!empty($a['titelbild'])): ?>
          <img src="<?= img_url($a['titelbild']) ?>" alt="<?= e($name) ?>" width="900" height="700">
        <?php else: ?>
          <div class="hero-placeholder" aria-hidden="true">
            <?php foreach ($gruppen as $gr): ?><span style="--c:<?= e(color_hex($gr['farbe'] ?? '')) ?>"><?= symbol_svg($gr['symbol'] ?? '') ?></span><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($gruppen): ?>
    <div class="hooks" aria-label="Unsere Gruppen">
      <div class="wrap hooks-row">
        <?php foreach ($gruppen as $i => $gr): ?>
          <a class="hook" href="#gruppe-<?= $i ?>" style="--c:<?= e(color_hex($gr['farbe'] ?? '')) ?>">
            <span class="hook-sign"><?= symbol_svg($gr['symbol'] ?? '') ?></span>
            <span class="hook-name"><?= e_name($gr['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <!-- Aktuelles & Termine -->
  <section id="aktuelles" class="section">
    <div class="wrap news-grid">
      <div class="news">
        <h2>Aktuelles</h2>
        <?php if (!$beitraege): ?>
          <p class="muted">Gerade gibt es keine Neuigkeiten.</p>
        <?php endif; ?>
        <?php foreach (array_slice($beitraege, 0, 6) as $i => $b): ?>
          <article class="post<?= $i === 0 ? ' post-first' : '' ?>">
            <?php if (!empty($b['bild'])): ?>
              <img class="post-img zoomable" src="<?= img_url($b['bild']) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <div>
              <time datetime="<?= e($b['datum'] ?? '') ?>"><?= e(date_de($b['datum'] ?? '')) ?></time>
              <h3><?= e($b['titel']) ?></h3>
              <div class="prose"><?= format_text($b['text'] ?? '') ?></div>
            </div>
          </article>
        <?php endforeach; ?>
        <?php if (count($beitraege) > 6): ?>
          <details class="more-posts">
            <summary>Ältere Beiträge anzeigen</summary>
            <?php foreach (array_slice($beitraege, 6) as $b): ?>
              <article class="post">
                <div>
                  <time datetime="<?= e($b['datum'] ?? '') ?>"><?= e(date_de($b['datum'] ?? '')) ?></time>
                  <h3><?= e($b['titel']) ?></h3>
                  <div class="prose"><?= format_text($b['text'] ?? '') ?></div>
                </div>
              </article>
            <?php endforeach; ?>
          </details>
        <?php endif; ?>
      </div>
      <aside class="dates" aria-labelledby="termine-h">
        <h2 id="termine-h">Termine</h2>
        <?php if (!$termine): ?>
          <p class="muted">Aktuell sind keine Termine eingetragen.</p>
        <?php else: ?>
          <ol class="date-list">
            <?php foreach ($termine as $t): $ts = strtotime($t['datum']); ?>
              <li>
                <span class="cal" aria-hidden="true"><b><?= date('j', $ts) ?></b><?= e(['Jän','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'][(int) date('n', $ts) - 1]) ?></span>
                <span><strong><?= e($t['titel']) ?></strong><small><?= e(date_de($t['datum'], true)) ?><?= !empty($t['info']) ? ', ' . e($t['info']) : '' ?></small></span>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </aside>
    </div>
  </section>

  <!-- Gruppen & Team -->
  <section id="gruppen" class="section section-tint">
    <div class="wrap">
      <div class="section-head">
        <h2>Gruppen &amp; Team</h2>
        <div class="prose"><?= format_text($g['einleitung'] ?? '') ?></div>
      </div>
      <?php if (!empty($g['teamfoto'])): ?>
        <figure class="team-photo"><img class="zoomable" src="<?= img_url($g['teamfoto']) ?>" alt="Unser Team" loading="lazy"></figure>
      <?php endif; ?>
      <div class="groups">
        <?php foreach ($gruppen as $i => $gr): ?>
          <article class="group" id="gruppe-<?= $i ?>" style="--c:<?= e(color_hex($gr['farbe'] ?? '')) ?>">
            <span class="group-sign"><?= symbol_svg($gr['symbol'] ?? '') ?></span>
            <h3><?= e_name($gr['name']) ?></h3>
            <ul class="people">
              <?php foreach (split_lines($gr['personen'] ?? '') as [$pn, $role]): ?>
                <li><span class="person"><?= e($pn) ?></span><?php if ($role): ?><span class="role"><?= e($role) ?></span><?php endif; ?></li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Öffnungszeiten -->
  <section id="zeiten" class="section">
    <div class="wrap hours-grid">
      <div>
        <h2>Öffnungszeiten &amp; Beiträge</h2>
        <div class="prose big"><?= format_text($z['oeffnungszeiten'] ?? '') ?></div>
        <div class="prose"><?= format_text($z['betreuung'] ?? '') ?></div>
      </div>
      <?php if (!empty($z['tarife'])): ?>
      <div class="table-wrap">
        <table class="prices">
          <caption>Kosten pro Monat</caption>
          <tbody>
          <?php foreach ($z['tarife'] as $t): if (trim($t['bezeichnung'] ?? '') === '') continue; ?>
            <tr><th scope="row"><?= e($t['bezeichnung']) ?></th><td><?= e($t['preis'] ?? '') ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Konzept -->
  <section id="konzept" class="section section-tint">
    <div class="wrap concept">
      <div>
        <h2>Unser pädagogisches Konzept</h2>
        <p class="lead"><?= e($k['einleitung'] ?? '') ?></p>
        <div class="prose"><?= format_text($k['text'] ?? '') ?></div>
      </div>
      <div class="quotes">
        <?php foreach ($k['zitate'] ?? [] as $q): if (trim($q['zitat'] ?? '') === '') continue; ?>
          <figure class="quote">
            <blockquote><p>„<?= e(trim($q['zitat'], " \"„“")) ?>“</p></blockquote>
            <?php if (!empty($q['autor'])): ?><figcaption><?= e($q['autor']) ?></figcaption><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Feste & Projekte -->
  <section id="feste" class="section">
    <div class="wrap">
      <h2>Feste &amp; Projekte</h2>
      <?php if ($feste): ?>
        <h3 class="sub">Durchs Jahr feiern wir</h3>
        <ul class="chips">
          <?php foreach ($feste as $fe): ?><li><?= e($fe) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <div class="projects">
        <?php foreach ($f['projekte'] ?? [] as $p): if (trim($p['titel'] ?? '') === '') continue;
          $imgs = array_filter([$p['bild'] ?? '', $p['bild2'] ?? '', $p['bild3'] ?? '']); ?>
          <article class="project">
            <div class="project-text">
              <h3><?= e($p['titel']) ?></h3>
              <div class="prose"><?= format_text($p['text'] ?? '') ?></div>
            </div>
            <?php if ($imgs): ?>
              <div class="project-imgs n<?= count($imgs) ?>">
                <?php foreach ($imgs as $im): ?><img class="zoomable" src="<?= img_url($im) ?>" alt="" loading="lazy"><?php endforeach; ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Geschichte -->
  <section id="geschichte" class="section section-tint">
    <div class="wrap">
      <div class="section-head">
        <h2>Unsere Geschichte</h2>
        <div class="prose"><?= format_text($h['einleitung'] ?? '') ?></div>
      </div>
      <ol class="timeline">
        <?php foreach ($h['eintraege'] ?? [] as $en): if (trim(($en['titel'] ?? '') . ($en['jahr'] ?? '')) === '') continue; ?>
          <li>
            <span class="year"><?= e($en['jahr'] ?? '') ?></span>
            <div class="tl-body">
              <h3><?= e($en['titel'] ?? '') ?></h3>
              <div class="prose"><?= format_text($en['text'] ?? '') ?></div>
              <?php if (!empty($en['bild'])): ?>
                <figure><img class="zoomable" src="<?= img_url($en['bild']) ?>" alt="<?= e($en['bildtext'] ?? '') ?>" loading="lazy">
                  <?php if (!empty($en['bildtext'])): ?><figcaption><?= e($en['bildtext']) ?></figcaption><?php endif; ?>
                </figure>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <?php if ($bilder): ?>
  <!-- Bilder -->
  <section id="bilder" class="section">
    <div class="wrap">
      <h2>Bilder</h2>
      <div class="gallery">
        <?php foreach ($bilder as $b): ?>
          <figure><img class="zoomable" src="<?= img_url($b['bild']) ?>" alt="<?= e($b['beschreibung'] ?? '') ?>" loading="lazy">
            <?php if (!empty($b['beschreibung'])): ?><figcaption><?= e($b['beschreibung']) ?></figcaption><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
<?php endif; ?>
</main>

<footer id="kontakt" class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <h2>Kontakt</h2>
      <address>
        <strong><?= e($name) ?></strong><br>
        <?= nl2br(e($a['adresse'] ?? '')) ?>
      </address>
      <p><a href="https://www.openstreetmap.org/search?query=<?= rawurlencode(str_replace("\n", ' ', $a['adresse'] ?? '')) ?>" rel="noopener" target="_blank">Auf der Karte ansehen</a></p>
    </div>
    <div class="contact-links">
      <?php if (!empty($a['telefon'])): ?><a class="big-link" href="<?= e(tel_link($a['telefon'])) ?>"><?= e($a['telefon']) ?></a><?php endif; ?>
      <?php if (!empty($a['email'])): ?><a class="big-link mail" style="--len:<?= mb_strlen($a['email']) ?>" href="mailto:<?= e($a['email']) ?>"><?= e_email($a['email']) ?></a><?php endif; ?>
    </div>
  </div>
  <div class="wrap footer-meta">
    <span>© <?= date('Y') ?> <?= e($name) ?></span>
    <span class="footer-links">
      <a href="?seite=impressum">Impressum</a>
      <a href="?seite=datenschutz">Datenschutz</a>
      <a href="admin/" class="admin-link">Anmelden</a>
    </span>
  </div>
</footer>

<div class="lightbox" hidden><button class="lb-close" aria-label="Bild schließen">×</button><img alt=""></div>
<script src="assets/main.js?v=<?= $v ?>" defer></script>
</body>
</html>
