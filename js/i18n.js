/* ═════════════════════════════════════════════════════════════════════
   BAOBAB HORIZON — SYSTÈME MULTILINGUE (i18n) ET GESTION RTL
   ═════════════════════════════════════════════════════════════════════ */
(function() {
  window.bhaToggleMobileMenu = function(btn, e) {
    if (e) {
      if (e.preventDefault) e.preventDefault();
      if (e.stopPropagation) e.stopPropagation();
    }
    
    var saved = JSON.parse(localStorage.getItem('baobab_client') || '{}');
    var accountLabel = '👤 Mon compte';
    var accountBadge = '-2%';
    if (saved && saved.name) {
      accountLabel = '👤 ' + saved.name.split(' ')[0];
      accountBadge = 'Remise -' + (saved.discount || 2) + '%';
    }

    var drawer = document.getElementById('bhaMobileDrawer');
    if (!drawer) {
      var pathname = window.location.pathname.split('/').pop() || 'index.html';
      if (!pathname || pathname === '') pathname = 'index.html';
      
      var isAccueil = (pathname === 'index.html' || pathname === '');
      var isAcheter = (pathname === 'ventes.html');
      var isLouer = (pathname === 'vacances.html');
      var isVoiture = (pathname === 'location-voiture.html');
      var isContact = (pathname === 'contact.html');

      drawer = document.createElement('div');
      drawer.id = 'bhaMobileDrawer';
      drawer.innerHTML = `
        <div style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(10,18,16,0.98); z-index:999999; display:flex; flex-direction:column; padding:24px 24px; box-sizing:border-box; backdrop-filter:blur(12px); border-bottom:3px solid #D6AF5C; overflow-y:auto; -webkit-overflow-scrolling:touch;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; padding-bottom:16px; border-bottom:1px solid rgba(214,175,92,0.2);">
            <div style="display:flex; align-items:center; gap:12px;">
              <div style="width:52px; height:52px; border-radius:50%; background:#fff; padding:2px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                <img src="LOGO.jpg" style="width:100%; height:100%; object-fit:contain; border-radius:50%;">
              </div>
              <div>
                <div style="color:#D6AF5C; font-family:'Lora',serif; font-size:1.15rem; font-weight:600; line-height:1.2;">Baobab Horizon</div>
                <div style="color:rgba(255,255,255,0.6); font-size:0.68rem; text-transform:uppercase; letter-spacing:0.12em;">Petite Côte · Sénégal</div>
              </div>
            </div>
            <button type="button" onclick="window.bhaToggleMobileMenu()" style="background:transparent; border:1px solid #D6AF5C; color:#D6AF5C; font-size:1.3rem; width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">✕</button>
          </div>
          <ul style="list-style:none; padding:0; margin:0 0 28px 0; display:flex; flex-direction:column; gap:10px;">
            <li><a href="#" onclick="window.bhaToggleMobileMenu(); window.bhaOpenAccountModal(); return false;" style="display:flex; justify-content:space-between; align-items:center; padding:15px 18px; color:#D6AF5C; text-decoration:none; font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; border-radius:4px; background:rgba(214,175,92,0.15); border-left:4px solid #D6AF5C;"><span>${accountLabel}</span> <span style="font-size:0.75rem; background:#D6AF5C; color:#0A1210; padding:3px 8px; border-radius:3px; font-weight:800;">${accountBadge}</span></a></li>
            <li><a href="index.html" style="display:block; padding:15px 18px; color:#FFFFFF; text-decoration:none; font-size:1rem; font-weight:600; text-transform:uppercase; letter-spacing:.12em; border-radius:4px; background:${isAccueil ? 'rgba(214,175,92,0.2)' : 'rgba(255,255,255,0.04)'}; border-left:4px solid ${isAccueil ? '#D6AF5C' : 'transparent'};">Accueil</a></li>
            <li><a href="ventes.html" style="display:block; padding:15px 18px; color:#FFFFFF; text-decoration:none; font-size:1rem; font-weight:600; text-transform:uppercase; letter-spacing:.12em; border-radius:4px; background:${isAcheter ? 'rgba(214,175,92,0.2)' : 'rgba(255,255,255,0.04)'}; border-left:4px solid ${isAcheter ? '#D6AF5C' : 'transparent'};">Acheter</a></li>
            <li><a href="vacances.html" style="display:block; padding:15px 18px; color:#FFFFFF; text-decoration:none; font-size:1rem; font-weight:600; text-transform:uppercase; letter-spacing:.12em; border-radius:4px; background:${isLouer ? 'rgba(214,175,92,0.2)' : 'rgba(255,255,255,0.04)'}; border-left:4px solid ${isLouer ? '#D6AF5C' : 'transparent'};">Louer</a></li>
            <li><a href="location-voiture.html" style="display:block; padding:15px 18px; color:#FFFFFF; text-decoration:none; font-size:1rem; font-weight:600; text-transform:uppercase; letter-spacing:.12em; border-radius:4px; background:${isVoiture ? 'rgba(214,175,92,0.2)' : 'rgba(255,255,255,0.04)'}; border-left:4px solid ${isVoiture ? '#D6AF5C' : 'transparent'};">Location de voiture</a></li>
            <li><a href="contact.html" style="display:block; padding:15px 18px; color:#FFFFFF; text-decoration:none; font-size:1rem; font-weight:600; text-transform:uppercase; letter-spacing:.12em; border-radius:4px; background:${isContact ? 'rgba(214,175,92,0.2)' : 'rgba(255,255,255,0.04)'}; border-left:4px solid ${isContact ? '#D6AF5C' : 'transparent'};">Contact</a></li>
          </ul>
          <div style="margin-top:auto; padding-top:20px; border-top:1px solid rgba(214,175,92,0.2); text-align:center;">
            <a href="https://wa.me/221780140942" target="_blank" style="display:block; width:100%; padding:15px; background:#D6AF5C; color:#0A1210; font-weight:700; text-align:center; text-decoration:none; border-radius:4px; text-transform:uppercase; letter-spacing:.1em; margin-bottom:16px; box-shadow:0 6px 20px rgba(214,175,92,0.3);">💬 Contactez un conseiller</a>
            <p style="color:rgba(255,255,255,0.5); font-size:.75rem; margin:0;">© 2026 Baobab Horizon · Immobilier & Séjours</p>
          </div>
        </div>
      `;
      document.body.appendChild(drawer);
    } else {
      var accountLink = drawer.querySelector('ul li:first-child a');
      if (accountLink) {
        accountLink.innerHTML = `<span>${accountLabel}</span> <span style="font-size:0.75rem; background:#D6AF5C; color:#0A1210; padding:3px 8px; border-radius:3px; font-weight:800;">${accountBadge}</span>`;
      }
      if (drawer.style.display === 'none') {
        drawer.style.display = 'block';
      } else {
        drawer.style.display = 'none';
      }
    }
  };

  function injectAccountStyles() {
    if (document.getElementById('bha-account-styles')) return;
    var style = document.createElement('style');
    style.id = 'bha-account-styles';
    style.textContent = `
      .nav-account-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 8px 14px !important;
        background: rgba(214, 175, 92, 0.12) !important;
        border: 1px solid rgba(214, 175, 92, 0.4) !important;
        color: #D6AF5C !important;
        font-family: 'Poppins', 'Jost', sans-serif !important;
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        transition: all 0.25s ease !important;
        line-height: 1 !important;
        text-decoration: none !important;
        height: auto !important;
        box-sizing: border-box !important;
      }
      .nav-account-btn svg {
        flex-shrink: 0 !important;
      }
      .nav-account-btn:hover {
        background: rgba(214, 175, 92, 0.25) !important;
        border-color: #D6AF5C !important;
        color: #FFFFFF !important;
        box-shadow: 0 4px 15px rgba(214, 175, 92, 0.25) !important;
      }
      .nav-account-btn.registered {
        background: #D6AF5C !important;
        color: #0F1A17 !important;
        border-color: #D6AF5C !important;
      }
      .nav-account-btn.registered svg {
        stroke: #0F1A17 !important;
      }
      @media (max-width: 1024px) {
        .nav-account-btn {
          padding: 6px 10px !important;
          font-size: 0.65rem !important;
        }
      }
      @media (max-width: 640px) {
        .nav-account-btn {
          padding: 6px 8px !important;
          font-size: 0.58rem !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  function initMobileMenuListeners() {
    injectAccountStyles();
    ['click', 'touchstart'].forEach(function(evt) {
      document.addEventListener(evt, function(e) {
        var btn = e.target.closest('.nav-menu-btn');
        if (btn) {
          window.bhaToggleMobileMenu(btn, e);
        }
      }, { passive: false });
    });
    window.bhaUpdateAccountUI();
    window.bhaInjectAccountOptions();
    setTimeout(window.bhaInjectAccountOptions, 800);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenuListeners);
  } else {
    initMobileMenuListeners();
  }

  // ── ESPACE CLIENT / INSCRIPTION REMISE 1% & 2% ──
  window.bhaOpenAccountModal = function() {
    var modal = document.getElementById('bhaAccountModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'bhaAccountModal';
      modal.innerHTML = `
        <div style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(10,18,16,0.88); z-index:999999; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(8px);">
          <div style="background:#162420; border:1px solid #D6AF5C; border-radius:8px; max-width:480px; width:100%; padding:28px; box-shadow:0 20px 50px rgba(0,0,0,0.6); position:relative; max-height:90vh; overflow-y:auto;">
            <button type="button" onclick="document.getElementById('bhaAccountModal').style.display='none'" style="position:absolute; top:16px; right:16px; background:transparent; border:1px solid rgba(214,175,92,0.4); color:#D6AF5C; font-size:1.2rem; width:36px; height:36px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;">✕</button>

            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
              <div style="width:48px; height:48px; border-radius:50%; background:#fff; padding:2px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                <img src="LOGO.jpg" style="width:100%; height:100%; object-fit:contain; border-radius:50%;">
              </div>
              <div>
                <h3 style="color:#D6AF5C; font-family:'Lora',serif; font-size:1.3rem; margin:0; font-weight:600;">Compte Privilège Client</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.75rem; margin:2px 0 0 0;">Petite Côte · Sénégal</p>
              </div>
            </div>

            <div style="background:rgba(214,175,92,0.12); border:1px solid rgba(214,175,92,0.3); border-radius:6px; padding:14px; margin-bottom:20px;">
              <div style="color:#D6AF5C; font-weight:700; font-size:0.88rem; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D6AF5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
                Avantages Remise Immédiate :
              </div>
              <ul style="margin:0; padding-left:18px; color:#F8F4EC; font-size:0.8rem; line-height:1.5;">
                <li><strong>1 % de remise immédiate</strong> dès votre enregistrement.</li>
                <li><strong>+1 % de remise supplémentaire</strong> (soit <strong>2 % au total</strong>) si vous acceptez de recevoir nos messages promotionnels et opportunités exclusives.</li>
              </ul>
              <p style="margin:8px 0 0 0; color:rgba(255,255,255,0.5); font-size:0.72rem; font-style:italic;">* Offre valable sur les transactions immobilières (achats) et séjours de vacances, hors location de voiture.</p>
            </div>

            <form id="bhaAccountForm" onsubmit="window.bhaSubmitAccount(event)">
              <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:rgba(255,255,255,0.7); margin-bottom:6px;">Nom & Prénom *</label>
                <input type="text" id="bhaCliName" required placeholder="Ex: Moussa Diop" style="width:100%; padding:11px 14px; background:#0B1412; border:1px solid rgba(214,175,92,0.3); color:#fff; font-size:0.9rem; border-radius:4px; box-sizing:border-box;">
              </div>

              <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:rgba(255,255,255,0.7); margin-bottom:6px;">Téléphone (WhatsApp) *</label>
                <input type="tel" id="bhaCliPhone" required placeholder="Ex: +221 77 000 00 00" style="width:100%; padding:11px 14px; background:#0B1412; border:1px solid rgba(214,175,92,0.3); color:#fff; font-size:0.9rem; border-radius:4px; box-sizing:border-box;">
              </div>

              <div style="margin-bottom:18px;">
                <label style="display:block; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:rgba(255,255,255,0.7); margin-bottom:6px;">Adresse Email *</label>
                <input type="email" id="bhaCliEmail" required placeholder="Ex: exemple@domaine.com" style="width:100%; padding:11px 14px; background:#0B1412; border:1px solid rgba(214,175,92,0.3); color:#fff; font-size:0.9rem; border-radius:4px; box-sizing:border-box;">
              </div>

              <div style="margin-bottom:20px; display:flex; align-items:flex-start; gap:10px; background:rgba(0,0,0,0.2); padding:10px 12px; border-radius:4px; border:1px solid rgba(214,175,92,0.2);">
                <input type="checkbox" id="bhaCliMkt" checked style="margin-top:3px; cursor:pointer;">
                <label for="bhaCliMkt" style="font-size:0.78rem; color:rgba(255,255,255,0.9); line-height:1.4; cursor:pointer;">
                  J'accepte de recevoir les messages promotionnels & opportunités exclusives de Baobab Horizon (<strong>+1% de remise supplémentaire, soit 2% au total</strong>).
                </label>
              </div>

              <div id="bhaAccountMsg" style="margin-bottom:14px; display:none; padding:10px; border-radius:4px; font-size:0.82rem;"></div>

              <button type="submit" id="bhaAccountSubmitBtn" style="width:100%; padding:14px; background:#D6AF5C; color:#0A1210; border:none; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.1em; border-radius:4px; cursor:pointer; box-shadow:0 6px 20px rgba(214,175,92,0.3);">
                Créer mon compte & Activer mes remises
              </button>
            </form>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    } else {
      modal.style.display = 'block';
    }

    try {
      var saved = JSON.parse(localStorage.getItem('baobab_client') || '{}');
      if (saved.name) document.getElementById('bhaCliName').value = saved.name;
      if (saved.phone) document.getElementById('bhaCliPhone').value = saved.phone;
      if (saved.email) document.getElementById('bhaCliEmail').value = saved.email;
      if (saved.marketing !== undefined) document.getElementById('bhaCliMkt').checked = !!saved.marketing;
    } catch(e) {}
  };

  window.bhaSubmitAccount = function(e) {
    if (e) e.preventDefault();
    var name = document.getElementById('bhaCliName').value.trim();
    var phone = document.getElementById('bhaCliPhone').value.trim();
    var email = document.getElementById('bhaCliEmail').value.trim();
    var marketing = document.getElementById('bhaCliMkt').checked;
    var msgDiv = document.getElementById('bhaAccountMsg');
    var btn = document.getElementById('bhaAccountSubmitBtn');

    if (!name || (!phone && !email)) {
      msgDiv.style.display = 'block';
      msgDiv.style.background = 'rgba(196,92,92,0.2)';
      msgDiv.style.color = '#f2a8a8';
      msgDiv.style.border = '1px solid #c45c5c';
      msgDiv.textContent = 'Veuillez remplir votre nom et au moins votre téléphone ou email.';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Enregistrement en cours...';

    var payload = {
      name: name,
      phone: phone,
      email: email,
      marketing: marketing
    };

    fetch('api/clients.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      btn.disabled = false;
      btn.textContent = 'Créer mon compte & Activer mes remises';
      
      if (data.ok) {
        var clientData = data.client || payload;
        clientData.discount = data.discount || (marketing ? 2 : 1);
        localStorage.setItem('baobab_client', JSON.stringify(clientData));

        msgDiv.style.display = 'block';
        msgDiv.style.background = 'rgba(90,158,111,0.2)';
        msgDiv.style.color = '#a8dab5';
        msgDiv.style.border = '1px solid #5a9e6f';
        msgDiv.innerHTML = '✔ ' + data.message;

        setTimeout(function() {
          document.getElementById('bhaAccountModal').style.display = 'none';
          window.bhaUpdateAccountUI();
        }, 1800);
      } else {
        msgDiv.style.display = 'block';
        msgDiv.style.background = 'rgba(196,92,92,0.2)';
        msgDiv.style.color = '#f2a8a8';
        msgDiv.style.border = '1px solid #c45c5c';
        msgDiv.textContent = data.error || 'Erreur lors de l’inscription.';
      }
    })
    .catch(function(err) {
      btn.disabled = false;
      btn.textContent = 'Créer mon compte & Activer mes remises';
      
      var discount = marketing ? 2 : 1;
      var clientData = { name: name, phone: phone, email: email, marketing: marketing, discount: discount };
      localStorage.setItem('baobab_client', JSON.stringify(clientData));

      msgDiv.style.display = 'block';
      msgDiv.style.background = 'rgba(90,158,111,0.2)';
      msgDiv.style.color = '#a8dab5';
      msgDiv.style.border = '1px solid #5a9e6f';
      msgDiv.innerHTML = '✔ Compte enregistré ! Remise de ' + discount + '% activée sur votre session.';

      setTimeout(function() {
        document.getElementById('bhaAccountModal').style.display = 'none';
        window.bhaUpdateAccountUI();
      }, 1800);
    });
  };

  window.bhaUpdateAccountUI = function() {
    try {
      injectAccountStyles();
      if (window.location.pathname.indexOf('location-voiture.html') !== -1) {
        var carAccountBtn = document.querySelector('.nav-account-btn');
        if (carAccountBtn) carAccountBtn.style.display = 'none';
        return;
      }

      var saved = JSON.parse(localStorage.getItem('baobab_client') || '{}');
      var navActions = document.querySelectorAll('.nav-actions');
      
      navActions.forEach(function(actionDiv) {
        var existingBtn = actionDiv.querySelector('.nav-account-btn');
        if (!existingBtn) {
          existingBtn = document.createElement('button');
          existingBtn.type = 'button';
          existingBtn.className = 'nav-account-btn';
          actionDiv.insertBefore(existingBtn, actionDiv.firstChild);
        }

        if (saved && saved.name) {
          existingBtn.className = 'nav-account-btn registered';
          existingBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>' + saved.name.split(' ')[0] + ' (-' + (saved.discount || 2) + '%)</span>';
          existingBtn.onclick = window.bhaOpenAccountModal;
        } else {
          existingBtn.className = 'nav-account-btn';
          existingBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg><span>Mon Compte (-2%)</span>';
          existingBtn.onclick = window.bhaOpenAccountModal;
        }
      });
    } catch(e) {}
  };

  window.bhaInjectAccountOptions = function() {
    if (window.location.pathname.indexOf('location-voiture.html') !== -1) return;

    // 1. Enrich leadModal if present
    var leadBox = document.querySelector('#leadModal .lead-box, #leadModal .modal-box');
    if (leadBox && !document.getElementById('bhaLeadAccountOpt')) {
      var grid = leadBox.querySelector('.lead-grid') || leadBox;
      var optDiv = document.createElement('div');
      optDiv.id = 'bhaLeadAccountOpt';
      optDiv.style.cssText = 'grid-column: 1/-1; background:rgba(214,175,92,0.12); border:1px solid rgba(214,175,92,0.3); border-radius:4px; padding:12px; margin:10px 0; text-align:left;';
      optDiv.innerHTML = `
        <div style="font-weight:700; color:#D6AF5C; font-size:0.85rem; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
          🎁 Activer mon compte privilège (-1% à -2% de remise)
        </div>
        <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color:#fff; cursor:pointer; margin-bottom:6px;">
          <input type="checkbox" id="bhaLeadCreateAccount" checked style="accent-color:#D6AF5C; width:16px; height:16px;">
          Enregistrer mon compte privilège (<strong>1% de remise immédiate</strong>)
        </label>
        <label style="display:flex; align-items:center; gap:8px; font-size:0.75rem; color:#D6AF5C; cursor:pointer;">
          <input type="checkbox" id="bhaLeadOptMarketing" checked style="accent-color:#D6AF5C; width:16px; height:16px;">
          Recevoir les offres et opportunités exclusives (<strong>+1% supplémentaire, soit 2% au total</strong>)
        </label>
      `;
      var actions = leadBox.querySelector('.lead-actions');
      if (actions) {
        leadBox.insertBefore(optDiv, actions);
      } else {
        grid.appendChild(optDiv);
      }
    }

    // Intercept submitLeadWhatsApp with systematic auto-capture
    if (!window.bhaOrigSubmitLeadWhatsApp && typeof window.submitLeadWhatsApp === 'function') {
      window.bhaOrigSubmitLeadWhatsApp = window.submitLeadWhatsApp;
      window.submitLeadWhatsApp = function() {
        var createAcc = document.getElementById('bhaLeadCreateAccount');
        var optMkt = document.getElementById('bhaLeadOptMarketing');
        var nameInput = document.getElementById('leadFullName') || document.getElementById('leadFirstName');
        var phoneInput = document.getElementById('leadPhone');

        if (nameInput && nameInput.value.trim()) {
          var nameVal = nameInput.value.trim();
          var phoneVal = phoneInput ? phoneInput.value.trim() : '';

          // 1. Systematic lead capture in database
          fetch('api/clients.php?action=lead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: nameVal,
              phone: phoneVal,
              email: '',
              source: 'Demande WhatsApp (' + (window.leadData ? window.leadData.context : 'Demande générale') + ')'
            })
          }).catch(function(e) {});

          // 2. Formal registration if checkbox checked
          if (createAcc && createAcc.checked) {
            var payload = {
              name: nameVal,
              phone: phoneVal,
              email: '',
              marketing: optMkt ? optMkt.checked : true
            };
            fetch('api/clients.php?action=register', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
              if (data && data.client) {
                localStorage.setItem('baobab_client', JSON.stringify(data.client));
                if (typeof window.bhaUpdateAccountUI === 'function') window.bhaUpdateAccountUI();
              }
            })
            .catch(e => {});
          }
        }

        localStorage.setItem('baobab_has_booked', 'true');
        var banner = document.getElementById('bhaFirstBookingAlert');
        if (banner) {
          banner.style.transform = 'translateX(-50%) translateY(-120px)';
          banner.style.opacity = '0';
          setTimeout(function() { banner.style.display = 'none'; }, 600);
        }

        if (typeof window.bhaOrigSubmitLeadWhatsApp === 'function') {
          window.bhaOrigSubmitLeadWhatsApp();
        }
      };
    }

    // 2. Enrich contactForm if present with systematic auto-capture
    var contactForm = document.getElementById('contactForm');
    if (contactForm && !document.getElementById('bhaContactAccountOpt')) {
      var grid2 = contactForm.querySelector('.form-grid') || contactForm;
      var optDiv2 = document.createElement('div');
      optDiv2.id = 'bhaContactAccountOpt';
      optDiv2.style.cssText = 'grid-column: 1/-1; background:rgba(214,175,92,0.12); border:1px solid rgba(214,175,92,0.3); border-radius:4px; padding:14px; margin:12px 0; text-align:left;';
      optDiv2.innerHTML = `
        <div style="font-weight:700; color:#D6AF5C; font-size:0.85rem; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
          🎁 Créer mon compte client & Activer ma remise
        </div>
        <label style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:#fff; cursor:pointer; margin-bottom:6px;">
          <input type="checkbox" id="bhaContactCreateAccount" checked style="accent-color:#D6AF5C; width:16px; height:16px;">
          Enregistrer mon compte privilège (<strong>1% de remise immédiate</strong>)
        </label>
        <label style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:#D6AF5C; cursor:pointer;">
          <input type="checkbox" id="bhaContactOptMarketing" checked style="accent-color:#D6AF5C; width:16px; height:16px;">
          Recevoir les offres et opportunités exclusives (<strong>+1% de remise supplémentaire, soit 2% au total</strong>)
        </label>
      `;
      var actions2 = contactForm.querySelector('.form-actions');
      if (actions2) {
        contactForm.insertBefore(optDiv2, actions2);
      } else {
        grid2.appendChild(optDiv2);
      }

      contactForm.addEventListener('submit', function(e) {
        var createAcc = document.getElementById('bhaContactCreateAccount');
        var optMkt = document.getElementById('bhaContactOptMarketing');
        var nameInput = document.getElementById('name');
        var phoneInput = document.getElementById('phone');
        var emailInput = document.getElementById('email');

        if (nameInput && (phoneInput || emailInput)) {
          var nameVal = nameInput.value.trim();
          var phoneVal = phoneInput ? phoneInput.value.trim() : '';
          var emailVal = emailInput ? emailInput.value.trim() : '';

          // 1. Systematic lead capture in database
          fetch('api/clients.php?action=lead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: nameVal,
              phone: phoneVal,
              email: emailVal,
              source: 'Formulaire Page Contact'
            })
          }).catch(function(e) {});

          // 2. Formal registration if checkbox checked
          if (createAcc && createAcc.checked) {
            var payload = {
              name: nameVal,
              phone: phoneVal,
              email: emailVal,
              marketing: optMkt ? optMkt.checked : true
            };
            fetch('api/clients.php?action=register', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
              if (data && data.client) {
                localStorage.setItem('baobab_client', JSON.stringify(data.client));
                if (typeof window.bhaUpdateAccountUI === 'function') window.bhaUpdateAccountUI();
              }
            })
            .catch(e => {});
          }
        }

        localStorage.setItem('baobab_has_booked', 'true');
        var banner = document.getElementById('bhaFirstBookingAlert');
        if (banner) {
          banner.style.transform = 'translateX(-50%) translateY(-120px)';
          banner.style.opacity = '0';
          setTimeout(function() { banner.style.display = 'none'; }, 600);
        }
      });
    }
  };

  window.bhaSetLanguage = function(lang) {
    if (typeof setLanguage === 'function') {
      setLanguage(lang);
    }
  };

  var STORAGE_KEY = 'baobab_lang';
  var DEFAULT_LANG = 'fr';
  var RTL_LANGS = ['ar'];

  // Dictionnaires texte pur garantis (Zero balise HTML brute)
  var EMBEDDED_I18N = {
    fr: {
      "nav.accueil": "Accueil",
      "nav.acheter": "Acheter",
      "nav.louer": "Louer",
      "nav.car_rental": "Location de voiture",
      "nav.production": "Boîte de production",
      "nav.hotel": "Notre Hôtel",
      "nav.contact": "Contact",
      "nav.contact_us": "Nous contacter",
      "nav.menu": "Menu",

      "hero.eyebrow": "Petite Côte du Sénégal",
      "hero.title": "Baobab Horizon",
      "hero.subtitle": "Immobilier de prestige & séjours sur mesure",
      "hero.discover": "Découvrir nos biens",
      "hero.contact": "Nous contacter",

      "intent.label": "Je souhaite…",
      "intent.acheter_title": "Acheter un bien",
      "intent.acheter_sub": "Villa, terrain ou investissement locatif",
      "intent.louer_title": "Louer pour des vacances",
      "intent.louer_sub": "Séjours d'une semaine à trois mois",
      "intent.year_title": "M'installer à l'année",
      "intent.year_sub": "Location longue durée, famille ou entreprise",
      "intent.commerce_title": "Ouvrir un commerce",
      "intent.commerce_sub": "Locaux de 39 à 164 m² à Ngaparou",
      "intent.car_rental_title": "Louer une voiture",
      "intent.car_rental_sub": "Berlines, SUV & 4x4 avec chauffeur",
      "zones.eyebrow": "Nos zones d'activité",
      "zones.title_main": "Petite Côte",
      "zones.title_sub": "du Sénégal",
      "zones.subtitle": "Ngaparou · Somone · Saly · Nguérigne · Mbour",

      "intro.eyebrow": "Notre sélection",
      "intro.title": "Des adresses pensées pour l'exception",
      "intro.text": "Baobab Horizon sélectionne des propriétés de prestige sur la Petite Côte du Sénégal — pour des séjours courts ou longue durée, dans des cadres d'exception entre mer, lagon et nature.",

      "stats.villas": "Villas disponibles",
      "stats.locaux": "Locaux commerciaux",
      "stats.guests": "Personnes accueillies",
      "stats.zones": "Zones d'implantation",

      "vacances.eyebrow": "Séjours · Résidentiel · Commercial",
      "vacances.title": "Location prestige",
      "vacances.sub": "Villas de vacances, location longue durée et locaux professionnels sur la Petite Côte.",
      "vacances.guede_tag": "💎 La résidence prestige de Baobab Horizon",
      "vacances.guede_title": "Villa Guédé Home — L'Art du Rêve & de l'Élégance",
      "vacances.guede_desc": "Vivez une expérience d'exception sur la Petite Côte. Avec ses 7 suites majestueuses, sa piscine privée scintillante, son pool house verdoyant et son accueil d'hôte sur mesure, la Villa Guédé Home a déjà séduit des milliers de voyageurs en quête de sérénité absolue. Laissez-vous tenter par un séjour inoubliable.",
      "vacances.guede_btn": "Explorer la Villa Guédé Home →",
      "vacances.section_villas": "Villas de vacances",
      "vacances.section_longue": "Longue durée",
      "vacances.section_locaux": "Locaux commerciaux",

      "ventes.eyebrow": "Achat immobilier à Petite Côte",
      "ventes.title": "Biens à vendre",
      "ventes.sub": "Villas, terrains et opportunités sélectionnées entre Nguerigne, Somone, Saly et Ngaparou.",
      "ventes.section_title": "Sélection vente",
      "ventes.method_eyebrow": "Méthode",
      "ventes.method_title": "Un parcours d'achat simple",
      "ventes.method_sub": "Vous nous décrivez votre projet, nous ciblons les biens adaptés, puis nous organisons les échanges et visites avec un suivi personnalisé.",

      "contact.eyebrow": "Parlons de votre projet",
      "contact.title": "Contact direct",
      "contact.sub": "Achat, vente, location de villas ou locaux commerciaux : notre équipe vous répond rapidement.",
      "contact.form_title": "Une demande, une visite ou un projet",
      "contact.submit_btn": "Envoyer via WhatsApp",

      "hotel.eyebrow": "Opportunités d'Investissement & Séjours",
      "hotel.title": "Luxe · Confort · Excellence",
      "hotel.sub": "Découvrez l'expérience Hôtel Dubai par Baobab Horizon.",

      "lead.eyebrow": "Demande WhatsApp",
      "lead.title": "Avant de continuer sur WhatsApp",
      "lead.sub": "Remplissez vos coordonnées rapidement pour préparer votre message WhatsApp direct.",
      "lead.fullname": "Nom & Prénom *",
      "lead.phone": "Numéro de téléphone *",
      "lead.startdate": "Date d'arrivée (facultatif)",
      "lead.enddate": "Date de départ (facultatif)",
      "lead.message": "Informations complémentaires (facultatif)",
      "lead.note": "Vos informations servent uniquement à préparer votre message WhatsApp. Rien n'est enregistré sur ce site.",
      "lead.cancel": "Annuler",
      "lead.submit": "Continuer sur WhatsApp",

      "cta.title": "Un bien vous intéresse ?",
      "cta.sub": "Contactez-nous pour connaître les disponibilités ou organiser une visite.",
      "cta.whatsapp": "WhatsApp direct",
      "cta.form": "Formulaire",

      "footer.tagline": "Votre partenaire immobilier de prestige sur la Petite Côte du Sénégal. Ventes, locations et séjours sur mesure.",
      "footer.col_nav": "Navigation",
      "footer.col_zones": "Zones",
      "footer.col_contact": "Contact",
      "footer.rights": "© 2026 Baobab Horizon · Tous droits réservés",
      "footer.legals": "Mentions légales",
      "footer.privacy": "Confidentialité",
      "notification.popup_eyebrow": "OFFRE DE BIENVENUE",
      "notification.popup_title": "1ère Réservation",
      "notification.popup_text": "Pour fêter votre arrivée sur la Petite Côte, un Cocktail de bienvenue & un Chef cuisinier vous sont offerts lors de votre premier séjour !",
      "notification.popup_btn": "Découvrir nos villas",
      "ld.badge": "Longue durée",
      "ld.title_sub": "Résidentiel",
      "ld.desc": "La même propriété d'exception disponible en location longue durée. Idéale pour des familles expatriées ou entreprises en déplacement. 7 chambres climatisées dont 3 suites, piscine, pool house, 2 000m² de terrain.",
      "ld.price_label": "Tarif mensuel",
      "ld.price_sub": "FCFA · par mois",
      "ld.chambres": "Chambres",
      "ld.persons": "Personnes max",
      "ld.habitable": "Habitables",
      "ld.terrain": "Terrain",
      "ld.contact": "↗ Contacter via WhatsApp",
      "ld.href": "https://wa.me/221780140942?text=Longue%20dur%C3%A9e%20Gu%C3%A9d%C3%A9%20Home",
      "ld.vacances_desc": "Une grande propriété disponible au mois pour familles expatriées, entreprises ou résidence premium sur la Petite Côte.",
      "ld.vacances_btn": "Demander les conditions",
      "ld.vacances_href": "https://wa.me/221780140942?text=Bonjour%2C%20je%20suis%20int%C3%A9ress%C3%A9(e)%20par%20la%20Villa%20Gu%C3%A9d%C3%A9%20Home%20en%20longue%20dur%C3%A9e.",
      "comm.badge": "Commercial",
      "comm.title_1": "Les Magasins",
      "comm.title_2": "Créoles",
      "comm.price_sub": "FCFA HT / mois",
      "comm.degressif": "Tarif dégressif",
      "comm.footer": "Tarifs HT, hors assurances et charges. Dépôt de garantie : 2 mois. Durée minimale : 1 an.",
      "comm.contact": "↗ Renseignements",
      "comm.href": "https://wa.me/221780140942?text=Magasins%20Cr%C3%A9oles",
      "story.eyebrow": "Notre histoire & Accompagnement",
      "story.title_1": "L'immobilier de prestige",
      "story.title_2": "au cœur de la",
      "story.title_3": "Petite Côte",
      "story.text": "Fondée par des passionnés de la Petite Côte sénégalaise, Baobab Horizon accompagne vos projets d'investissement, de résidence secondaire ou de vacances à Ngaparou, Somone, Saly et Nguérigne. Nous construisons à vos côtés un projet clair, fiable et sur mesure — avec un interlocuteur unique, de la recherche à la signature.",
      "story.point_1": "Expertise locale et connaissance du terrain",
      "story.point_2": "Accompagnement personnalisé",
      "story.point_3": "Sécurité juridique et transparence",
      "story.point_4": "Biens sélectionnés avec exigence",
      "story.point_5": "Recherche personnalisée",
      "story.point_6": "Visites organisées",
      "story.point_7": "Conseil local fiable",
      "story.point_8": "Suivi jusqu'à la signature",
      "story.contact_btn": "Nous contacter",
      "sponsors.label": "Ils nous font confiance"
    },
    en: {
      "nav.accueil": "Home",
      "nav.acheter": "Buy",
      "nav.louer": "Rent",
      "nav.car_rental": "Car Rental",
      "nav.production": "Production Company",
      "nav.hotel": "Dubai Hotel",
      "nav.contact": "Contact",
      "nav.contact_us": "Contact Us",
      "nav.menu": "Menu",

      "hero.eyebrow": "Petite Côte, Senegal",
      "hero.title": "Baobab Horizon",
      "hero.subtitle": "Luxury Real Estate & Tailor-Made Stays",
      "hero.discover": "Explore Properties",
      "hero.contact": "Contact Us",

      "intent.label": "I wish to...",
      "intent.acheter_title": "Buy a Property",
      "intent.acheter_sub": "Villa, land or rental investment",
      "intent.louer_title": "Vacation Rentals",
      "intent.louer_sub": "Stays from one week to three months",
      "intent.year_title": "Year-Round Living",
      "intent.year_sub": "Long-term rental, family or business",
      "intent.commerce_title": "Open a Business",
      "intent.commerce_sub": "Commercial spaces from 39 to 164 m² in Ngaparou",
      "intent.car_rental_title": "Rent a Car",
      "intent.car_rental_sub": "Sedans, SUVs & 4x4s with driver",
      "zones.eyebrow": "Our Areas of Activity",
      "zones.title_main": "Petite Côte",
      "zones.title_sub": "of Senegal",
      "zones.subtitle": "Ngaparou · Somone · Saly · Nguerigne · Mbour",

      "intro.eyebrow": "Our Selection",
      "intro.title": "Addresses Designed for Excellence",
      "intro.text": "Baobab Horizon selects prestigious properties on the Petite Côte of Senegal — for short or long-term stays, in exceptional settings between sea, lagoon, and nature.",

      "stats.villas": "Available Villas",
      "stats.locaux": "Commercial Units",
      "stats.guests": "Guests Welcomed",
      "stats.zones": "Locations",

      "vacances.eyebrow": "Stays · Residential · Commercial",
      "vacances.title": "Prestige Rentals",
      "vacances.sub": "Vacation villas, long-term rentals and commercial spaces on the Petite Côte.",
      "vacances.guede_tag": "💎 Baobab Horizon's Flagship Residence",
      "vacances.guede_title": "Villa Guédé Home — The Art of Dreams & Elegance",
      "vacances.guede_desc": "Experience absolute luxury on the Petite Côte. With its 7 majestic suites, sparkling private pool, lush pool house, and bespoke hospitality, Villa Guédé Home has charmed thousands of guests seeking peace and elegance.",
      "vacances.guede_btn": "Explore Villa Guédé Home →",
      "vacances.section_villas": "Vacation Villas",
      "vacances.section_longue": "Long-Term Rentals",
      "vacances.section_locaux": "Commercial Spaces",

      "ventes.eyebrow": "Property Purchase on Petite Côte",
      "ventes.title": "Properties for Sale",
      "ventes.sub": "Villas, land and handpicked opportunities in Nguerigne, Somone, Saly, and Ngaparou.",
      "ventes.section_title": "Handpicked Sales Selection",
      "ventes.method_eyebrow": "Method",
      "ventes.method_title": "A Seamless Buying Journey",
      "ventes.method_sub": "Tell us your goals, we select suitable properties, and organize visits with personalized guidance.",

      "contact.eyebrow": "Let's Talk About Your Project",
      "contact.title": "Direct Contact",
      "contact.sub": "Buy, sell, or rent villas and commercial units: our team responds promptly.",
      "contact.form_title": "Inquiry, Visit or Project",
      "contact.submit_btn": "Send via WhatsApp",

      "hotel.eyebrow": "Investment & Stay Opportunities",
      "hotel.title": "Luxury · Comfort · Excellence",
      "hotel.sub": "Discover the Dubai Hotel experience by Baobab Horizon.",

      "lead.eyebrow": "WhatsApp Request",
      "lead.title": "Before continuing on WhatsApp",
      "lead.sub": "Fill in your details quickly to prepare your direct WhatsApp message.",
      "lead.fullname": "Full Name *",
      "lead.phone": "Phone Number *",
      "lead.startdate": "Arrival Date (optional)",
      "lead.enddate": "Departure Date (optional)",
      "lead.message": "Additional Notes (optional)",
      "lead.note": "Your information is only used to prepare your WhatsApp message. Nothing is stored on this website.",
      "lead.cancel": "Cancel",
      "lead.submit": "Continue on WhatsApp",

      "cta.title": "Interested in a Property?",
      "cta.sub": "Contact us to check availability or schedule a viewing.",
      "cta.whatsapp": "Direct WhatsApp",
      "cta.form": "Contact Form",

      "footer.tagline": "Your prestigious real estate partner on the Petite Côte of Senegal. Tailor-made sales, rentals and stays.",
      "footer.col_nav": "Navigation",
      "footer.col_zones": "Locations",
      "footer.col_contact": "Contact",
      "footer.rights": "© 2026 Baobab Horizon · All rights reserved",
      "footer.legals": "Legal Notices",
      "footer.privacy": "Privacy Policy",
      "notification.popup_eyebrow": "WELCOME OFFER",
      "notification.popup_title": "1st Booking",
      "notification.popup_text": "To celebrate your arrival on the Petite Côte, a welcome Cocktail & a private Chef cook are offered during your first stay!",
      "notification.popup_btn": "Discover our villas",
      "ld.badge": "Long term",
      "ld.title_sub": "Residential",
      "ld.desc": "The same exceptional property available for long-term rental. Ideal for expatriate families or business travelers. 7 air-conditioned bedrooms including 3 suites, swimming pool, pool house, 2,000m² of land.",
      "ld.price_label": "Monthly rate",
      "ld.price_sub": "FCFA · per month",
      "ld.chambres": "Bedrooms",
      "ld.persons": "Max guests",
      "ld.habitable": "Living area",
      "ld.terrain": "Land area",
      "ld.contact": "↗ Contact via WhatsApp",
      "ld.href": "https://wa.me/221780140942?text=Long-term%20rental%20Guede%20Home",
      "ld.vacances_desc": "A large property available monthly for expatriate families, companies or premium residence on the Petite Côte.",
      "ld.vacances_btn": "Inquire about terms",
      "ld.vacances_href": "https://wa.me/221780140942?text=Hello%2C%20I%20am%20interested%20in%20Villa%20Guede%20Home%20for%20long-term%20rental.",
      "comm.badge": "Commercial",
      "comm.title_1": "The Creole",
      "comm.title_2": "Shops",
      "comm.price_sub": "FCFA excl. tax / month",
      "comm.degressif": "Degressive rate",
      "comm.footer": "Rates excl. tax, insurance and charges not included. Security deposit: 2 months. Minimum term: 1 year.",
      "comm.contact": "↗ Information",
      "comm.href": "https://wa.me/221780140942?text=Creole%20Shops",
      "story.eyebrow": "Our Story & Support",
      "story.title_1": "Prestige real estate",
      "story.title_2": "in the heart of the",
      "story.title_3": "Petite Côte",
      "story.text": "Founded by passionate experts of the Senegalese Petite Côte, Baobab Horizon supports your investment, secondary residence, or vacation projects in Ngaparou, Somone, Saly, and Nguerigne. We build a clear, reliable, and tailor-made project alongside you — with a single point of contact, from search to signature.",
      "story.point_1": "Local expertise and field knowledge",
      "story.point_2": "Personalized support",
      "story.point_3": "Legal security and transparency",
      "story.point_4": "Rigourously selected properties",
      "story.point_5": "Custom search",
      "story.point_6": "Organized visits",
      "story.point_7": "Reliable local advice",
      "story.point_8": "Follow-up until signature",
      "story.contact_btn": "Contact Us",
      "sponsors.label": "They trust us"
    },
    ar: {
      "nav.accueil": "الرئيسية",
      "nav.acheter": "شراء",
      "nav.louer": "إيجار",
      "nav.car_rental": "تأجير السيارات",
      "nav.production": "شركة إنتاج",
      "nav.hotel": "فندق دبي",
      "nav.contact": "اتصل بنا",
      "nav.contact_us": "تواصل معنا",
      "nav.menu": "القائمة",

      "hero.eyebrow": "الساحل الصغير، السنغال",
      "hero.title": "باوباب هورايزون",
      "hero.subtitle": "عقارات فاخرة وإقامات مخصصة",
      "hero.discover": "استكشف عقاراتنا",
      "hero.contact": "تواصل معنا",

      "intent.label": "أرغب في...",
      "intent.acheter_title": "شراء عقار",
      "intent.acheter_sub": "فيلا، أرض أو استثمار إيجاري",
      "intent.louer_title": "إيجار عطلات",
      "intent.louer_sub": "إقامات من أسبوع إلى ثلاثة أشهر",
      "intent.year_title": "الإقامة السنوية",
      "intent.year_sub": "إيجار طويل الأجل للعائلات أو الشركات",
      "intent.commerce_title": "افتتاح نشاط تجاري",
      "intent.commerce_sub": "محلات تجارية من 39 إلى 164 م² في نجابارو",
      "intent.car_rental_title": "استئجار سيارة",
      "intent.car_rental_sub": "سيارات صالون، دفع رباعي مع سائق",
      "zones.eyebrow": "مناطق نشاطنا",
      "zones.title_main": "الساحل الصغير",
      "zones.title_sub": "في السنغال",
      "zones.subtitle": "نجابارو · سومون · سالي · نجيرين · مبور",

      "intro.eyebrow": "مجموعتنا المختارة",
      "intro.title": "عناوين مصممة للتميز",
      "intro.text": "تختار باوباب هورايزون عقارات فاخرة على الساحل الصغير في السنغال — للإقامات القصيرة والطويلة، في بيئات استثنائية بين البحر والبحيرة الطبيعية.",

      "stats.villas": "فيلات متاحة",
      "stats.locaux": "محلات تجارية",
      "stats.guests": "ضيوف تم استقبالهم",
      "stats.zones": "م مواقع الانتشار",

      "vacances.eyebrow": "إقامات · سكني · تجاري",
      "vacances.title": "إيجارات فاخرة",
      "vacances.sub": "فيلات عطلات، إيجار طويل الأجل ومحلات تجارية على الساحل الصغير.",
      "vacances.guede_tag": "💎 الإقامة الفاخرة لباوباب هورايزون",
      "vacances.guede_title": "فيلا جيدي هوم — فن الأحلام والأناقة",
      "vacances.guede_desc": "عش تجربة استثنائية على الساحل الصغير. بفضل أجنحتها السبعة الفاخرة، وحمام السباحة الخاص، والضيافة المخصصة، استقطبت فيلا جيدي هوم آلاف الضيوف الباحثين عن الهدوء والرفاهية.",
      "vacances.guede_btn": "استكشف فيلا جيدي هوم ←",
      "vacances.section_villas": "فيلات عطلات",
      "vacances.section_longue": "إيجار طويل الأجل",
      "vacances.section_locaux": "محلات تجارية",

      "ventes.eyebrow": "شراء عقارات على الساحل الصغير",
      "ventes.title": "عقارات للبيع",
      "ventes.sub": "فيلات، أراضٍ وفرص ممتازة في نجيرين، سومون، سالي ونجابارو.",
      "ventes.section_title": "تشكيلة البيع",
      "ventes.method_eyebrow": "منهجيتنا",
      "ventes.method_title": "مسار شراء سلس وميسر",
      "ventes.method_sub": "تصف لنا مشروعك، نحدد العقارات المناسبة، وننظم الزيارات والاستشارات المخصصة.",

      "contact.eyebrow": "لنتحدث عن مشروعك",
      "contact.title": "اتصال مباشر",
      "contact.sub": "شراء، بيع أو إيجار فيلات ومحلات تجارية: فريقنا يجيبك بسرعة.",
      "contact.form_title": "استفسار، زيارة أو مشروع",
      "contact.submit_btn": "إرسال عبر واتساب",

      "hotel.eyebrow": "فرص الاستثمار والإقامة",
      "hotel.title": "فخامة · راحـة · تميز",
      "hotel.sub": "اكتشف تجربة فندق دبي من باوباب هورايزون.",

      "lead.eyebrow": "طلب عبر واتساب",
      "lead.title": "قبل المتابعة على واتساب",
      "lead.sub": "أدخل بياناتك بسرعة لإعداد رسالة واتساب المباشرة.",
      "lead.fullname": "الاسم الكامل *",
      "lead.phone": "رقم الهاتف *",
      "lead.startdate": "تاريخ الوصول (اختياري)",
      "lead.enddate": "تاريخ المغادرة (اختياري)",
      "lead.message": "معلومات إضافية (اختياري)",
      "lead.note": "تُستخدم معلوماتك فقط لإعداد رسالة الواتساب الخاصة بك. لا يتم حفظ أي شيء على هذا الموقع.",
      "lead.cancel": "إلغاء",
      "lead.submit": "المتابعة على واتساب",

      "cta.title": "هل يعجبك عقار معين؟",
      "cta.sub": "تواصل معنا لمعرفة التوافر أو تنظيم زيارة.",
      "cta.whatsapp": "واتساب مباشر",
      "cta.form": "نموذج التواصل",

      "footer.tagline": "شريكك العقاري الفاخر على الساحل الصغير بالسنغال. بيع، إيجارات وإقامات مخصصة.",
      "footer.col_nav": "التنقل",
      "footer.col_zones": "الم مواقع",
      "footer.col_contact": "التواصل",
      "footer.rights": "© 2026 باوباب هورايزون · جميع الحقوق محفوظة",
      "footer.legals": "الإشعار القانوني",
      "footer.privacy": "سياسة الخصوصية",
      "notification.popup_eyebrow": "عرض الترحيب",
      "notification.popup_title": "الحجز الأول",
      "notification.popup_text": "للاحتفال بوصولكم إلى الساحل الصغير، نقدم لكم كوكتيل ترحيبي وطاهٍ خاص مجاناً خلال إقامتكم الأولى!",
      "notification.popup_btn": "اكتشف فيلاتنا",
      "ld.badge": "إيجار طويل الأجل",
      "ld.title_sub": "سكني",
      "ld.desc": "نفس العقار الاستثنائي متاح للإيجار طويل الأجل. مثالي للعائلات الوافدة أو رحلات العمل. 7 غرف نوم مكيفة بما في ذلك 3 أجنحة، مسبح، بيت مسبح، 2000 متر مربع من الأرض.",
      "ld.price_label": "التعرفة الشهرية",
      "ld.price_sub": "فرنك غرب أفريقي · شهرياً",
      "ld.chambres": "غرف نوم",
      "ld.persons": "الحد الأقصى للأشخاص",
      "ld.habitable": "المساحة السكنية",
      "ld.terrain": "الأرض",
      "ld.contact": "↗ تواصل عبر واتساب",
      "ld.href": "https://wa.me/221780140942?text=إيجار%20طويل%20الأجل%20فيلا%20جيدي%20هوم",
      "ld.vacances_desc": "عقار كبير متاح شهرياً للعائلات الوافدة أو الشركات أو الإقامة المميزة في الساحل الصغير.",
      "ld.vacances_btn": "طلب الشروط",
      "ld.vacances_href": "https://wa.me/221780140942?text=مرحباً%2C%20أنا%20مهتم%20بفيلا%20جيدي%20هوم%20للإيجار%20طويل%20الأجل.",
      "comm.badge": "تجاري",
      "comm.title_1": "محلات",
      "comm.title_2": "كريول",
      "comm.price_sub": "فرنك غرب أفريقي دون رسوم / شهرياً",
      "comm.degressif": "أسعار تنازلية",
      "comm.footer": "الأسعار دون رسوم، وتستثني التأمينات والمصاريف. مبلغ التأمين: شهرين. الحد الأدنى للمدة: سنة واحدة.",
      "comm.contact": "↗ استفسارات",
      "comm.href": "https://wa.me/221780140942?text=محلات%20كريول",
      "story.eyebrow": "قصتنا ومرافقتنا",
      "story.title_1": "العقارات الفاخرة",
      "story.title_2": "في قلب",
      "story.title_3": "الساحل الصغير",
      "story.text": "تأسست باوباب هورايزون على يد خبراء شغوفين بمنطقة الساحل الصغير السنغالي، وهي ترافق مشاريعكم الاستثمارية أو منازلكم الثانية أو عطلاتكم في نجابارو وسومون وسالي ونجيرين. نحن نبني معكم مشروعًا واضحًا وموثوقًا ومصممًا خصيصًا — مع جهة اتصال واحدة، من البحث حتى التوقيع.",
      "story.point_1": "خبرة محلية ومعرفة ميدانية",
      "story.point_2": "مرافقة مخصصة",
      "story.point_3": "الأمان القانوني والشفافية",
      "story.point_4": "عقارات مختارة بعناية ودقة",
      "story.point_5": "بحث مخصص",
      "story.point_6": "زيارات منظمة",
      "story.point_7": "نصائح محلية موثوقة",
      "story.point_8": "متابعة حتى التوقيع",
      "story.contact_btn": "اتصل بنا",
      "sponsors.label": "إنهم يثقون بنا"
    }
  };

  var translations = EMBEDDED_I18N[DEFAULT_LANG];

  function getSavedLang() {
    return localStorage.getItem(STORAGE_KEY) || DEFAULT_LANG;
  }

  function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach(function(el) {
      var key = el.getAttribute('data-i18n');
      if (translations && translations[key] !== undefined) {
        var val = translations[key];
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
          el.placeholder = val;
        } else {
          el.textContent = val;
        }
      }
    });
    document.querySelectorAll('[data-i18n-href]').forEach(function(el) {
      var key = el.getAttribute('data-i18n-href');
      if (translations && translations[key] !== undefined) {
        el.setAttribute('href', translations[key]);
      }
    });
  }

  function updateLanguageUI(currentLang) {
    document.querySelectorAll('.bha-lang-btn').forEach(function(btn) {
      var btnLang = btn.getAttribute('data-lang');
      if (btnLang === currentLang) {
        btn.classList.add('active');
        btn.style.setProperty('color', '#D6AF5C', 'important');
        btn.style.setProperty('opacity', '1', 'important');
        btn.style.setProperty('font-weight', '700', 'important');
        btn.style.setProperty('background', 'transparent', 'important');
        btn.style.setProperty('border-bottom', '2px solid #D6AF5C', 'important');
      } else {
        btn.classList.remove('active');
        btn.style.setProperty('color', '#FFFFFF', 'important');
        btn.style.setProperty('opacity', '0.9', 'important');
        btn.style.setProperty('font-weight', '500', 'important');
        btn.style.setProperty('background', 'transparent', 'important');
        btn.style.setProperty('border-bottom', '2px solid transparent', 'important');
      }
    });
  }

  function setLanguage(lang) {
    if (!lang) return;
    try {
      localStorage.setItem(STORAGE_KEY, lang);
    } catch(e) {}
    document.documentElement.lang = lang;

    if (RTL_LANGS.indexOf(lang) >= 0) {
      document.documentElement.dir = 'rtl';
    } else {
      document.documentElement.dir = 'ltr';
    }

    if (EMBEDDED_I18N && EMBEDDED_I18N[lang]) {
      translations = EMBEDDED_I18N[lang];
      applyTranslations();
      updateLanguageUI(lang);
    }

    if (window.location.protocol !== 'file:') {
      fetch('i18n/' + lang + '.json?v=' + Date.now())
        .then(function(res) {
          if (!res.ok) throw new Error('Translation file error');
          return res.json();
        })
        .then(function(data) {
          translations = data;
          applyTranslations();
          updateLanguageUI(lang);
        })
        .catch(function(err) {});
    }
  }

  function renderLanguageSelector() {
    var nav = document.querySelector('nav') || document.querySelector('header');
    if (!nav || document.querySelector('.bha-lang-switcher')) return;

    var container = document.createElement('div');
    container.className = 'bha-lang-switcher';
    container.style.cssText = 'display:inline-flex;align-items:center;gap:4px;font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;margin-left:14px;margin-right:14px;cursor:pointer;user-select:none;color:#FFFFFF;vertical-align:middle;';

    container.innerHTML = 
      '<span class="bha-lang-btn" data-lang="fr" style="transition:all 0.25s ease;padding:3px 6px;border-radius:3px;color:#FFFFFF !important;cursor:pointer;">FR</span>' +
      '<span style="opacity:0.6;color:#FFFFFF !important;padding:0 2px;">|</span>' +
      '<span class="bha-lang-btn" data-lang="en" style="transition:all 0.25s ease;padding:3px 6px;border-radius:3px;color:#FFFFFF !important;cursor:pointer;">EN</span>' +
      '<span style="opacity:0.6;color:#FFFFFF !important;padding:0 2px;">|</span>' +
      '<span class="bha-lang-btn" data-lang="ar" style="transition:all 0.25s ease;padding:3px 6px;border-radius:3px;color:#FFFFFF !important;cursor:pointer;font-family:sans-serif;">العربية</span>';

    var navActions = nav.querySelector('.nav-actions');
    if (navActions) {
      navActions.insertBefore(container, navActions.firstChild);
    } else {
      nav.appendChild(container);
    }

    container.addEventListener('click', function(e) {
      var btn = e.target.closest('.bha-lang-btn');
      if (btn) {
        var lang = btn.getAttribute('data-lang');
        setLanguage(lang);
      }
    });
  }

  function initMobileMenu() {
    function setupButtons() {
      var btns = document.querySelectorAll('.nav-menu-btn');
      btns.forEach(function(btn) {
        if (btn._hasMenuClick) return;
        btn._hasMenuClick = true;

        btn.addEventListener('click', function(e) {
          window.bhaToggleMobileMenu(btn, e);
        });
      });
    }

    setupButtons();

    if (!document._hasNavCloseListener) {
      document._hasNavCloseListener = true;
      document.addEventListener('click', function(e) {
        var openNav = document.querySelector('nav.nav-open, header.nav-open');
        if (openNav && !openNav.contains(e.target)) {
          openNav.classList.remove('nav-open');
          var openBtn = openNav.querySelector('.nav-menu-btn');
          if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }

  // ── OFFRE 1ÈRE RÉSERVATION ALERTE NOTIFICATION (POPUP PUBLICITAIRE CENTRE) ──
  function renderFirstBookingAlert() {
    if (localStorage.getItem('baobab_has_booked') === 'true' || localStorage.getItem('baobab_first_booking_closed') === 'true') {
      return;
    }
    
    if (document.getElementById('bhaFirstBookingAlert')) return;

    // Create backdrop overlay container
    var backdrop = document.createElement('div');
    backdrop.id = 'bhaFirstBookingAlert';
    backdrop.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(10, 18, 16, 0.85); backdrop-filter:blur(8px); z-index:99999999; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity 0.5s ease; padding:20px; box-sizing:border-box;';

    // Create modal popup window box
    backdrop.innerHTML = `
      <div id="bhaFirstBookingBox" style="background:#162420; border:2px solid #D6AF5C; border-radius:12px; max-width:480px; width:100%; padding:38px 28px; box-shadow:0 25px 60px rgba(0,0,0,0.75); position:relative; text-align:center; transform:scale(0.85); transition:transform 0.5s cubic-bezier(0.16, 1, 0.3, 1); box-sizing:border-box; font-family:\'Jost\', sans-serif;">
        <button id="bhaFirstBookingClose" type="button" style="position:absolute; top:16px; right:16px; background:transparent; border:1px solid rgba(214,175,92,0.4); color:#D6AF5C; font-size:1.2rem; width:36px; height:36px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.25s; line-height:1;">✕</button>
        
        <div style="font-size:3rem; margin-bottom:18px; display:inline-flex; align-items:center; justify-content:center; background:rgba(214,175,92,0.12); width:80px; height:80px; border-radius:50%; border:1px solid rgba(214,175,92,0.35); color:#D6AF5C;">
          🎁
        </div>
        
        <div data-i18n="notification.popup_eyebrow" style="font-size:0.75rem; font-weight:700; color:#D6AF5C; letter-spacing:0.2em; text-transform:uppercase; margin-bottom:8px;">OFFRE DE BIENVENUE</div>
        
        <h3 data-i18n="notification.popup_title" style="color:#FFF; font-family:\'Cormorant Garamond\', Georgia, serif; font-size:2.2rem; font-weight:300; margin:0 0 16px 0; line-height:1.1; letter-spacing:0.04em;">1ère Réservation</h3>
        
        <p data-i18n="notification.popup_text" style="color:rgba(250,247,242,0.85); font-size:0.9rem; font-weight:300; line-height:1.6; margin:0 0 24px 0; font-family:\'Jost\', sans-serif;">Pour fêter votre arrivée sur la Petite Côte, un Cocktail de bienvenue & un Chef cuisinier vous sont offerts lors de votre premier séjour !</p>
        
        <button id="bhaFirstBookingActionBtn" type="button" data-i18n="notification.popup_btn" style="width:100%; padding:14px 20px; background:#D6AF5C; color:#0A1210; border:none; font-weight:700; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.12em; border-radius:4px; cursor:pointer; transition:all 0.25s; box-shadow:0 6px 20px rgba(214,175,92,0.3); font-family:\'Jost\', sans-serif;">Découvrir nos villas</button>
      </div>
    `;

    document.body.appendChild(backdrop);

    var closeBtn = backdrop.querySelector('#bhaFirstBookingClose');
    var actionBtn = backdrop.querySelector('#bhaFirstBookingActionBtn');
    var box = backdrop.querySelector('#bhaFirstBookingBox');

    function closePopup() {
      localStorage.setItem('baobab_first_booking_closed', 'true');
      backdrop.style.opacity = '0';
      box.style.transform = 'scale(0.85)';
      backdrop.style.pointerEvents = 'none';
      setTimeout(function() {
        backdrop.style.display = 'none';
      }, 500);
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        closePopup();
      });
      closeBtn.addEventListener('mouseover', function() { 
        closeBtn.style.borderColor = '#D6AF5C';
        closeBtn.style.background = 'rgba(214,175,92,0.1)';
      });
      closeBtn.addEventListener('mouseout', function() { 
        closeBtn.style.borderColor = 'rgba(214,175,92,0.4)';
        closeBtn.style.background = 'transparent';
      });
    }

    if (actionBtn) {
      actionBtn.addEventListener('click', function(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        closePopup();
        var dest = document.getElementById('selection') || document.getElementById('villas') || document.querySelector('.rental-grid') || document.querySelector('.properties');
        if (dest) {
          dest.scrollIntoView({ behavior: 'smooth' });
        }
      });
      actionBtn.addEventListener('mouseover', function() {
        actionBtn.style.background = '#e8c280';
        actionBtn.style.boxShadow = '0 6px 24px rgba(214,175,92,0.45)';
      });
      actionBtn.addEventListener('mouseout', function() {
        actionBtn.style.background = '#D6AF5C';
        actionBtn.style.boxShadow = '0 6px 20px rgba(214,175,92,0.3)';
      });
    }

    backdrop.addEventListener('click', function(e) {
      if (e.target === backdrop) {
        closePopup();
      }
    });

    setTimeout(function() {
      if (localStorage.getItem('baobab_has_booked') === 'true' || localStorage.getItem('baobab_first_booking_closed') === 'true') {
        return;
      }
      backdrop.style.opacity = '1';
      backdrop.style.pointerEvents = 'all';
      box.style.transform = 'scale(1)';
    }, 2800);
  }

  // Intercepter les requêtes fetch pour détecter les réservations réussies
  (function() {
    var origFetch = window.fetch;
    if (origFetch) {
      window.fetch = function(url, options) {
        return origFetch.apply(this, arguments).then(function(response) {
          try {
            var urlStr = typeof url === 'string' ? url : (url && url.url) || '';
            if (urlStr.indexOf('reservations.php') !== -1 || urlStr.indexOf('clients.php?action=register') !== -1) {
              var clone = response.clone();
              clone.json().then(function(data) {
                if (data && (data.success || data.ok)) {
                  localStorage.setItem('baobab_has_booked', 'true');
                  var banner = document.getElementById('bhaFirstBookingAlert');
                  if (banner) {
                    banner.style.transform = 'translateX(-50%) translateY(-120px)';
                    banner.style.opacity = '0';
                    setTimeout(function() { banner.style.display = 'none'; }, 600);
                  }
                }
              }).catch(function() {});
            }
          } catch(e) {}
          return response;
        });
      };
    }
  })();

  // ── GLOBAL CONTACT AGENT SELECTOR MODAL ──
  function showContactSelectorModal(type, textParam) {
    var existing = document.getElementById('bhaContactSelectorModal');
    if (existing) existing.remove();

    var modal = document.createElement('div');
    modal.id = 'bhaContactSelectorModal';
    modal.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(10, 18, 16, 0.85); backdrop-filter:blur(8px); z-index:999999999; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.4s ease; padding:20px; box-sizing:border-box;';

    var daniLink = '';
    var mactarLink = '';
    var daniLabel = '';
    var mactarLabel = '';

    if (type === 'whatsapp') {
      var msg = textParam ? '?text=' + textParam : '';
      var separator = msg ? '&' : '?';
      daniLink = 'https://wa.me/221780140942' + msg + separator + 'data-bha-bypass=true';
      mactarLink = 'https://wa.me/221773371813' + msg + separator + 'data-bha-bypass=true';
      daniLabel = 'Discuter sur WhatsApp';
      mactarLabel = 'Discuter sur WhatsApp';
    } else {
      daniLink = 'tel:+221780140942';
      mactarLink = 'tel:+221773371813';
      daniLabel = 'Appeler Dani';
      mactarLabel = 'Appeler Mactar';
    }

    modal.innerHTML = `
      <div style="background:#162420; border:2px solid #D6AF5C; border-radius:12px; max-width:460px; width:100%; padding:30px 24px; box-shadow:0 25px 60px rgba(0,0,0,0.75); position:relative; font-family:\'Jost\', sans-serif; box-sizing:border-box; color:#FAF7F2; transform:scale(0.9); transition:transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
        <button id="bhaCloseContactSelector" type="button" onmouseover="this.style.borderColor=\'#D6AF5C\'; this.style.color=\'#FFF\'" onmouseout="this.style.borderColor=\'rgba(214,175,92,0.4)\'; this.style.color=\'#D6AF5C\'" style="position:absolute; top:16px; right:16px; background:transparent; border:1px solid rgba(214,175,92,0.4); color:#D6AF5C; font-size:1.2rem; width:36px; height:36px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.25s; line-height:1;">✕</button>
        
        <h3 style="color:#FFF; font-family:\'Cormorant Garamond\', Georgia, serif; font-size:1.8rem; font-weight:300; margin:0 0 8px 0; line-height:1.2; text-align:center;">Choisissez votre interlocuteur</h3>
        <p style="color:rgba(250,247,242,0.7); font-size:0.85rem; font-weight:300; line-height:1.5; margin:0 0 24px 0; text-align:center;">Notre équipe est à votre disposition pour vous accompagner.</p>
        
        <!-- Agent DANI -->
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(214,175,92,0.25); border-radius:8px; padding:18px; margin-bottom:14px; display:flex; flex-direction:column; gap:12px;">
          <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <div style="flex:1;">
              <h4 style="margin:0; font-size:1.05rem; color:#FFF; font-weight:500;">Dani</h4>
              <p style="margin:2px 0 0 0; font-size:0.72rem; color:rgba(250,247,242,0.55); text-transform:uppercase; letter-spacing:0.05em;">Associé - Co-fondateur</p>
            </div>
            <div style="font-size:0.8rem; color:#D6AF5C; font-weight:500;">+221 78 014 09 42</div>
          </div>
          <a href="${daniLink}" target="_blank" data-bha-bypass="true" onmouseover="this.style.background=\'#c5a052\'" onmouseout="this.style.background=\'#D6AF5C\'" class="bha-selector-btn" style="display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; background:#D6AF5C; color:#0A1210; text-decoration:none; font-weight:700; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; border-radius:4px; text-align:center; transition:background 0.2s;">
            ${type === 'whatsapp' ? '💬 ' : '📞 '} ${daniLabel}
          </a>
        </div>

        <!-- Agent MACTAR -->
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(214,175,92,0.25); border-radius:8px; padding:18px; display:flex; flex-direction:column; gap:12px;">
          <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <div style="flex:1;">
              <h4 style="margin:0; font-size:1.05rem; color:#FFF; font-weight:500;">Mactar</h4>
              <p style="margin:2px 0 0 0; font-size:0.72rem; color:rgba(250,247,242,0.55); text-transform:uppercase; letter-spacing:0.05em;">Associé - Co-fondateur</p>
            </div>
            <div style="font-size:0.8rem; color:#D6AF5C; font-weight:500;">+221 77 337 18 13</div>
          </div>
          <a href="${mactarLink}" target="_blank" data-bha-bypass="true" onmouseover="this.style.background=\'#446840\'" onmouseout="this.style.background=\'#375534\'" class="bha-selector-btn" style="display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; background:#375534; color:#FFF; text-decoration:none; font-weight:700; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; border-radius:4px; text-align:center; transition:background 0.2s; border:1px solid rgba(255,255,255,0.1);">
            ${type === 'whatsapp' ? '💬 ' : '📞 '} ${mactarLabel}
          </a>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    setTimeout(function() {
      modal.style.opacity = '1';
      modal.firstElementChild.style.transform = 'scale(1)';
    }, 10);

    var closeBtn = modal.querySelector('#bhaCloseContactSelector');
    var closeSelector = function() {
      modal.style.opacity = '0';
      modal.firstElementChild.style.transform = 'scale(0.9)';
      setTimeout(function() {
        modal.remove();
      }, 400);
    };

    closeBtn.addEventListener('click', closeSelector);
    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeSelector();
    });

    modal.querySelectorAll('.bha-selector-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        setTimeout(closeSelector, 600);
      });
    });
  }

  // Override window.open globally to intercept JavaScript redirects to WhatsApp (e.g. Lead Modal submission)
  var origOpen = window.open;
  window.open = function(url, target, features) {
    var urlStr = typeof url === 'string' ? url : (url && url.url) || '';
    if (urlStr && urlStr.indexOf('wa.me') !== -1 && urlStr.indexOf('data-bha-bypass') === -1) {
      var textParam = '';
      var textIdx = urlStr.indexOf('text=');
      if (textIdx !== -1) {
        textParam = urlStr.substring(textIdx + 5);
      }
      showContactSelectorModal('whatsapp', textParam);
      return null;
    }
    return origOpen.apply(this, arguments);
  };

  document.addEventListener('click', function(e) {
    var target = e.target.closest('a, button');
    if (!target) return;

    if (target.closest('.bha-wa-popover') || target.closest('#bhaContactSelectorModal') || target.hasAttribute('data-bha-bypass') || target.closest('.contact-card') || target.closest('.contact-cards')) {
      return;
    }

    var href = target.getAttribute('href') || '';
    var isWhatsApp = href.indexOf('wa.me') !== -1;
    var isCall = href.indexOf('tel:') !== -1;

    if (isWhatsApp || isCall) {
      e.preventDefault();
      
      var textParam = '';
      if (isWhatsApp) {
        try {
          var urlObj = new URL(href);
          textParam = urlObj.searchParams.get('text') || '';
        } catch(err) {
          var textIdx = href.indexOf('text=');
          if (textIdx !== -1) {
            textParam = href.substring(textIdx + 5);
          }
        }
      }

      showContactSelectorModal(isWhatsApp ? 'whatsapp' : 'call', textParam);
    }
  });

  function init() {
    renderLanguageSelector();
    renderFirstBookingAlert();
    setLanguage(getSavedLang());
    initMobileMenu();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.bhaSetLanguage = setLanguage;
})();
