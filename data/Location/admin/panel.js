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
var DB={}, CK='';

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
.bha-drop{border:2px dashed rgba(184,147,90,.3);padding:22px;text-align:center;color:#8a7d6a;cursor:pointer;margin:10px 0;transition:.2s}
.bha-drop:hover,.bha-drop.over{border-color:#b8935a;background:rgba(184,147,90,.04)}
.bha-drop input{display:none}
.bha-pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin:12px 0}
.bha-photo{position:relative;aspect-ratio:4/3;overflow:hidden;background:#2a2520;border:1px solid rgba(184,147,90,.15)}
.bha-photo img{width:100%;height:100%;object-fit:cover;display:block}
.bha-photo-bar{position:absolute;bottom:0;left:0;right:0;display:flex;gap:4px;padding:5px;background:rgba(0,0,0,.8)}
.bha-photo-n{position:absolute;top:5px;left:5px;background:rgba(0,0,0,.65);color:#fff;font-size:.52rem;padding:2px 6px}
.bha-sep{height:1px;background:rgba(184,147,90,.12);margin:18px 0}
@media(max-width:600px){.bha-grid2,.bha-grid3{grid-template-columns:1fr!important}}
`;

/* ─── HTML ────────────────────────────────────────────────── */
var HTML=`
<div class="bha-ov" id="bhaModal">
<div class="bha-box">

<div class="bha-head">
  <div class="bha-logo">Baobab <span>Admin</span></div>
  <div class="bha-hbtns">
    <button type="button" class="bha-btn ghost sm" id="bhaLogout" style="display:none">Déconnexion</button>
    <button type="button" class="bha-close" id="bhaClose">&times;</button>
  </div>
</div>

<!-- LOGIN -->
<div id="bhaLogin" style="padding:28px 22px">
  <div style="font-size:1.1rem;color:#faf7f2;margin-bottom:6px">Connexion</div>
  <div style="font-size:.8rem;color:#8a7d6a;margin-bottom:18px">Espace administration Baobab Horizon</div>
  <div class="bha-msg" id="bhaLoginMsg"></div>
  <div class="bha-fg" style="margin-bottom:14px">
    <label class="bha-label">Mot de passe</label>
    <input class="bha-input" type="password" id="bhaPassword" placeholder="Mot de passe admin" autocomplete="current-password">
  </div>
  <button type="button" class="bha-btn" id="bhaLoginBtn">Se connecter</button>
</div>

<!-- ADMIN -->
<div id="bhaAdmin" style="display:none">
  <div class="bha-tabs">
    <button class="bha-tab on" data-tab="bhaTabList">Mes biens</button>
    <button class="bha-tab" data-tab="bhaTabEdit">Modifier</button>
    <button class="bha-tab" data-tab="bhaTabPhotos">Photos</button>
    <button class="bha-tab" data-tab="bhaTabNew">+ Nouveau bien</button>
  </div>

  <!-- TAB LISTE -->
  <div class="bha-pane on" id="bhaTabList">
    <div class="bha-msg" id="bhaMsg"></div>
    <div style="font-size:.8rem;color:#8a7d6a;margin-bottom:14px">Cliquez sur un bien pour le modifier ou gérer ses photos.</div>
    <div class="bha-list" id="bhaList"></div>
    <div class="bha-row"><button type="button" class="bha-btn" id="bhaGoNew">+ Créer un nouveau bien</button></div>
  </div>

  <!-- TAB MODIFIER -->
  <div class="bha-pane" id="bhaTabEdit">
    <div class="bha-msg" id="bhaEditMsg"></div>
    <div style="font-size:.82rem;color:#8a7d6a;margin-bottom:18px" id="bhaEditSub">← Sélectionnez un bien dans "Mes biens"</div>

    <div class="bha-section">
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
    <div class="bha-drop" id="bhaDrop">
      <input type="file" id="bhaFileInput" accept="image/jpeg,image/png,image/webp" multiple>
      <div style="font-size:.9rem;font-weight:500;color:#e8dcc8;margin-bottom:6px">📁 Glissez vos photos ici ou cliquez</div>
      <div style="font-size:.74rem">JPG, PNG, WEBP — max 8 Mo par fichier</div>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:8px">
      <input class="bha-input" type="text" id="bhaUrlInput" placeholder="Ou coller une URL d'image (https://...)" style="flex:1">
      <button type="button" class="bha-btn ghost sm" id="bhaAddUrl">Ajouter URL</button>
    </div>
    <div class="bha-pgrid" id="bhaPhotoGrid"></div>
    <div class="bha-row">
      <button type="button" class="bha-btn" id="bhaSavePhotos">💾 Enregistrer les photos</button>
    </div>
  </div>

  <!-- TAB NOUVEAU -->
  <div class="bha-pane" id="bhaTabNew">
    <div class="bha-msg" id="bhaNewMsg"></div>
    <div style="font-size:.8rem;color:#8a7d6a;margin-bottom:18px">Créez un nouveau bien immobilier. Les photos s'ajoutent après création.</div>

    <div class="bha-section">
      <div class="bha-section-title">Informations générales</div>
      <div class="bha-grid2">
        <div class="bha-fg bha-full">
          <label class="bha-label">Clé unique (minuscules, tirets)</label>
          <input class="bha-input" type="text" id="bhaNKey" placeholder="Ex: villa-ocean-saly">
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Type de bien</label>
          <select class="bha-select" id="bhaNType">
            <option value="vacances">🏖 Location vacances</option>
            <option value="vente">🏠 Villa à vendre</option>
            <option value="terrain">🌿 Terrain à vendre</option>
          </select>
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Nom du bien</label>
          <input class="bha-input" type="text" id="bhaNName" placeholder="Ex: Villa Océan Saly">
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Zone / Localisation</label>
          <input class="bha-input" type="text" id="bhaNZone" placeholder="Ex: Saly · Bord de mer · 50m plage">
        </div>
        <div class="bha-fg bha-full">
          <label class="bha-label">Description complète</label>
          <textarea class="bha-textarea" id="bhaNDesc" placeholder="Décrivez le bien en détail..."></textarea>
        </div>
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Prix</div>
      <div class="bha-grid3">
        <div class="bha-fg">
          <label class="bha-label">Montant (0 = Sur demande)</label>
          <input class="bha-input" type="number" id="bhaNPrice" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Unité de prix</label>
          <input class="bha-input" type="text" id="bhaNPriceUnit" placeholder="FCFA · nuit">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Note prix</label>
          <input class="bha-input" type="text" id="bhaNPriceNote" placeholder="">
        </div>
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Caractéristiques</div>
      <div class="bha-grid3">
        <div class="bha-fg">
          <label class="bha-label">Chambres</label>
          <input class="bha-input" type="number" id="bhaNBedrooms" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Salles de bain</label>
          <input class="bha-input" type="number" id="bhaNBathrooms" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Personnes max</label>
          <input class="bha-input" type="number" id="bhaNPersons" min="0" value="0">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Surface / Spec</label>
          <input class="bha-input" type="text" id="bhaNArea" placeholder="Ex: 1 200">
        </div>
        <div class="bha-fg">
          <label class="bha-label">Label surface</label>
          <input class="bha-input" type="text" id="bhaNAreaLabel" placeholder="m²">
        </div>
      </div>
    </div>

    <div class="bha-section">
      <div class="bha-section-title">Équipements / Tags</div>
      <div class="bha-fg">
        <label class="bha-label">Tags séparés par virgule</label>
        <input class="bha-input" type="text" id="bhaNTags" placeholder="Ex: Piscine, Chef, Climatisation">
      </div>
    </div>

    <div class="bha-row">
      <button type="button" class="bha-btn" id="bhaCreate">✅ Créer ce bien</button>
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

  /* logout */
  document.getElementById('bhaLogout').addEventListener('click',function(){
    api('/logout.php',{method:'POST'}).then(function(){
      DB={}; CK='';
      document.getElementById('bhaAdmin').style.display='none';
      document.getElementById('bhaLogin').style.display='block';
      document.getElementById('bhaLogout').style.display='none';
      document.getElementById('bhaPassword').value='';
    });
  });

  /* liste → nouveau */
  document.getElementById('bhaGoNew').addEventListener('click',function(){ showTab('bhaTabNew'); });

  /* save edit */
  document.getElementById('bhaSaveEdit').addEventListener('click',doSaveEdit);

  /* delete */
  document.getElementById('bhaDelete').addEventListener('click',function(){
    if(!CK) return;
    if(!confirm('Supprimer "'+((DB[CK]&&DB[CK].name)||CK)+'" ? Irréversible.')) return;
    delete DB[CK]; CK='';
    saveAll(function(){ renderList(); showTab('bhaTabList'); msg('bhaMsg','Bien supprimé.',true); refresh(); });
  });

  /* create */
  document.getElementById('bhaCreate').addEventListener('click',doCreate);

  /* photos — drop */
  var drop=document.getElementById('bhaDrop');
  var fi=document.getElementById('bhaFileInput');
  drop.addEventListener('click',function(){ fi.click(); });
  fi.addEventListener('change',function(){ if(!CK){msg('bhaPhotoMsg','Sélectionnez un bien d\'abord.',false);return;} if(fi.files.length) upload(fi.files); fi.value=''; });
  drop.addEventListener('dragover',function(e){ e.preventDefault(); drop.classList.add('over'); });
  drop.addEventListener('dragleave',function(){ drop.classList.remove('over'); });
  drop.addEventListener('drop',function(e){ e.preventDefault(); drop.classList.remove('over'); if(!CK){msg('bhaPhotoMsg','Sélectionnez un bien d\'abord.',false);return;} if(e.dataTransfer.files.length)upload(e.dataTransfer.files); });

  /* add url */
  document.getElementById('bhaAddUrl').addEventListener('click',function(){
    if(!CK){msg('bhaPhotoMsg','Sélectionnez un bien.',false);return;}
    var u=document.getElementById('bhaUrlInput').value.trim();
    if(!u) return;
    if(!DB[CK].images)DB[CK].images=[];
    DB[CK].images.push(u);
    document.getElementById('bhaUrlInput').value='';
    renderPhotos();
  });

  /* save photos */
  document.getElementById('bhaSavePhotos').addEventListener('click',function(){
    saveAll(function(){ msg('bhaPhotoMsg','Photos enregistrées.',true); renderList(); refresh(); });
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
  api('/login.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:pw})})
    .then(function(){ showAdmin(); })
    .catch(function(e){ msg('bhaLoginMsg',e.message,false); });
}
function showAdmin(){
  document.getElementById('bhaLogin').style.display='none';
  document.getElementById('bhaAdmin').style.display='block';
  document.getElementById('bhaLogout').style.display='inline-flex';
  api('/properties.php').then(function(d){ DB=d; renderList(); }).catch(function(e){ msg('bhaMsg',e.message,false); });
}

/* ─── LISTE ───────────────────────────────────────────────── */
function renderList(){
  var el=document.getElementById('bhaList');
  if(!el) return;
  var keys=Object.keys(DB);
  if(!keys.length){ el.innerHTML='<p style="color:#8a7d6a;padding:10px">Aucun bien. Créez-en un.</p>'; return; }
  el.innerHTML=keys.map(function(k){
    var v=DB[k];
    var bc=v.type==='vacances'?'bha-bv':v.type==='vente'?'bha-bvente':'bha-bt';
    var badge='<span class="bha-badge '+bc+'">'+esc(v.type||'')+'</span>';
    var prix=v.price?Number(v.price).toLocaleString('fr-FR')+' '+(v.priceUnit||''):'Sur demande';
    return '<div class="bha-item'+(k===CK?' on':'')+'" data-key="'+esc(k)+'">'
      +'<div><div class="bha-item-name">'+esc(v.name)+'</div>'
      +'<div class="bha-item-meta">'+badge+' '+esc(v.zone||'')+' · '+esc(prix)+'</div></div>'
      +'<div style="display:flex;gap:6px;flex-shrink:0">'
      +'<button type="button" class="bha-btn ghost sm" data-act="edit" data-key="'+esc(k)+'">Modifier</button>'
      +'<button type="button" class="bha-btn ghost sm" data-act="photos" data-key="'+esc(k)+'">Photos ('+(v.images||[]).length+')</button>'
      +'</div></div>';
  }).join('');
  el.querySelectorAll('[data-act]').forEach(function(b){
    b.addEventListener('click',function(e){
      e.stopPropagation();
      select(b.dataset.key);
      showTab(b.dataset.act==='edit'?'bhaTabEdit':'bhaTabPhotos');
    });
  });
  el.querySelectorAll('.bha-item').forEach(function(i){
    i.addEventListener('click',function(){ select(i.dataset.key); showTab('bhaTabEdit'); });
  });
}

/* ─── SELECT ──────────────────────────────────────────────── */
function select(k){
  CK=k; var v=DB[k]; if(!v) return;
  var s=function(id,val){ var e=document.getElementById(id); if(e)e.value=val||''; };
  s('bhaFType',v.type||'vacances');
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
  renderPhotos();
  renderList();
}

/* ─── BUILD OBJET ─────────────────────────────────────────── */
function build(px){
  var g=function(id){ var e=document.getElementById(px+id); return e?e.value:''; };
  return{
    type:g('Type')||'vacances',
    name:g('Name').trim(),
    zone:g('Zone').trim(),
    price:parseInt(g('Price'))||0,
    priceUnit:g('PriceUnit').trim(),
    priceNote:g('PriceNote').trim(),
    description:g('Desc').trim(),
    bedrooms:parseInt(g('Bedrooms'))||0,
    bathrooms:parseInt(g('Bathrooms'))||0,
    persons:parseInt(g('Persons'))||0,
    area:g('Area').trim(),
    areaLabel:g('AreaLabel').trim(),
    tags:g('Tags').split(',').map(function(t){return t.trim();}).filter(Boolean),
    images:(DB[CK]&&DB[CK].images)||[]
  };
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
  saveAll(function(){ renderList(); msg('bhaEditMsg','✅ Bien enregistré.',true); refresh(); });
}

/* ─── CREATE ──────────────────────────────────────────────── */
function doCreate(){
  var key=(document.getElementById('bhaNKey').value||'').trim().toLowerCase();
  if(!key||!/^[a-z0-9_-]+$/.test(key)){msg('bhaNewMsg','Clé invalide',false);return;}
  if(DB[key]){msg('bhaNewMsg','Cette clé existe déjà.',false);return;}
  if(!(document.getElementById('bhaNName').value||'').trim()){msg('bhaNewMsg','Le nom est obligatoire',false);return;}
  var obj=build('bhaN');
  obj.images=[];
  DB[key]=obj; CK=key;
  saveAll(function(){
    renderList(); select(key); showTab('bhaTabPhotos');
    msg('bhaPhotoMsg','✅ Bien créé ! Ajoutez maintenant des photos.',true);
  });
}

/* ─── PHOTOS ──────────────────────────────────────────────── */
function renderPhotos(){
  var grid=document.getElementById('bhaPhotoGrid');
  if(!grid) return;
  var imgs=(DB[CK]&&DB[CK].images)||[];
  grid.innerHTML=imgs.map(function(url,i){
    var src=url.indexOf('http')===0?url:url;
    return '<div class="bha-photo" data-idx="'+i+'">'
      +'<span class="bha-photo-n">'+(i+1)+'</span>'
      +'<img src="'+esc(src)+'" alt="" onerror="this.style.opacity=\'.25\'">'
      +'<div class="bha-photo-bar">'
      +'<button type="button" class="bha-btn ghost sm" data-act="up"'+(i===0?' disabled':'')+'>↑</button>'
      +'<button type="button" class="bha-btn ghost sm" data-act="down"'+(i===imgs.length-1?' disabled':'')+'>↓</button>'
      +'<button type="button" class="bha-btn danger sm" data-act="del">✕</button>'
      +'</div></div>';
  }).join('');
  grid.querySelectorAll('button').forEach(function(b){
    b.addEventListener('click',function(){
      var idx=+b.closest('.bha-photo').dataset.idx;
      var act=b.dataset.act;
      var list=DB[CK].images;
      if(act==='up'&&idx>0){var t=list[idx-1];list[idx-1]=list[idx];list[idx]=t;renderPhotos();}
      if(act==='down'&&idx<list.length-1){var t2=list[idx+1];list[idx+1]=list[idx];list[idx]=t2;renderPhotos();}
      if(act==='del'&&confirm('Supprimer cette photo ?')){
        var url=list.splice(idx,1)[0]; renderPhotos(); renderList();
        if(url.indexOf('images/')===0) api('/delete-image.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url:url})}).catch(function(){});
      }
    });
  });
}

function upload(files){
  var chain=Promise.resolve();
  for(var i=0;i<files.length;i++){
    (function(f){
      chain=chain.then(function(){
        var fd=new FormData(); fd.append('villa',CK); fd.append('photo',f);
        return fetch(apiUrl('upload.php'),{method:'POST',credentials:'same-origin',body:fd})
          .then(function(r){return r.json().then(function(j){if(!r.ok)throw new Error(j.error||'Upload échoué');return j;});})
          .then(function(j){ if(!DB[CK].images)DB[CK].images=[]; DB[CK].images.push(j.url); });
      });
    })(files[i]);
  }
  chain.then(function(){ renderPhotos(); renderList(); msg('bhaPhotoMsg',files.length+' photo(s) ajoutée(s). Cliquez Enregistrer.',true); })
    .catch(function(e){ msg('bhaPhotoMsg',e.message,false); });
}

/* ─── SAVE ALL ────────────────────────────────────────────── */
function saveAll(cb){
  api('/properties.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(DB)})
    .then(function(){ if(cb)cb(); })
    .catch(function(e){ msg('bhaMsg',e.message,false); });
}

/* ─── REFRESH PAGE ────────────────────────────────────────── */
function refresh(){
  fetch('data/properties.json?'+Date.now()).then(function(r){return r.json();}).then(function(d){
    if(window.loadVacancesData)window.loadVacancesData(d);
    if(window.loadVentesData)window.loadVentesData(d);
    if(window.loadGalleryData)window.loadGalleryData(d);
  }).catch(function(){});
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
