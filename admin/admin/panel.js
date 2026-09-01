(function(){
'use strict';

/* ─── URL API ─────────────────────────────────────────────── */
function apiUrl(p){
  var s=document.querySelector('script[src*="panel.js"]');
  if(s){try{return new URL('../api/'+p.replace(/^\//,''),new URL(s.getAttribute('src')||'admin/panel.js',location.href).href).href;}catch(e){}}
  return location.origin+location.pathname.replace(/[^/]*$/,'')+'api/'+p.replace(/^\//,'');
}
function api(p,o){
  return fetch(apiUrl(p),Object.assign({credentials:'same-origin'},o||{}))
    .then(function(r){return r.json().catch(function(){return{};}).then(function(j){if(!r.ok)throw new Error(j.error||'Erreur serveur');return j;});});
}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ─── STATE ───────────────────────────────────────────────── */
var DB={}, CK='', availability={}, reservations={}, currentMonth=new Date();

/* ─── CSS ─────────────────────────────────────────────────── */
var CSS=`
.bha-ov{position:fixed;inset:0;z-index:200000;background:rgba(8,6,4,.95);display:none;align-items:flex-start;justify-content:center;padding:16px;overflow-y:auto}
.bha-ov.open{display:flex}
.bha-box{position:relative;width:min(980px,100%);margin:auto;background:#1a1612;border:1px solid rgba(184,147,90,.28);font-family:'Jost',sans-serif;color:#e8dcc8}
.bha-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px 0;border-bottom:1px solid rgba(184,147,90,.15);flex-wrap:wrap;gap:8px}
.bha-logo{font-family:'Cormorant Garamond',serif;font-size:1.15rem;letter-spacing:.18em;text-transform:uppercase;color:#faf7f2}.bha-logo span{color:#b8935a}
.bha-hbtns{display:flex;align-items:center;gap:8px}
.bha-close{background:none;border:1px solid rgba(184,147,90,.35);color:#e8dcc8;width:32px;height:32px;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center}
.bha-close:hover{border-color:#b8935a;color:#b8935a}
.bha-tabs{display:flex;gap:0;padding:0 22px;border-bottom:1px solid rgba(184,147,90,.15);overflow-x:auto}
.bha-tab{background:none;border:none;border-bottom:2px solid transparent;font-family:inherit;font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;color:#8a7d6a;padding:11px 18px;cursor:pointer;white-space:nowrap;margin-bottom:-1px}
.bha-tab:hover{color:#e8dcc8}
.bha-tab.on{color:#b8935a;border-bottom-color:#b8935a}
.bha-pane{display:none;padding:22px}
.bha-pane.on{display:block}
.bha-msg{padding:10px 14px;margin-bottom:14px;font-size:.82rem;border-radius:1px;display:none}
.bha-msg.ok{background:rgba(90,158,111,.14);border:1px solid rgba(90,158,111,.4);color:#a8dab5}
.bha-msg.err{background:rgba(196,92,92,.14);border:1px solid rgba(196,92,92,.4);color:#f0b0b0}
.bha-section{margin-bottom:22px}
.bha-section-title{font-size:.6rem;letter-spacing:.26em;text-transform:uppercase;color:#b8935a;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid rgba(184,147,90,.15)}
.bha-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.bha-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.bha-full{grid-column:1/-1}
.bha-fg{display:flex;flex-direction:column;gap:6px}
.bha-label{font-size:.63rem;letter-spacing:.12em;text-transform:uppercase;color:#8a7d6a}
.bha-input,.bha-select,.bha-textarea{width:100%;padding:10px 12px;background:#2a2520;border:1px solid rgba(184,147,90,.22);color:#faf7f2;font-family:inherit;font-size:.86rem;outline:none}
.bha-input:focus,.bha-select:focus,.bha-textarea:focus{border-color:#b8935a}
.bha-textarea{min-height:85px;resize:vertical}
.bha-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 18px;border:1px solid #b8935a;background:#b8935a;color:#0f0d0a;font-family:inherit;font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;cursor:pointer;transition:.2s}
.bha-btn:hover{background:#d4ab72;border-color:#d4ab72}
.bha-btn.ghost{background:transparent;color:#e8dcc8;border-color:rgba(184,147,90,.4)}
.bha-btn.ghost:hover{background:rgba(184,147,90,.1);color:#faf7f2;border-color:#b8935a}
.bha-btn.danger{background:transparent;border-color:#c45c5c;color:#c45c5c}
.bha-btn.danger:hover{background:rgba(196,92,92,.1)}
.bha-btn.sm{padding:7px 11px;font-size:.6rem}
.bha-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px}
.bha-list{display:flex;flex-direction:column;gap:3px}
.bha-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 14px;background:#0f0d0a;border:1px solid rgba(184,147,90,.12);cursor:pointer;transition:.2s}
.bha-item:hover,.bha-item.on{border-color:rgba(184,147,90,.5);background:rgba(184,147,90,.05)}
.bha-item-name{font-size:.9rem;color:#faf7f2}
.bha-item-meta{font-size:.6rem;color:#8a7d6a;margin-top:3px}
.bha-badge{font-size:.5rem;letter-spacing:.15em;text-transform:uppercase;padding:2px 8px;border-radius:1px;display:inline-block}
.bha-bv{background:rgba(90,158,111,.18);color:#7fcf9a;border:1px solid rgba(90,158,111,.28)}
.bha-bvente{background:rgba(184,147,90,.18);color:#d4ab72;border:1px solid rgba(184,147,90,.28)}
.bha-bt{background:rgba(138,125,106,.18);color:#e8dcc8;border:1px solid rgba(138,125,106,.28)}
.bha-drop{border:2px dashed rgba(184,147,90,.3);padding:22px;text-align:center;color:#8a7d6a;cursor:pointer;margin:10px 0;transition:.2s;position:relative}
.bha-drop:hover,.bha-drop.over{border-color:#b8935a;background:rgba(184,147,90,.04)}
.bha-drop input{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:1}
.bha-pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin:12px 0}
.bha-photo{position:relative;aspect-ratio:4/3;overflow:hidden;background:#2a2520;border:1px solid rgba(184,147,90,.15)}
.bha-photo img{width:100%;height:100%;object-fit:cover;display:block}
.bha-photo-bar{position:absolute;bottom:0;left:0;right:0;display:flex;gap:4px;padding:5px;background:rgba(0,0,0,.8)}
.bha-photo-n{position:absolute;top:5px;left:5px;background:rgba(0,0,0,.65);color:#fff;font-size:.52rem;padding:2px 6px}
.bha-sep{height:1px;background:rgba(184,147,90,.12);margin:18px 0}
/* Calendar styles */
.bha-calendar{background:#0f0d0a;border:1px solid rgba(184,147,90,.22);padding:16px;border-radius:2px}
.bha-calendar-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.bha-calendar-month{font-size:.85rem;font-weight:500;color:#e8dcc8;text-transform:uppercase;letter-spacing:.12em}
.bha-calendar-nav{display:flex;gap:8px}
.bha-calendar-nav button{background:transparent;border:1px solid rgba(184,147,90,.4);color:#e8dcc8;width:32px;height:32px;cursor:pointer;font-size:1rem}
.bha-calendar-nav button:hover{border-color:#b8935a;color:#b8935a}
.bha-calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:8px}
.bha-calendar-day{aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#8a7d6a;text-transform:uppercase;letter-spacing:.1em}
.bha-calendar-date{aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:.8rem;background:#2a2520;border:1px solid rgba(184,147,90,.15);color:#e8dcc8;cursor:pointer;transition:.2s}
.bha-calendar-date:hover{border-color:#b8935a}
.bha-calendar-date.blocked{background:rgba(196,92,92,.3);border-color:#c45c5c;color:#f0b0b0}
.bha-calendar-date.reserved{background:rgba(184,147,90,.3);border-color:#b8935a}
.bha-calendar-date.today{border:2px solid #b8935a}
.bha-calendar-date.disabled{opacity:.3;cursor:not-allowed}
/* Request list */
.bha-request-item{padding:14px;background:#0f0d0a;border:1px solid rgba(184,147,90,.12);margin-bottom:8px}
.bha-request-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;flex-wrap:wrap;gap:8px}
.bha-request-name{font-size:.95rem;color:#faf7f2;font-weight:500}
.bha-request-status{font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;padding:3px 8px;border-radius:1px;background:rgba(138,125,106,.18);color:#e8dcc8;border:1px solid rgba(138,125,106,.28)}
.bha-request-status.ok{background:rgba(90,158,111,.18);color:#7fcf9a;border-color:rgba(90,158,111,.28)}
.bha-request-status.invoice{background:rgba(184,147,90,.22);color:#d4ab72;border-color:rgba(184,147,90,.38)}
.bha-request-details{font-size:.75rem;color:#8a7d6a;line-height:1.6}
.bha-request-link{display:block;margin-top:10px;padding:10px 12px;background:rgba(184,147,90,.08);border:1px solid rgba(184,147,90,.3);font-size:.72rem;color:#d4ab72;word-break:break-all;text-decoration:none;border-radius:2px}
.bha-request-link:hover{border-color:#b8935a;color:#e8c89a}
.bha-request-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
/* Modal accès client */
.bha-modal-access{position:fixed;inset:0;z-index:999999;display:flex;align-items:center;justify-content:center}
.bha-modal-access-overlay{position:absolute;inset:0;background:rgba(0,0,0,.82)}
.bha-modal-access-dialog{position:relative;max-width:620px;width:calc(100% - 40px);background:#1a1612;border:1px solid rgba(184,147,90,.35);box-shadow:0 30px 80px rgba(0,0,0,.6)}
.bha-modal-access-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(184,147,90,.15)}
.bha-modal-access-header > div:first-child{font-family:'Cormorant Garamond',Georgia,serif;font-size:1.25rem;color:#faf7f2;letter-spacing:.05em}
.bha-modal-access-close{background:transparent;border:0;color:#8a7d6a;font-size:1.6rem;cursor:pointer;line-height:1}
.bha-modal-access-close:hover{color:#e8dcc8}
.bha-modal-access-body{padding:22px 20px;color:#e8dcc8}
.bha-access-row{margin-bottom:12px;font-size:.8rem}
.bha-access-row label{display:block;font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;color:#8a7d6a;margin-bottom:4px}
.bha-access-row input,.bha-access-row textarea{width:100%;border:1px solid rgba(184,147,90,.25);background:rgba(255,255,255,.04);color:#faf7f2;padding:11px 13px;font:inherit;font-size:.82rem;outline:none;resize:vertical}
.bha-access-row input:focus,.bha-access-row textarea:focus{border-color:#b8935a}
.bha-access-url{display:flex;gap:8px}
.bha-access-url input{flex:1}
@media(max-width:600px){.bha-grid2,.bha-grid3{grid-template-columns:1fr!important}.bha-tab[data-tab="bhaTabEdit"],.bha-tab[data-tab="bhaTabNew"]{display:none}.bha-item [data-act="edit"],#bhaGoNew{display:none}.bha-ov{padding:8px}.bha-box{width:100%}.bha-head,.bha-tabs,.bha-pane{padding-left:12px;padding-right:12px}.bha-tabs{overflow-x:auto}.bha-tab{padding:10px 14px}.bha-section-title{margin-bottom:16px}.bha-input,.bha-select,.bha-textarea{padding:10px}.bha-btn{padding:10px 14px}}
`;

/* ─── HTML ────────────────────────────────────────────────── */
var HTML=`
<div class="bha-ov" id="bhaModal">
<div class="bha-box">

<div class="bha-head">
  <div class="bha-logo">Baobab <span>Admin</span></div>
  <div class="bha-hbtns">
    <a href="hotel_admin.php" class="bha-btn ghost sm" id="bhaHotelAdmin" style="display:none; text-decoration:none;" target="_blank">Gérer l'Hôtel</a>
    <button type="button" class="bha-btn ghost sm" id="bhaLogout" style="display:none">Déconnexion</button>
    <button type="button" class="bha-close" id="bhaClose">&times;</button>
  </div>
</div>

<!-- LOGIN -->
<div id="bhaLogin" style="padding:28px 22px">
  <div style="font-size:1.1rem;color:#faf7f2;margin-bottom:6px">Connexion</div>
  <div style="font-size:.8rem;color:#8a7d6a;margin-bottom:18px">Espace administration Baobab Horizon</div>
  <div class="bha-msg" id="bhaLoginMsg"></div>
  <div class="bha-fg" style="margin-bottom:14px; position:relative;">
    <label class="bha-label">Mot de passe</label>
    <div style="position:relative;">
      <input class="bha-input" type="password" id="bhaPassword" placeholder="Mot de passe admin" autocomplete="current-password" style="padding-right:36px;">
      <button type="button" id="bhaTogglePassword" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#8a7d6a; cursor:pointer; font-size:1.1rem; padding:0;" aria-label="Afficher le mot de passe">👁️</button>
    </div>
  </div>
  <button type="button" class="bha-btn" id="bhaLoginBtn">Se connecter</button>
  <div id="bhaForgotPwdContainer" style="display:none; margin-top:15px; text-align:center;">
    <a href="#" id="bhaForgotPwdLink" style="color:#b8935a; text-decoration:underline; font-size:0.8rem;">Mot de passe oublié ?</a>
  </div>
</div>

<!-- ADMIN -->
<div id="bhaAdmin" style="display:none">
  <div class="bha-tabs">
    <button class="bha-tab on" data-tab="bhaTabList">Mes biens</button>
    <button class="bha-tab" data-tab="bhaTabEdit">Modifier</button>
    <button class="bha-tab" data-tab="bhaTabPhotos">Photos</button>
    <button class="bha-tab" data-tab="bhaTabAvailability">📅 Réservations</button>
  </div>

  <!-- TAB LISTE -->
  <div class="bha-pane on" id="bhaTabList">
    <div class="bha-msg" id="bhaMsg"></div>
    <div style="font-size:.8rem;color:#8a7d6a;margin-bottom:14px">Cliquez sur un bien pour le modifier ou gérer ses photos.</div>
    <div class="bha-list" id="bhaList"></div>
  </div>

  <!-- TAB MODIFIER -->
  <div class="bha-pane" id="bhaTabEdit">
    <div class="bha-msg" id="bhaEditMsg"></div>
    <div style="font-size:.82rem;color:#8a7d6a;margin-bottom:18px" id="bhaEditSub">← Sélectionnez un bien dans "Mes biens"</div>      <div class="bha-section">
      <div class="bha-section-title">Informations générales</div>
      <div class="bha-grid2">
        <div class="bha-fg bha-full">
          <label class="bha-label">Type de bien</label>
          <select class="bha-select" id="bhaFType">
            <option value="vacances">🏖 Location vacances</option>
            <option value="vente">🏠 Villa à vendre</option>
            <option value="terrain">🌿 Terrain à vendre</option>
          </select>
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Section d'affichage</label>
          <select class="bha-select" id="bhaFSection">
            <option value="location">📍 Page Locations (Louer)</option>
            <option value="vente">🏠 Page Ventes (Acheter)</option>
            <option value="accueil">🏡 Page Accueil</option>
          </select>
        </div>
        <div class="bha-fg bha-full" style="flex-direction:row;align-items:center;gap:12px">
          <label class="bha-label" style="margin:0">Disponible</label>
          <input type="checkbox" id="bhaFAvailable" checked style="width:auto;height:auto">
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Nom du bien</label>
          <input class="bha-input" type="text" id="bhaFName" placeholder="Ex: Villa Guédé Home">
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Zone / Localisation</label>
          <input class="bha-input" type="text" id="bhaFZone" placeholder="Ex: Nguerigne · Petite Côte · 300m plage">
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Description complète</label>
          <textarea class="bha-textarea" id="bhaFDesc" placeholder="Décrivez le bien : emplacement, ambiance, points forts, ce qui le rend unique..."></textarea>
        </div>
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Prix</div>
      <div class="bha-grid3">
        <div class="bha-fg">
          <label class="bha-label">Montant (0 = Sur demande)</label>
          <input class="bha-input" type="number" id="bhaFPrice" min="0" placeholder="Ex: 440000">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Unité de prix</label>
          <input class="bha-input" type="text" id="bhaFPriceUnit" placeholder="FCFA · nuit">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Note (optionnel)</label>
          <input class="bha-input" type="text" id="bhaFPriceNote" placeholder="Ex: Visite sur RDV">
        </div>
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Caractéristiques</div>
      <div class="bha-grid3">
        <div class="bha-fg">
          <label class="bha-label">Chambres (0 = masqué)</label>
          <input class="bha-input" type="number" id="bhaFBedrooms" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Salles de bain (0 = masqué)</label>
          <input class="bha-input" type="number" id="bhaFBathrooms" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Personnes max (0 = masqué)</label>
          <input class="bha-input" type="number" id="bhaFPersons" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Surface / Spec (valeur)</label>
          <input class="bha-input" type="text" id="bhaFArea" placeholder="Ex: 1 200 ou 300m">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Label surface</label>
          <input class="bha-input" type="text" id="bhaFAreaLabel" placeholder="Ex: m² hab. ou Plage">
        </div>
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Équipements / Tags</div>
      <div class="bha-fg">
        <label class="bha-label">Tags séparés par virgule</label>
        <input class="bha-input" type="text" id="bhaFTags" placeholder="Ex: Piscine, Pool house, Climatisation, Chef privé, Jacuzzi">
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Identifiant technique</div>
      <div class="bha-fg">
        <label class="bha-label">Clé unique (lettres minuscules, chiffres, tirets)</label>
        <input class="bha-input" type="text" id="bhaFKey" placeholder="Ex: villa-guede">
        <small style="color:#8a7d6a;font-size:.65rem">⚠ Ne pas modifier la clé d'un bien existant</small>
      </div>
    </div>

    <div class="bha-row">
      <button type="button" class="bha-btn" id="bhaSaveEdit">💾 Enregistrer les modifications</button>
      <button type="button" class="bha-btn danger sm" id="bhaDelete">Supprimer ce bien</button>
    </div>
  </div>

  <!-- TAB PHOTOS -->
  <div class="bha-pane" id="bhaTabPhotos">
    <div class="bha-msg" id="bhaPhotoMsg"></div>
    <div style="font-size:.82rem;color:#8a7d6a;margin-bottom:16px" id="bhaPhotoSub">← Sélectionnez un bien dans "Mes biens"</div>
    <div class="bha-section">
      <div class="bha-section-title">Sélectionnez la catégorie</div>
      <div class="bha-fg">
        <select class="bha-select" id="bhaPhotoCategory">
          <option value="exterieur">Vue de l'extérieur</option>
          <option value="interieur">Vue de l'intérieur</option>
          <option value="chambres">Chambres</option>
        </select>
      </div>
    </div>
    <div class="bha-drop" id="bhaDrop">
      <input type="file" id="bhaFileInput" accept="image/jpeg,image/png,image/webp" multiple>
      <div style="font-size:.9rem;font-weight:500;color:#e8dcc8;margin-bottom:6px">📁 Glissez vos photos ici ou cliquez</div>
      <div style="font-size:.74rem">JPG, PNG, WEBP — max 8 Mo par fichier</div>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:8px">
      <input class="bha-input" type="text" id="bhaUrlInput" placeholder="Ou coller une URL d'image (https://...)" style="flex:1">
      <button type="button" class="bha-btn ghost sm" id="bhaAddUrl">Ajouter URL</button>
    </div>
    <div id="bhaPhotoCategories"></div>
    <div class="bha-row">
      <button type="button" class="bha-btn" id="bhaSavePhotos">💾 Enregistrer les photos</button>
    </div>
  </div>

  <!-- TAB RESERVATIONS & AVAILABILITY -->
  <div class="bha-pane" id="bhaTabAvailability">
    <div class="bha-msg" id="bhaAvailabilityMsg"></div>
    <div style="font-size:.82rem;color:#8a7d6a;margin-bottom:16px" id="bhaAvailabilitySub">← Sélectionnez un bien dans "Mes biens"</div>
    
    <div class="bha-section">
      <div class="bha-section-title">Gérer la disponibilité</div>
      <div class="bha-fg bha-full" style="margin-bottom:12px">
        <label class="bha-label">Bloquer une période</label>
        <div class="bha-grid2">
          <div class="bha-fg">
            <input class="bha-input" type="date" id="bhaBlockStart">
          </div>
          <div class="bha-fg">
            <input class="bha-input" type="date" id="bhaBlockEnd">
          </div>
        </div>
        <div class="bha-row">
          <button type="button" class="bha-btn" id="bhaBlockDates">🔒 Bloquer la période</button>
          <button type="button" class="bha-btn ghost sm" id="bhaRefreshAvailability">🔄 Rafraîchir</button>
        </div>
      </div>
      
      <div class="bha-calendar" id="bhaCalendar"></div>
      <div style="font-size:.7rem;color:#8a7d6a;margin-top:8px;display:flex;gap:16px">
        <span style="display:flex;align-items:center;gap:6px"><span style="width:12px;height:12px;background:rgba(196,92,92,.3);border:1px solid #c45c5c"></span>Bloqué</span>
        <span style="display:flex;align-items:center;gap:6px"><span style="width:12px;height:12px;background:rgba(184,147,90,.3);border:1px solid #b8935a"></span>Réservé</span>
      </div>
    </div>
    
    <div class="bha-section">
      <div class="bha-section-title">📥 Demandes de réservation (en attente)</div>
      <div id="bhaRequestsList"></div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">✅ Réservations validées</div>
      <div id="bhaValidatedList"></div>
    </div>
  </div>

  <!-- MODALE LIEN ACCÈS CLIENT (après validation) -->
  <div class="bha-modal-access" id="bhaAccessModal" style="display:none">
    <div class="bha-modal-access-overlay"></div>
    <div class="bha-modal-access-dialog">
      <div class="bha-modal-access-header">
        <div>🎉 Réservation validée</div>
        <button type="button" class="bha-modal-access-close" id="bhaAccessClose" aria-label="Fermer">×</button>
      </div>
      <div class="bha-modal-access-body" id="bhaAccessBody"></div>
    </div>
  </div>
</div>

</div><!-- bhaAdmin -->
</div></div>`;

/* ─── INIT ────────────────────────────────────────────────── */
function init(){
  if(document.getElementById('bhaModal')) return;
  var st=document.createElement('style'); st.textContent=CSS; document.head.appendChild(st);
  document.body.insertAdjacentHTML('beforeend',HTML);

  /* open */
  document.querySelectorAll('.nav-admin-btn').forEach(function(b){
    b.addEventListener('click',function(e){ e.preventDefault(); openModal(); });
  });
  /* close */
  document.getElementById('bhaClose').addEventListener('click',closeModal);
  document.getElementById('bhaModal').addEventListener('click',function(e){ if(e.target===this)closeModal(); });

  /* tabs */
  document.querySelectorAll('.bha-tab[data-tab]').forEach(function(t){
    t.addEventListener('click',function(){ showTab(t.dataset.tab); });
  });

  /* login */
  document.getElementById('bhaLoginBtn').addEventListener('click',doLogin);
  document.getElementById('bhaPassword').addEventListener('keydown',function(e){ if(e.key==='Enter')doLogin(); });
  
  var togglePwdBtn = document.getElementById('bhaTogglePassword');
  if(togglePwdBtn) {
    togglePwdBtn.addEventListener('click',function(){
      var pwdInput = document.getElementById('bhaPassword');
      if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        this.textContent = '🔒';
      } else {
        pwdInput.type = 'password';
        this.textContent = '👁️';
      }
    });
  }
  
  var forgotPwdLink = document.getElementById('bhaForgotPwdLink');
  if(forgotPwdLink) {
    forgotPwdLink.addEventListener('click', function(e) {
      e.preventDefault();
      alert("Veuillez contacter l'administrateur principal (Dani) au +221 78 014 09 42 pour réinitialiser le mot de passe.");
    });
  }

  /* logout */
  document.getElementById('bhaLogout').addEventListener('click',function(){
    api('/logout.php',{method:'POST'}).then(function(){
      DB={}; CK=''; availability={}; reservations={};
      document.getElementById('bhaAdmin').style.display='none';
      document.getElementById('bhaLogin').style.display='block';
      document.getElementById('bhaLogout').style.display='none';
      var bhaHotelAdmin = document.getElementById('bhaHotelAdmin');
      if(bhaHotelAdmin) bhaHotelAdmin.style.display='none';
      document.getElementById('bhaPassword').value='';
    });
  });

  /* save edit */
  document.getElementById('bhaSaveEdit').addEventListener('click',doSaveEdit);

  /* delete */
  document.getElementById('bhaDelete').addEventListener('click',function(){
    if(!CK) return;
    if(!confirm('Supprimer "'+((DB[CK]&&DB[CK].name)||CK)+'" ? Irréversible.')) return;
    delete DB[CK]; CK='';
    saveAll(function(){ renderList(); showTab('bhaTabList'); msg('bhaMsg','Bien supprimé.',true); refresh(); });
  });

  /* photos — drop / clic */
  var drop=document.getElementById('bhaDrop');
  var fi=document.getElementById('bhaFileInput');
  /* L'input est en position absolue transparent sur toute la zone.
     Le clic natif ouvre le sélecteur. Le drop est intercepté en phase capture. */
  fi.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2;';
  fi.addEventListener('change',function(){
    if(!CK){msg('bhaPhotoMsg','Sélectionnez un bien d\'abord.',false);return;}
    if(fi.files && fi.files.length) upload(fi.files);
    fi.value='';
  });
  // Drag & drop capturé avant l'input
  drop.addEventListener('dragenter',function(e){e.preventDefault();drop.classList.add('over');},true);
  drop.addEventListener('dragover', function(e){e.preventDefault();},true);
  drop.addEventListener('dragleave',function(e){
    if(!drop.contains(e.relatedTarget))drop.classList.remove('over');
  },true);
  drop.addEventListener('drop',function(e){
    e.preventDefault();e.stopPropagation();drop.classList.remove('over');
    if(!CK){msg('bhaPhotoMsg','Sélectionnez un bien d\'abord.',false);return;}
    var files=e.dataTransfer&&e.dataTransfer.files;
    if(files&&files.length)upload(files);
  },true);

  /* add url */
  document.getElementById('bhaAddUrl').addEventListener('click',function(){
    if(!CK){msg('bhaPhotoMsg','Sélectionnez un bien.',false);return;}
    var u=document.getElementById('bhaUrlInput').value.trim();
    if(!u){msg('bhaPhotoMsg','Entrez une URL valide.',false);return;}
    if(u.indexOf('http')!==0){msg('bhaPhotoMsg','L\'URL doit commencer par http:// ou https://',false);return;}
    var category=document.getElementById('bhaPhotoCategory').value;
    ensurePhotoStructure();
    if(!DB[CK].photos[category]) DB[CK].photos[category]=[];
    DB[CK].photos[category].push(u);
    if(!DB[CK].images) DB[CK].images=[];
    DB[CK].images.push(u);
    document.getElementById('bhaUrlInput').value='';
    renderPhotos();
    saveAllWithMsg('bhaPhotoMsg','✅ URL ajoutée et enregistrée.',true,function(){ renderList(); refresh(); });
  });

  /* save photos */
  document.getElementById('bhaSavePhotos').addEventListener('click',function(){
    saveAllWithMsg('bhaPhotoMsg','✅ Photos enregistrées.',true,function(){ renderList(); refresh(); });
  });
  
  /* availability / reservations */
  document.getElementById('bhaBlockDates').addEventListener('click',blockDateRange);
  document.getElementById('bhaRefreshAvailability').addEventListener('click',function(){
    loadAvailability();
    loadReservations();
  });

  checkSession();
}

/* ─── OPEN / CLOSE ────────────────────────────────────────── */
function openModal(){
  document.getElementById('bhaModal').classList.add('open');
  document.body.style.overflow='hidden';
  checkSession();
}
function closeModal(){
  document.getElementById('bhaModal').classList.remove('open');
  document.body.style.overflow='';
}

/* ─── TABS ────────────────────────────────────────────────── */
function showTab(id){
  document.querySelectorAll('.bha-pane').forEach(function(p){ p.classList.remove('on'); });
  document.querySelectorAll('.bha-tab').forEach(function(t){ t.classList.remove('on'); });
  var pane=document.getElementById(id); if(pane)pane.classList.add('on');
  var tab=document.querySelector('.bha-tab[data-tab="'+id+'"]'); if(tab)tab.classList.add('on');
}

/* ─── SESSION / LOGIN ─────────────────────────────────────── */
function checkSession(){
  api('/login.php').then(function(r){ if(r.logged_in)showAdmin(); }).catch(function(){});
}
function doLogin(){
  var pw=document.getElementById('bhaPassword').value;
  var msgEl=document.getElementById('bhaLoginMsg');
  if(msgEl) msgEl.style.display='none';
  var forgotContainer = document.getElementById('bhaForgotPwdContainer');
  if(forgotContainer) forgotContainer.style.display='none';
  
  api('/login.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:pw})})
    .then(function(){ showAdmin(); })
    .catch(function(e){
      msg('bhaLoginMsg',e.message,false);
      if(e.message.indexOf('bloqué')!==-1) {
        document.getElementById('bhaPassword').disabled = true;
        document.getElementById('bhaLoginBtn').disabled = true;
        if(forgotContainer) forgotContainer.style.display = 'block';
      }
    });
}
function showAdmin(){
  document.getElementById('bhaLogin').style.display='none';
  document.getElementById('bhaAdmin').style.display='block';
  document.getElementById('bhaLogout').style.display='inline-flex';
  var bhaHotelAdmin = document.getElementById('bhaHotelAdmin');
  if(bhaHotelAdmin) bhaHotelAdmin.style.display='inline-flex';
  api('/properties.php').then(function(d){ DB=d; renderList(); }).catch(function(e){ msg('bhaMsg',e.message,false); });
  loadAvailability();
  loadReservations();
}

/* ─── LISTE ───────────────────────────────────────────────── */
function getTotalPhotos(v){
  if(v.photos){
    return (v.photos.exterieur||[]).length + (v.photos.interieur||[]).length + (v.photos.chambres||[]).length;
  } else if(v.images){
    return v.images.length;
  }
  return 0;
}

function renderList(){
  var el=document.getElementById('bhaList');
  if(!el) return;
  var keys=Object.keys(DB);
  if(!keys.length){ el.innerHTML='<p style="color:#8a7d6a;padding:10px">Aucun bien. Créez-en un.</p>'; return; }
  el.innerHTML=keys.map(function(k){
    var v=DB[k];
    var bc=v.type==='vacances'?'bha-bv':v.type==='vente'?'bha-bvente':'bha-bt';
    var badge='<span class="bha-badge '+bc+'">'+esc(v.type||'')+'</span>';
    var sectionIcon=v.section==='accueil'?'🏡':v.section==='vente'?'🏠':'📍';
    var availDot=v.available!==false
      ? '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:rgba(90,158,111,.8);margin-right:4px"></span>'
      : '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#c45c5c;margin-right:4px"></span>';
    var prix=v.price?Number(v.price).toLocaleString('fr-FR')+' '+(v.priceUnit||''):'Sur demande';
    var totalPhotos=getTotalPhotos(v);
    return '<a href="#" class="bha-item'+(k===CK?' on':'')+'" data-key="'+esc(k)+'" style="text-decoration:none;color:inherit">'
      +'<div style="flex:1"><div class="bha-item-name">'+availDot+esc(v.name)+'</div>'
      +'<div class="bha-item-meta">'+badge+' '+sectionIcon+' '+esc(v.zone||'')+' · '+esc(prix)+'</div></div>'
      +'<div style="display:flex;gap:6px;flex-shrink:0" onclick="event.stopPropagation()">'
      +'<button type="button" class="bha-btn ghost sm" data-act="edit" data-key="'+esc(k)+'">Modifier</button>'
      +'<button type="button" class="bha-btn ghost sm" data-act="photos" data-key="'+esc(k)+'">Photos ('+totalPhotos+')</button>'
      +(v.type==='vacances'?'<button type="button" class="bha-btn ghost sm" data-act="reservations" data-key="'+esc(k)+'">📅 Réservations</button>':'')
      +'</div></a>';
  }).join('');
  el.querySelectorAll('[data-act]').forEach(function(b){
    b.addEventListener('click',function(e){
      e.stopPropagation();
      select(b.dataset.key);
      if(b.dataset.act==='edit'){
        showTab('bhaTabEdit');
      } else if(b.dataset.act==='photos'){
        showTab('bhaTabPhotos');
      } else if(b.dataset.act==='reservations'){
        showTab('bhaTabAvailability');
      }
    });
  });
  el.querySelectorAll('.bha-item').forEach(function(i){
    i.addEventListener('click',function(e){
      e.preventDefault();
      select(i.dataset.key); showTab('bhaTabPhotos');
    });
  });
}

/* ─── SELECT ──────────────────────────────────────────────── */
function select(k){
  CK=k; var v=DB[k]; if(!v) return;
  var s=function(id,val){ var e=document.getElementById(id); if(e)e.value=val||''; };
  s('bhaFType',v.type||'vacances');
  s('bhaFSection',v.section||(v.type==='vacances'?'location':'vente'));
  var availEl=document.getElementById('bhaFAvailable');
  if(availEl) availEl.checked=v.available!==false;
  s('bhaFName',v.name);
  s('bhaFZone',v.zone);
  s('bhaFPrice',v.price||0);
  s('bhaFPriceUnit',v.priceUnit);
  s('bhaFPriceNote',v.priceNote);
  s('bhaFBedrooms',v.bedrooms||0);
  s('bhaFBathrooms',v.bathrooms||0);
  s('bhaFPersons',v.persons||0);
  s('bhaFArea',v.area);
  s('bhaFAreaLabel',v.areaLabel);
  s('bhaFTags',(v.tags||[]).join(', '));
  s('bhaFDesc',v.description);
  s('bhaFKey',k);
  var sub=document.getElementById('bhaEditSub');
  if(sub)sub.textContent='Modification : '+v.name+' ('+v.type+')';
  var ps=document.getElementById('bhaPhotoSub');
  if(ps)ps.textContent='Photos de : '+v.name;
  var as=document.getElementById('bhaAvailabilitySub');
  if(as)as.textContent='Réservations pour : '+v.name;
  renderPhotos();
  renderList();
  renderCalendar();
  renderRequests();
}

/* ─── BUILD OBJET ─────────────────────────────────────────── */
function build(px){
  var g=function(id){ var e=document.getElementById(px+id); return e?e.value:''; };
  var v = DB[CK] || {};
  // Reconstruire images[] depuis photos{} pour que la couverture fonctionne
  var photos = v.photos || {exterieur:[],interieur:[],chambres:[]};
  var flatImages = [];
  ['exterieur','interieur','chambres'].forEach(function(c){
    (photos[c]||[]).forEach(function(u){ if(flatImages.indexOf(u)===-1) flatImages.push(u); });
  });
  // Si photos{} vide, conserver images[] existant
  if(flatImages.length===0) flatImages = (v.images||[]).slice();

  var availEl=document.getElementById(px+'Available');
  var available = availEl ? availEl.checked : true;

  return {
    type:        g('Type')||'vacances',
    section:     g('Section')||(g('Type')==='vacances'?'location':'vente'),
    name:        g('Name').trim(),
    zone:        g('Zone').trim(),
    price:       parseInt(g('Price'))||0,
    priceUnit:   g('PriceUnit').trim(),
    priceNote:   g('PriceNote').trim(),
    description: g('Desc').trim(),
    bedrooms:    parseInt(g('Bedrooms'))||0,
    bathrooms:   parseInt(g('Bathrooms'))||0,
    persons:     parseInt(g('Persons'))||0,
    area:        g('Area').trim(),
    areaLabel:   g('AreaLabel').trim(),
    tags:        g('Tags').split(',').map(function(t){return t.trim();}).filter(Boolean),
    available:   available,
    images:      flatImages,
    photos:      photos
  };
}

/* ensureImages — compatibilité avec doSaveEdit */
function ensureImages(){
  if(!CK || !DB[CK]) return [];
  var v = DB[CK];
  var photos = v.photos || {};
  var merged = [];
  ['exterieur','interieur','chambres'].forEach(function(c){
    (photos[c]||[]).forEach(function(u){ if(merged.indexOf(u)===-1) merged.push(u); });
  });
  return merged.length ? merged : (v.images||[]).slice();
}

/* ─── SAVE EDIT ───────────────────────────────────────────── */
function doSaveEdit(){
  var oldKey=CK;
  var newKey=(document.getElementById('bhaFKey').value||'').trim().toLowerCase();
  if(!newKey||!/^[a-z0-9_-]+$/.test(newKey)){msg('bhaEditMsg','Clé invalide (minuscules, chiffres, tirets)',false);return;}
  if(!(document.getElementById('bhaFName').value||'').trim()){msg('bhaEditMsg','Le nom est obligatoire',false);return;}
  var updated=build('bhaF');
  if(newKey!==oldKey){delete DB[oldKey]; CK=newKey;}
  DB[newKey]=updated;
  saveAllWithMsg('bhaEditMsg','✅ Bien enregistré.',true,function(){ renderList(); refresh(); });
}

/* ─── CREATE ──────────────────────────────────────────────── */
function doCreate(){
  var key=(document.getElementById('bhaNKey').value||'').trim().toLowerCase();
  if(!key||!/^[a-z0-9_-]+$/.test(key)){msg('bhaNewMsg','Clé invalide',false);return;}
  if(DB[key]){msg('bhaNewMsg','Cette clé existe déjà.',false);return;}
  if(!(document.getElementById('bhaNName').value||'').trim()){msg('bhaNewMsg','Le nom est obligatoire',false);return;}
  var obj=build('bhaN');
  obj.photos={exterieur:[],interieur:[],chambres:[]};
  DB[key]=obj; CK=key;
  saveAll(function(){
    renderList(); select(key); showTab('bhaTabPhotos');
    msg('bhaPhotoMsg','✅ Bien créé ! Ajoutez maintenant des photos.',true);
  });
}

/* ─── PHOTOS ──────────────────────────────────────────────── */
var CATEGORIES=[
  {key:'exterieur',label:'Vue de l\'extérieur'},
  {key:'interieur',label:'Vue de l\'intérieur'},
  {key:'chambres',label:'Chambres'}
];

function ensurePhotoStructure(){
  if(!DB[CK]) return;
  // Garantir que photos{} existe avec les 3 catégories
  if(!DB[CK].photos || typeof DB[CK].photos !== 'object' || Array.isArray(DB[CK].photos)){
    DB[CK].photos = {exterieur:[], interieur:[], chambres:[]};
  }
  ['exterieur','interieur','chambres'].forEach(function(c){
    if(!Array.isArray(DB[CK].photos[c])) DB[CK].photos[c]=[];
  });
  // Garantir que images[] existe (tableau plat pour couverture)
  if(!Array.isArray(DB[CK].images)) DB[CK].images=[];
  // Si images[] contient des urls mais photos{} est vide → migration vers exterieur
  var totalPhotos = DB[CK].photos.exterieur.length + DB[CK].photos.interieur.length + DB[CK].photos.chambres.length;
  if(totalPhotos === 0 && DB[CK].images.length > 0){
    DB[CK].photos.exterieur = DB[CK].images.slice();
  }
}

function renderPhotos(){
  var container=document.getElementById('bhaPhotoCategories');
  if(!container) return;
  ensurePhotoStructure();
  var photos=DB[CK].photos;
  container.innerHTML=CATEGORIES.map(function(cat){
    var imgs=photos[cat.key]||[];
    var imgsHtml=imgs.map(function(url,i){
      // Construire l'URL absolue pour les chemins relatifs (ex: images/villa/photo.jpg)
      var src=url.indexOf('http')===0 ? url : apiUrl('').replace(/\/api\/?$/,'/')+url;
      return '<div class="bha-photo" data-cat="'+cat.key+'" data-idx="'+i+'">'
        +'<span class="bha-photo-n">'+(i+1)+'</span>'
        +'<img src="'+esc(src)+'" alt="" onerror="this.style.opacity=\'.25\'">'
        +'<div class="bha-photo-bar">'
        +'<button type="button" class="bha-btn ghost sm" data-act="up"'+(i===0?' disabled':'')+'>↑</button>'
        +'<button type="button" class="bha-btn ghost sm" data-act="down"'+(i===imgs.length-1?' disabled':'')+'>↓</button>'
        +'<select class="bha-select" data-act="move" style="padding:2px 6px;font-size:.65rem;min-width:110px">'+
          CATEGORIES.map(function(c){return '<option value="'+c.key+'"'+(c.key===cat.key?' selected':'')+'>'+c.label+'</option>'}).join('')+
        '</select>'
        +'<button type="button" class="bha-btn danger sm" data-act="del">✕</button>'
        +'</div></div>';
    }).join('');
    return '<div class="bha-section">'
      +'<div class="bha-section-title">'+cat.label+' ('+imgs.length+')</div>'
      +'<div class="bha-pgrid">'+imgsHtml+'</div>'
      +'</div>';
  }).join('');
  
  container.querySelectorAll('button, select').forEach(function(el){
    el.addEventListener('click',function(e){
      if(e.target.tagName!=='BUTTON') return;
      handlePhotoAction(e.target);
    });
    el.addEventListener('change',function(e){
      if(e.target.tagName!=='SELECT' || !e.target.dataset.act || e.target.dataset.act!=='move') return;
      handlePhotoAction(e.target);
    });
  });
}

function handlePhotoAction(el){
  var photo=el.closest('.bha-photo');
  var idx=+photo.dataset.idx;
  var cat=photo.dataset.cat;
  var act=el.dataset.act;
  var list=DB[CK].photos[cat];
  
  if(act==='up'&&idx>0){
    var t=list[idx-1];list[idx-1]=list[idx];list[idx]=t;
    renderPhotos();
  } else if(act==='down'&&idx<list.length-1){
    var t2=list[idx+1];list[idx+1]=list[idx];list[idx]=t2;
    renderPhotos();
  } else if(act==='del'&&confirm('Supprimer cette photo ?')){
    var url=list.splice(idx,1)[0];
    renderPhotos();
    renderList();
    if(url.indexOf('images/')===0) api('/delete-image.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url:url})}).catch(function(){});
  } else if(act==='move'){
    var newCat=el.value;
    if(newCat!==cat){
      var url=list.splice(idx,1)[0];
      if(!DB[CK].photos[newCat]) DB[CK].photos[newCat]=[];
      DB[CK].photos[newCat].push(url);
      renderPhotos();
    }
  }
}

function upload(files){
  if(!CK){ msg('bhaPhotoMsg','Sélectionnez un bien d\'abord.',false); return; }
  var category = document.getElementById('bhaPhotoCategory').value || 'exterieur';
  var total = files.length;
  var done  = 0;
  var errs  = [];

  msg('bhaPhotoMsg', '⏳ Upload en cours (0 / '+total+')…', true);

  function uploadOne(f){
    var fd = new FormData();
    fd.append('villa',    CK);
    fd.append('photo',    f);
    fd.append('category', category);
    return fetch(apiUrl('upload.php'), {method:'POST', credentials:'same-origin', body:fd})
      .then(function(r){ return r.json().then(function(j){ if(!r.ok) throw new Error(j.error||'Erreur serveur'); return j; }); })
      .then(function(j){
        done++;
        // Mettre à jour DB local pour affichage immédiat
        ensurePhotoStructure();
        var cat = j.category || category;
        if(!DB[CK].photos[cat]) DB[CK].photos[cat]=[];
        // Éviter les doublons
        if(DB[CK].photos[cat].indexOf(j.url) === -1) DB[CK].photos[cat].push(j.url);
        if(DB[CK].images.indexOf(j.url) === -1)      DB[CK].images.push(j.url);
        msg('bhaPhotoMsg', '⏳ Upload en cours ('+done+' / '+total+')…', true);
      })
      .catch(function(e){ done++; errs.push(f.name+' : '+e.message); });
  }

  // Enchaîner les uploads un par un pour éviter les timeouts
  var chain = Promise.resolve();
  for(var i=0; i<files.length; i++){
    (function(f){ chain = chain.then(function(){ return uploadOne(f); }); })(files[i]);
  }
  chain.then(function(){
    renderPhotos();
    renderList();
    if(errs.length){
      msg('bhaPhotoMsg', '⚠️ '+errs.join(' | '), false);
    } else {
      // Le serveur a déjà mis à jour properties.json — recharger pour sync
      api('/properties.php').then(function(d){
        // Garder les données à jour
        Object.keys(d).forEach(function(k){ DB[k]=d[k]; });
        renderPhotos();
        renderList();
        msg('bhaPhotoMsg', '✅ '+total+' photo(s) uploadée(s) dans "'+category+'"', true);
      }).catch(function(){
        msg('bhaPhotoMsg', '✅ '+total+' photo(s) uploadée(s).', true);
      });
    }
  });
}

/* ─── SAVE ALL ────────────────────────────────────────────── */
function saveAll(cb){
  saveAllWithMsg('bhaMsg', null, true, cb);
}

function saveAllWithMsg(msgId, successText, isOk, cb){
  var payload={};
  Object.keys(DB).forEach(function(k){
    var v=Object.assign({},DB[k]);
    delete v._migrated;
    // S'assurer que images[] est toujours à jour avant envoi
    if(v.photos){
      var flat=[];
      ['exterieur','interieur','chambres'].forEach(function(c){
        (v.photos[c]||[]).forEach(function(u){ if(flat.indexOf(u)===-1) flat.push(u); });
      });
      if(flat.length) v.images=flat;
    }
    // Garantir que images[] existe toujours
    if(!Array.isArray(v.images)) v.images=[];
    payload[k]=v;
  });
  api('/properties.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
    .then(function(){
      if(successText) msg(msgId, successText, true);
      if(cb) cb();
    })
    .catch(function(e){
      // Afficher l'erreur dans le panneau actif ET dans la console
      console.error('Erreur sauvegarde:', e.message);
      msg(msgId, '❌ ' + e.message, false);
    });
}

/* ─── REFRESH PAGE ────────────────────────────────────────── */
function refresh(){
  fetch('data/properties.json?'+Date.now()).then(function(r){return r.json();}).then(function(d){
    if(window.loadVacancesData)window.loadVacancesData(d);
    if(window.loadVentesData)window.loadVentesData(d);
    if(window.loadGalleryData)window.loadGalleryData(d);
  }).catch(function(){});
}

/* ─── AVAILABILITY & RESERVATIONS FUNCTIONS ───────────────── */
function loadAvailability(){
  api('/availability.php?action=get').then(function(d){
    availability=d;
    renderCalendar();
  }).catch(function(){});
}

function loadReservations(){
  api('/reservations.php?action=list')
    .then(function(d){
      reservations=d;
      renderRequests();
    })
    .catch(function(){});
}

function renderCalendar(){
  if(!CK || !availability[CK]){
    var calEl=document.getElementById('bhaCalendar');
    if(calEl) calEl.innerHTML='<div style="text-align:center;color:#8a7d6a;padding:20px">Sélectionnez un bien d\'abord</div>';
    return;
  }
  var year=currentMonth.getFullYear();
  var month=currentMonth.getMonth();
  var firstDay=new Date(year, month, 1);
  var lastDay=new Date(year, month+1, 0);
  var monthNames=['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
  var dayNames=['L','M','M','J','V','S','D'];
  var startDay=firstDay.getDay();
  if(startDay===0) startDay=7;
  var html='<div class="bha-calendar-header"><button class="bha-btn ghost sm" id="bhaCalPrev">←</button><div class="bha-calendar-month">'+monthNames[month]+' '+year+'</div><button class="bha-btn ghost sm" id="bhaCalNext">→</button></div><div class="bha-calendar-grid">';
  for(var i=1;i<startDay;i++){
    html+='<div class="bha-calendar-date disabled"></div>';
  }
  var blockedDates=availability[CK].blocked_dates || [];
  var reservedDates=[];
  (availability[CK].reservations || []).forEach(function(r){
    var start=new Date(r.start);
    var end=new Date(r.end);
    var d=new Date(start);
    while(d<=end){
      reservedDates.push(d.toISOString().split('T')[0]);
      d.setDate(d.getDate()+1);
    }
  });
  for(var day=1;day<=lastDay.getDate();day++){
    var d=new Date(year, month, day);
    var dateStr=d.toISOString().split('T')[0];
    var isBlocked=blockedDates.indexOf(dateStr)>-1;
    var isReserved=reservedDates.indexOf(dateStr)>-1;
    var isToday=dateStr===new Date().toISOString().split('T')[0];
    var classes='bha-calendar-date';
    if(isBlocked) classes+=' blocked';
    if(isReserved) classes+=' reserved';
    if(isToday) classes+=' today';
    html+='<div class="'+classes+'" data-date="'+dateStr+'">'+day+'</div>';
  }
  html+='</div>';
  var calEl=document.getElementById('bhaCalendar');
  if(calEl){
    calEl.innerHTML=html;
    calEl.querySelector('#bhaCalPrev').addEventListener('click',function(){currentMonth.setMonth(currentMonth.getMonth()-1);renderCalendar();});
    calEl.querySelector('#bhaCalNext').addEventListener('click',function(){currentMonth.setMonth(currentMonth.getMonth()+1);renderCalendar();});
    calEl.querySelectorAll('.bha-calendar-date:not(.disabled)').forEach(function(el){
      el.addEventListener('click',function(){toggleDateBlock(el.dataset.date);});
    });
  }
}

function renderRequests(){
  var listEl=document.getElementById('bhaRequestsList');
  if(!listEl) return;
  var requests=(reservations.requests || []).filter(function(r){return r.villa===CK;});
  if(!requests.length){
    listEl.innerHTML='<div style="text-align:center;color:#8a7d6a;padding:20px">Aucune demande de réservation pour ce bien</div>';
  } else {
    listEl.innerHTML=requests.map(function(r){
      return '<div class="bha-request-item"><div class="bha-request-header"><div class="bha-request-name">'+esc(r.first_name)+' '+esc(r.last_name)+'</div><span class="bha-request-status">En attente</span></div><div class="bha-request-details">'+
        '<div>Dates: '+r.start+' → '+r.end+' ('+r.nights+' nuit'+(r.nights>1?'s':'')+')</div>'+
        '<div>Personnes: '+r.guests+'</div>'+
        '<div>Chef: '+r.chef+'</div>'+
        '<div>Contact: '+r.phone+(r.email?' / '+r.email:'')+'</div>'+
        '<div>Total: '+Number(r.total_amount).toLocaleString('fr-FR')+' '+(DB[CK]&&DB[CK].priceUnit?DB[CK].priceUnit:'')+'</div>'+
        (r.created_at?'<div>Envoyée le: '+r.created_at+'</div>':'')+
      '</div><div class="bha-row" style="margin-top:10px"><button class="bha-btn" data-act="validate" data-id="'+r.id+'">✅ Valider</button><button class="bha-btn danger sm" data-act="reject" data-id="'+r.id+'">❌ Refuser</button></div></div>';
    }).join('');
    listEl.querySelectorAll('[data-act="validate"]').forEach(function(b){
      b.addEventListener('click',function(){validateRequest(b.dataset.id);});
    });
    listEl.querySelectorAll('[data-act="reject"]').forEach(function(b){
      b.addEventListener('click',function(){rejectRequest(b.dataset.id);});
    });
  }
  renderValidated();
}

function renderValidated(){
  var listEl=document.getElementById('bhaValidatedList');
  if(!listEl) return;
  var list=(reservations.validated || []).filter(function(r){return r.villa===CK;});
  if(!list.length){
    listEl.innerHTML='<div style="text-align:center;color:#8a7d6a;padding:20px">Aucune réservation validée pour ce bien</div>';
    return;
  }
  listEl.innerHTML=list.map(function(r){
    var hasInvoice = !!r.invoice_generated;
    var statusClass = hasInvoice ? 'invoice' : 'ok';
    var statusLabel = hasInvoice ? ('Facture #'+(r.invoice_number||'')) : 'Validée — en attente infos client';
    var accessUrl = r.access_url || buildAccessUrlFallback(r.access_key);
    var waClient = 'https://wa.me/'+formatPhoneWA(r.phone)+'?text='+encodeURIComponent(buildWhatsAppClientMsg(r, accessUrl));
    var pdfUrl = 'reservation?key='+encodeURIComponent(r.access_key)+'&download=1';
    return '<div class="bha-request-item">'+
      '<div class="bha-request-header">'+
        '<div class="bha-request-name">'+esc(r.first_name)+' '+esc(r.last_name)+'</div>'+
        '<span class="bha-request-status '+statusClass+'">'+esc(statusLabel)+'</span>'+
      '</div>'+
      '<div class="bha-request-details">'+
        '<div>Dates: '+(r.start||r.start_date)+' → '+(r.end||r.end_date)+' ('+r.nights+' nuit'+(r.nights>1?'s':'')+')</div>'+
        '<div>Personnes: '+r.guests+' &nbsp;|&nbsp; Chef: '+r.chef+'</div>'+
        '<div>Contact: '+r.phone+(r.email?' / '+r.email:'')+'</div>'+
        '<div>Total: '+Number(r.total_amount).toLocaleString('fr-FR')+' '+(DB[CK]&&DB[CK].priceUnit?DB[CK].priceUnit:'')+'</div>'+
        (r.validated_at?'<div>Validée le: '+r.validated_at+'</div>':'')+
        (hasInvoice && r.personal_info && r.personal_info.payment_method
          ? '<div>Mode de paiement: '+esc(prettyPayMethod(r.personal_info.payment_method))+'</div>' : '')+
      '</div>'+
      '<a class="bha-request-link" href="'+esc(accessUrl)+'" target="_blank" rel="noopener">🔗 '+esc(accessUrl)+'</a>'+
      '<div class="bha-request-actions">'+
        '<button type="button" class="bha-btn sm" data-act="copyurl" data-url="'+esc(accessUrl)+'">📋 Copier lien</button>'+
        '<a class="bha-btn ghost sm" style="text-decoration:none" href="'+esc(waClient)+'" target="_blank" rel="noopener">💬 WhatsApp client</a>'+
        '<a class="bha-btn ghost sm" style="text-decoration:none" href="'+esc(pdfUrl)+'" target="_blank" rel="noopener">📄 PDF facture</a>'+
        '<button type="button" class="bha-btn danger sm" data-act="delval" data-key="'+esc(r.access_key||'')+'">🗑️ Supprimer</button>'+
      '</div>'+
    '</div>';
  }).join('');
  listEl.querySelectorAll('[data-act="copyurl"]').forEach(function(b){
    b.addEventListener('click',function(){ copyText(b.dataset.url, 'bhaAvailabilityMsg', 'Lien copié !'); });
  });
  listEl.querySelectorAll('[data-act="delval"]').forEach(function(b){
    b.addEventListener('click',function(){ deleteValidated(b.dataset.key); });
  });
}

function formatPhoneWA(phone){
  if(!phone) return '221780140942';
  var digits = String(phone).replace(/[^0-9]/g,'');
  if(!digits) return '221780140942';
  if(digits.length<=9 && digits.charAt(0)!=='2') digits = '221' + digits;
  return digits;
}

function prettyPayMethod(m){
  return ({orange_money:'Orange Money', wave:'Wave', bank_transfer:'Virement bancaire'})[m] || m;
}

function buildWhatsAppClientMsg(r, url){
  var startStr = r.start || r.start_date || '';
  var endStr = r.end || r.end_date || '';
  var startDate = startStr ? new Date(startStr + 'T00:00:00') : null;
  var endDate = endStr ? new Date(endStr + 'T00:00:00') : null;
  var nights = (startDate && endDate && endDate > startDate) ? Math.round((endDate - startDate) / 86400000) : (r.nights || 1);

  var total = Number(r.total_amount || r.total || 0);
  if (!total || total <= 0) {
    var villaKey = r.villa || '';
    var villaObj = (typeof DB !== 'undefined' && DB && DB[villaKey]) ? DB[villaKey] : null;
    if (!villaObj && typeof DB !== 'undefined' && DB) {
      Object.keys(DB).forEach(function(k){
        if (DB[k] && (DB[k].name === r.villa_name || DB[k].name === r.villa)) villaObj = DB[k];
      });
    }
    var pricePerNight = villaObj ? (parseInt(villaObj.price, 10) || 0) : (parseInt(r.base_price, 10) || 0);
    if (pricePerNight > 0) {
      total = pricePerNight * nights;
    }
  }

  var villaName = r.villa_name || r.villa || (typeof DB !== 'undefined' && DB && DB[r.villa] ? DB[r.villa].name : 'Villa');
  var guestsText = (r.guests && r.guests !== '?') ? r.guests : (typeof DB !== 'undefined' && DB && DB[r.villa] && DB[r.villa].persons ? DB[r.villa].persons : 'Non spécifié');
  var totalText = total > 0 ? Number(total).toLocaleString('fr-FR') + ' FCFA' : 'À confirmer avec l\'équipe';

  return 'Bonjour '+((r.first_name||'')+' '+(r.last_name||'')).trim()+',\n\n'
    +'Merci pour votre réservation à *Baobab Horizon* ! ✨\n\n'
    +'Votre demande pour la villa *'+villaName+'* a été *validée*.\n'
    +'📍 Dates : '+startStr+' → '+endStr+' ('+nights+' nuit'+(nights>1?'s':'')+')\n'
    +'👥 Personnes : '+guestsText+'\n'
    +'💰 Total : '+totalText+'\n\n'
    +'Veuillez remplir vos informations et choisir votre mode de paiement en cliquant sur le lien ci-dessous :\n'
    +(url||r.access_url||'')+'\n\n'
    +'Cela nous permettra de vous générer votre facture officielle.\n\n'
    +'À très bientôt !\n— Équipe Baobab Horizon\n📞 +221 78 014 09 42';
}

function buildAccessUrlFallback(key){
  if(!key) return '';
  var loc = window.location;
  var path = loc.pathname.replace(/\/admin\/[^/]*$/,'/reservation');
  return loc.origin + path + '?key=' + encodeURIComponent(key);
}

function copyText(text, msgId, okText){
  var done = false;
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function(){ done=true; if(msgId) msg(msgId, okText||'Copié !',true); }).catch(function(){});
    }
  } catch(e){}
  if(!done){
    var ta=document.createElement('textarea');
    ta.value=text; ta.style.position='fixed'; ta.style.opacity='0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); done=true; if(msgId) msg(msgId, okText||'Copié !',true); } catch(e){}
    document.body.removeChild(ta);
  }
}

function openAccessModal(res){
  var modal = document.getElementById('bhaAccessModal');
  var body = document.getElementById('bhaAccessBody');
  var close = document.getElementById('bhaAccessClose');
  if(!modal || !body) return;
  var accessUrl = res.access_url || buildAccessUrlFallback(res.access_key);
  var waUrl = 'https://wa.me/'+formatPhoneWA(res.phone)+'?text='+encodeURIComponent(buildWhatsAppClientMsg(res, accessUrl));
  var phone = res.phone || '';
  var nights = res.nights || (Math.round((new Date(res.end||res.end_date)-new Date(res.start||res.start_date))/86400000));
  var email = res.email || '';
  var totalFmt = Number(res.total_amount||0).toLocaleString('fr-FR')+' FCFA';
  var msgClient = buildWhatsAppClientMsg(res, accessUrl);

  body.innerHTML =
    '<p style="color:#8a7d6a;font-size:.8rem;margin-bottom:18px">Envoyez le lien ci-dessous au client pour qu\'il remplisse ses informations et reçoive sa facture.</p>'+
    '<div class="bha-access-row">'+
      '<label>Résumé</label>'+
      '<div style="font-size:.82rem;line-height:1.8;background:rgba(184,147,90,.06);padding:12px;border:1px solid rgba(184,147,90,.18)">'+
        '<div><strong>Client :</strong> '+esc(res.first_name)+' '+esc(res.last_name)+'</div>'+
        '<div><strong>Villa :</strong> '+esc(res.villa_name||res.villa)+'</div>'+
        '<div><strong>Dates :</strong> '+(res.start||res.start_date)+' → '+(res.end||res.end_date)+' ('+nights+' nuit'+(nights>1?'s':'')+')</div>'+
        '<div><strong>Personnes :</strong> '+(res.guests||'?')+' &nbsp;·&nbsp; <strong>Chef :</strong> '+(res.chef||'Non')+'</div>'+
        '<div><strong>Téléphone :</strong> '+esc(phone)+' &nbsp;·&nbsp; <strong>Email :</strong> '+esc(email)+'</div>'+
        '<div style="margin-top:6px;color:#d4ab72"><strong>Total :</strong> '+totalFmt+'</div>'+
      '</div>'+
    '</div>'+
    '<div class="bha-access-row">'+
      '<label>Lien d\'accès client (à envoyer)</label>'+
      '<div class="bha-access-url"><input type="text" readonly id="bhaAccessUrlInput" value="'+esc(accessUrl)+'"><button type="button" class="bha-btn sm" id="bhaCopyUrlBtn">📋 Copier</button></div>'+
    '</div>'+
    '<div class="bha-access-row">'+
      '<label>Message WhatsApp prêt à envoyer</label>'+
      '<textarea id="bhaWhatsAppMsg" rows="10" spellcheck="false">'+esc(msgClient)+'</textarea>'+
    '</div>'+
    '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px">'+
      '<button type="button" class="bha-btn" id="bhaWABtn">💬 Ouvrir WhatsApp</button>'+
      '<button type="button" class="bha-btn ghost sm" id="bhaCopyMsgBtn">📋 Copier message</button>'+
      '<a style="text-decoration:none" class="bha-btn ghost sm" id="bhaMailBtn" href="mailto:'+encodeURIComponent(email)+'?subject='+encodeURIComponent('Réservation Baobab Horizon — '+esc(res.villa_name||res.villa))+'&body='+encodeURIComponent(msgClient)+'">✉️ Ouvrir email</a>'+
    '</div>';

  modal.style.display='flex';
  document.body.style.overflow='hidden';

  function hide(){
    modal.style.display='none';
    document.body.style.overflow='';
  }
  close.onclick = hide;
  modal.querySelector('.bha-modal-access-overlay').onclick = hide;
  document.getElementById('bhaCopyUrlBtn').onclick = function(){
    var input = document.getElementById('bhaAccessUrlInput');
    input.select();
    copyText(input.value, 'bhaAvailabilityMsg', 'Lien copié !');
    this.textContent='✅ Copié';
    var t=this; setTimeout(function(){t.textContent='📋 Copier';},1500);
  };
  document.getElementById('bhaCopyMsgBtn').onclick = function(){
    var ta = document.getElementById('bhaWhatsAppMsg');
    copyText(ta.value, 'bhaAvailabilityMsg', 'Message copié !');
    this.textContent='✅ Copié';
    var t=this; setTimeout(function(){t.textContent='📋 Copier message';},1500);
  };
  document.getElementById('bhaWABtn').onclick = function(){
    var ta = document.getElementById('bhaWhatsAppMsg');
    var wa = 'https://wa.me/'+formatPhoneWA(phone)+'?text='+encodeURIComponent(ta.value||msgClient);
    window.open(wa, '_blank', 'noopener');
  };
}

function toggleDateBlock(date){
  if(!CK) return;
  var action;
  if((availability[CK].blocked_dates||[]).indexOf(date)>-1){
    action='unblock';
  } else {
    action='block';
  }
  var fd=new FormData();
  fd.append('action',action);
  fd.append('villa',CK);
  fd.append('date',date);
  fetch(apiUrl('availability.php'),{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(){
      loadAvailability();
      msg('bhaAvailabilityMsg',action==='block'?'Date bloquée':'Date débloquée',true);
    })
    .catch(function(e){msg('bhaAvailabilityMsg','Erreur',false);});
}

function blockDateRange(){
  if(!CK) return;
  var start=document.getElementById('bhaBlockStart').value;
  var end=document.getElementById('bhaBlockEnd').value;
  if(!start||!end){
    msg('bhaAvailabilityMsg','Sélectionnez les dates de début et fin',false);
    return;
  }
  var fd=new FormData();
  fd.append('action','block_range');
  fd.append('villa',CK);
  fd.append('start',start);
  fd.append('end',end);
  fetch(apiUrl('availability.php'),{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(){
      loadAvailability();
      document.getElementById('bhaBlockStart').value='';
      document.getElementById('bhaBlockEnd').value='';
      msg('bhaAvailabilityMsg','Période bloquée',true);
    })
    .catch(function(e){msg('bhaAvailabilityMsg','Erreur',false);});
}

function validateRequest(id){
  if(!confirm('Valider cette demande de réservation ?')) return;
  var fd=new FormData();
  fd.append('action','validate');
  fd.append('request_id',id);
  fetch(apiUrl('reservations.php'),{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json().catch(function(){return{};});})
    .then(function(res){
      loadReservations();
      loadAvailability();
      renderRequests();
      renderCalendar();
      if(res && res.success){
        msg('bhaAvailabilityMsg','Demande validée — copiez le lien ci-dessous',true);
        openAccessModal(res);
      } else {
        msg('bhaAvailabilityMsg','Demande validée',true);
      }
    })
    .catch(function(){msg('bhaAvailabilityMsg','Erreur',false);});
}

function rejectRequest(id){
  if(!confirm('Refuser cette demande de réservation ?')) return;
  var fd=new FormData();
  fd.append('action','reject');
  fd.append('request_id',id);
  fetch(apiUrl('reservations.php'),{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json().catch(function(){return{};});})
    .then(function(){
      loadReservations();
      renderRequests();
      msg('bhaAvailabilityMsg','Demande refusée',true);
    })
    .catch(function(){msg('bhaAvailabilityMsg','Erreur',false);});
}

function deleteValidated(accessKey){
  if(!accessKey) return;
  if(!confirm('Supprimer cette réservation validée ? Les dates seront automatiquement débloquées. Cette action est irréversible.')) return;
  var fd=new FormData();
  fd.append('action','delete_validated');
  fd.append('access_key',accessKey);
  fetch(apiUrl('reservations.php'),{method:'POST',body:fd,credentials:'same-origin'})
    .then(function(r){return r.json().catch(function(){return{};});})
    .then(function(res){
      if(res && res.success){
        loadReservations();
        loadAvailability();
        renderRequests();
        renderCalendar();
        msg('bhaAvailabilityMsg','Réservation supprimée, dates débloquées',true);
      } else {
        var err = (res && res.error) ? res.error : 'Erreur';
        msg('bhaAvailabilityMsg',err,false);
      }
    })
    .catch(function(){msg('bhaAvailabilityMsg','Erreur réseau',false);});
}

/* ─── MSG ─────────────────────────────────────────────────── */
function msg(id,text,ok){
  var el=document.getElementById(id); if(!el) return;
  el.textContent=text; el.className='bha-msg '+(ok?'ok':'err'); el.style.display='block';
  clearTimeout(el._t); el._t=setTimeout(function(){el.style.display='none';},6000);
}

/* ─── EXPOSE ──────────────────────────────────────────────── */
window.BaobabAdmin={open:openModal};

if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
})();
