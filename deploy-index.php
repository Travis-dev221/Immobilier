<?php
// Patch index.html — remplace l'ancien modal admin par le nouveau
// Uploadez dans public_html/, ouvrez dans le nav, se supprime seul.

$file = __DIR__ . '/index.html';
if (!file_exists($file)) {
    die('<p style="font:1rem monospace;color:#f0b0b0;padding:20px">ERREUR: index.html introuvable dans ' . __DIR__ . '</p>');
}

$html = file_get_contents($file);
if ($html === false) {
    die('<p style="font:1rem monospace;color:#f0b0b0;padding:20px">ERREUR: impossible de lire index.html</p>');
}

// 1. Ajouter CSS des onglets (si pas déjà présent)
$cssMarker = '.bh-atab{';
$cssNew = '.bh-admin-hidden{display:none!important}
.bh-atab{background:none;border:none;border-bottom:2px solid transparent;font-family:inherit;font-size:.62rem;letter-spacing:.16em;text-transform:uppercase;color:#8a7d6a;padding:10px 16px;cursor:pointer;margin-bottom:-1px;white-space:nowrap}
.bh-atab:hover{color:#e8dcc8}
.bh-atab.bh-atab-on{color:#b8935a;border-bottom-color:#b8935a}
.bh-apane{display:none}
.bh-apane.bh-apane-on{display:block}
.bh-admin-btn-danger{background:transparent;border-color:#c45c5c;color:#c45c5c}
.bh-admin-btn-danger:hover{background:rgba(196,92,92,.1)}';

if (strpos($html, $cssMarker) === false) {
    $html = str_replace('.bh-admin-hidden{display:none!important}', $cssNew, $html);
}

// 2. Remplacer l'ancien modal par le nouveau
$oldModal = '<div class="bh-admin-overlay" id="bhAdminModal" role="dialog" aria-modal="true">
  <div class="bh-admin-box">
    <button type="button" class="bh-admin-close" id="bhAdminClose" aria-label="Fermer">&times;</button>
    <div id="bhLoginView">
      <p class="story-eye">Administration</p>
      <h2 class="bh-admin-title">Connexion</h2>
      <p class="bh-admin-sub">Connectez-vous pour g&eacute;rer les photos des villas.</p>
      <form id="bhLoginForm" action="#" onsubmit="return false;">
        <label class="bh-admin-label" for="bhPassword">Mot de passe</label>
        <input class="bh-admin-input" type="password" id="bhPassword" required placeholder="Mot de passe admin">
        <div style="margin-top:14px"><button type="button" class="bh-admin-btn" id="bhLoginBtn">Se connecter</button></div>
        <div id="bhLoginMsg" class="bh-admin-msg err bh-admin-hidden"></div>
      </form>
    </div>
    <div id="bhAdminView" class="bh-admin-hidden">
      <p class="story-eye">Connect&eacute;</p>
      <h2 class="bh-admin-title">G&eacute;rer les photos</h2>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px;align-items:end">
        <div style="flex:1;min-width:200px">
          <label class="bh-admin-label" for="bhVillaSelect">Villa</label>
          <select class="bh-admin-select" id="bhVillaSelect"></select>
        </div>
        <button type="button" class="bh-admin-btn" id="bhSaveBtn">Enregistrer</button>
        <button type="button" class="bh-admin-btn bh-admin-btn-ghost" id="bhLogoutBtn">D&eacute;connexion</button>
      </div>
      <div id="bhSaveMsg" class="bh-admin-msg bh-admin-hidden"></div>
      <div class="bh-admin-drop" id="bhDropZone">
        <input type="file" id="bhFileInput" accept="image/jpeg,image/png,image/webp" multiple>
        <p><strong>Cliquez ou glissez vos photos ici</strong></p>
      </div>
      <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <input class="bh-admin-input" type="text" id="bhUrlInput" placeholder="URL d\'image" style="flex:1">
        <button type="button" class="bh-admin-btn bh-admin-btn-ghost" id="bhAddUrlBtn">Ajouter URL</button>
      </div>
      <div class="bh-admin-grid" id="bhPhotoGrid"></div>
    </div>
  </div>
</div>';

$newModal = <<<'MODAL'
<div class="bh-admin-overlay" id="bhAdminModal" role="dialog" aria-modal="true">
  <div class="bh-admin-box">
    <button type="button" class="bh-admin-close" id="bhAdminClose" aria-label="Fermer">&times;</button>
    <div id="bhLoginView">
      <p class="story-eye">Administration · Baobab Horizon</p>
      <h2 class="bh-admin-title">Connexion</h2>
      <form id="bhLoginForm" action="#" onsubmit="return false;">
        <label class="bh-admin-label" for="bhPassword">Mot de passe</label>
        <input class="bh-admin-input" type="password" id="bhPassword" required placeholder="Mot de passe admin" autocomplete="current-password">
        <div style="margin-top:14px"><button type="button" class="bh-admin-btn" id="bhLoginBtn">Se connecter</button></div>
        <div id="bhLoginMsg" class="bh-admin-msg err bh-admin-hidden"></div>
      </form>
    </div>
    <div id="bhAdminView" class="bh-admin-hidden">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <h2 class="bh-admin-title" style="margin:0">Gestion des biens</h2>
        <button type="button" class="bh-admin-btn bh-admin-btn-ghost" id="bhLogoutBtn" style="font-size:.68rem;padding:8px 14px">Déconnexion</button>
      </div>
      <div style="display:flex;border-bottom:1px solid rgba(184,147,90,.2);margin-bottom:20px;flex-wrap:wrap">
        <button type="button" class="bh-atab bh-atab-on" data-tab="list">Mes biens</button>
        <button type="button" class="bh-atab" data-tab="edit">Modifier</button>
        <button type="button" class="bh-atab" data-tab="photos">Photos</button>
        <button type="button" class="bh-atab" data-tab="new">+ Nouveau</button>
      </div>
      <div id="bhSaveMsg" class="bh-admin-msg bh-admin-hidden"></div>
      <div id="bhTabList" class="bh-apane bh-apane-on">
        <div style="font-size:.78rem;color:#8a7d6a;margin-bottom:14px">Cliquez sur un bien pour le modifier.</div>
        <div id="bhBienList" style="display:flex;flex-direction:column;gap:3px"></div>
        <div style="margin-top:14px"><button type="button" class="bh-admin-btn" id="bhGoNew">+ Nouveau bien</button></div>
      </div>
      <div id="bhTabEdit" class="bh-apane">
        <p style="font-size:.78rem;color:#8a7d6a;margin-bottom:14px" id="bhEditSub">Sélectionnez un bien dans la liste.</p>
        <div id="bhEditMsg" class="bh-admin-msg bh-admin-hidden"></div>
        <label class="bh-admin-label">Type de bien</label>
        <select class="bh-admin-select" id="bhFType" style="margin-bottom:12px"><option value="vacances">🏖 Location vacances</option><option value="vente">🏠 Villa à vendre</option><option value="terrain">🌿 Terrain à vendre</option></select>
        <label class="bh-admin-label">Nom du bien</label>
        <input class="bh-admin-input" type="text" id="bhFName" placeholder="Ex: Villa Guédé Home" style="margin-bottom:12px">
        <label class="bh-admin-label">Zone / Localisation</label>
        <input class="bh-admin-input" type="text" id="bhFZone" placeholder="Ex: Nguerigne · Petite Côte" style="margin-bottom:12px">
        <label class="bh-admin-label">Description</label>
        <textarea class="bh-admin-input" id="bhFDesc" style="min-height:80px;resize:vertical;margin-bottom:12px" placeholder="Décrivez le bien..."></textarea>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
          <div><label class="bh-admin-label">Prix (0 = Sur demande)</label><input class="bh-admin-input" type="number" id="bhFPrice" min="0" value="0"></div>
          <div><label class="bh-admin-label">Unité de prix</label><input class="bh-admin-input" type="text" id="bhFPriceUnit" placeholder="FCFA · nuit"></div>
        </div>
        <label class="bh-admin-label">Note prix (optionnel)</label>
        <input class="bh-admin-input" type="text" id="bhFPriceNote" placeholder="Ex: Visite sur RDV" style="margin-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px">
          <div><label class="bh-admin-label">Chambres</label><input class="bh-admin-input" type="number" id="bhFBedrooms" min="0" value="0"></div>
          <div><label class="bh-admin-label">Salles de bain</label><input class="bh-admin-input" type="number" id="bhFBaths" min="0" value="0"></div>
          <div><label class="bh-admin-label">Personnes max</label><input class="bh-admin-input" type="number" id="bhFPersons" min="0" value="0"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
          <div><label class="bh-admin-label">Surface (valeur)</label><input class="bh-admin-input" type="text" id="bhFArea" placeholder="1 200"></div>
          <div><label class="bh-admin-label">Surface (label)</label><input class="bh-admin-input" type="text" id="bhFAreaLabel" placeholder="m² hab."></div>
        </div>
        <label class="bh-admin-label">Tags / Équipements (virgule)</label>
        <input class="bh-admin-input" type="text" id="bhFTags" placeholder="Piscine, Chef, Clim" style="margin-bottom:12px">
        <label class="bh-admin-label">Clé technique</label>
        <input class="bh-admin-input" type="text" id="bhFKey" placeholder="villa-guede" style="margin-bottom:4px">
        <small style="color:#8a7d6a;font-size:.65rem;display:block;margin-bottom:16px">⚠ Ne pas modifier la clé d'un bien existant</small>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button type="button" class="bh-admin-btn" id="bhSaveEdit">💾 Enregistrer</button>
          <button type="button" class="bh-admin-btn bh-admin-btn-danger" id="bhDeleteBtn" style="font-size:.68rem">Supprimer</button>
        </div>
      </div>
      <div id="bhTabPhotos" class="bh-apane">
        <p style="font-size:.78rem;color:#8a7d6a;margin-bottom:14px" id="bhPhotoSub">Sélectionnez un bien dans la liste.</p>
        <div id="bhPhotosMsg" class="bh-admin-msg bh-admin-hidden"></div>
        <div class="bh-admin-drop" id="bhDropZone">
          <input type="file" id="bhFileInput" accept="image/jpeg,image/png,image/webp" multiple>
          <p><strong>Glissez vos photos ici</strong> ou cliquez</p>
          <p style="font-size:.72rem;margin-top:6px;color:#8a7d6a">JPG · PNG · WEBP — max 8 Mo</p>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap">
          <input class="bh-admin-input" type="text" id="bhUrlInput" placeholder="URL d'image (https://...)" style="flex:1">
          <button type="button" class="bh-admin-btn bh-admin-btn-ghost" id="bhAddUrlBtn">Ajouter URL</button>
        </div>
        <div class="bh-admin-grid" id="bhPhotoGrid"></div>
        <div style="margin-top:14px"><button type="button" class="bh-admin-btn" id="bhSavePhotos">💾 Enregistrer les photos</button></div>
      </div>
      <div id="bhTabNew" class="bh-apane">
        <div id="bhNewMsg" class="bh-admin-msg bh-admin-hidden"></div>
        <label class="bh-admin-label">Clé technique (unique, minuscules-tirets)</label>
        <input class="bh-admin-input" type="text" id="bhNKey" placeholder="Ex: villa-ocean" style="margin-bottom:12px">
        <label class="bh-admin-label">Type de bien</label>
        <select class="bh-admin-select" id="bhNType" style="margin-bottom:12px"><option value="vacances">🏖 Location vacances</option><option value="vente">🏠 Villa à vendre</option><option value="terrain">🌿 Terrain à vendre</option></select>
        <label class="bh-admin-label">Nom du bien</label>
        <input class="bh-admin-input" type="text" id="bhNName" placeholder="Ex: Villa Océan" style="margin-bottom:12px">
        <label class="bh-admin-label">Zone / Localisation</label>
        <input class="bh-admin-input" type="text" id="bhNZone" placeholder="Ex: Saly · Bord de mer" style="margin-bottom:12px">
        <label class="bh-admin-label">Description</label>
        <textarea class="bh-admin-input" id="bhNDesc" style="min-height:80px;resize:vertical;margin-bottom:12px" placeholder="Décrivez le bien..."></textarea>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
          <div><label class="bh-admin-label">Prix (0 = Sur demande)</label><input class="bh-admin-input" type="number" id="bhNPrice" min="0" value="0"></div>
          <div><label class="bh-admin-label">Unité de prix</label><input class="bh-admin-input" type="text" id="bhNPriceUnit" placeholder="FCFA · nuit"></div>
        </div>
        <label class="bh-admin-label">Note prix</label>
        <input class="bh-admin-input" type="text" id="bhNPriceNote" style="margin-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px">
          <div><label class="bh-admin-label">Chambres</label><input class="bh-admin-input" type="number" id="bhNBedrooms" min="0" value="0"></div>
          <div><label class="bh-admin-label">Salles de bain</label><input class="bh-admin-input" type="number" id="bhNBaths" min="0" value="0"></div>
          <div><label class="bh-admin-label">Personnes</label><input class="bh-admin-input" type="number" id="bhNPersons" min="0" value="0"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
          <div><label class="bh-admin-label">Surface (valeur)</label><input class="bh-admin-input" type="text" id="bhNArea" placeholder="1 200"></div>
          <div><label class="bh-admin-label">Surface (label)</label><input class="bh-admin-input" type="text" id="bhNAreaLabel" placeholder="m²"></div>
        </div>
        <label class="bh-admin-label">Tags (virgule)</label>
        <input class="bh-admin-input" type="text" id="bhNTags" placeholder="Piscine, Chef" style="margin-bottom:16px">
        <button type="button" class="bh-admin-btn" id="bhCreateBtn">✅ Créer ce bien</button>
      </div>
    </div>
  </div>
</div>
MODAL;

// Essayer de remplacer l'ancien modal
$count = 0;

// Chercher le modal par son début uniquement (plus flexible)
$startTag = '<div class="bh-admin-overlay" id="bhAdminModal"';
$endTag = '</div>' . "\n" . "\n" . '<!-- NAV -->';
$endTag2 = '</div>' . "\n\n" . '<!-- NAV -->';

$pos = strpos($html, $startTag);
if ($pos !== false) {
    // Trouver la fin du modal (après les 3 divs fermants)
    $depth = 0;
    $i = $pos;
    $len = strlen($html);
    while ($i < $len) {
        if (substr($html, $i, 5) === '<div ') { $depth++; $i += 5; continue; }
        if (substr($html, $i, 4) === '<div') { $depth++; $i += 4; continue; }
        if (substr($html, $i, 6) === '</div>') {
            $depth--;
            if ($depth === 0) { $end = $i + 6; break; }
            $i += 6; continue;
        }
        $i++;
    }
    if (isset($end)) {
        $html = substr($html, 0, $pos) . $newModal . "\n" . substr($html, $end);
        $count = 1;
    }
}

if ($count === 0) {
    // Fallback : ajouter avant <!-- NAV -->
    $navPos = strpos($html, '<!-- NAV -->');
    if ($navPos === false) $navPos = strpos($html, '<nav>');
    if ($navPos !== false) {
        $html = substr($html, 0, $navPos) . $newModal . "\n" . substr($html, $navPos);
        $count = 1;
    }
}

if ($count === 0) {
    die('<p style="font:1rem monospace;color:#f0b0b0;padding:20px">ERREUR: impossible de trouver le modal dans index.html. Uploadez index.html manuellement.</p>');
}

$ok = file_put_contents($file, $html) !== false;
@unlink(__FILE__);

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="favicon.png"><meta charset=UTF-8><style>body{font-family:monospace;background:#0f0d0a;color:#e8dcc8;padding:24px}a{color:#b8935a}</style></head><body>';
if ($ok) {
    echo '<p style="color:#7fcf9a">✅ index.html mis à jour — modal admin complet injecté.</p>';
    echo '<p>Rechargez le site avec <strong>Ctrl+Shift+R</strong> puis cliquez Admin.</p>';
    echo '<p><a href="/">Aller sur le site →</a></p>';
} else {
    echo '<p style="color:#f0b0b0">❌ Erreur écriture index.html.</p>';
}
echo '<p style="color:#7fcf9a;font-size:.85rem">🗑 deploy-index.php supprimé.</p>';
echo '</body></html>';
