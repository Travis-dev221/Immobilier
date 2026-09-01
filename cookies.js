/* ═════════════════════════════════════════════════════════════════════
   BAOBAB HORIZON — BANDEAU DE CONSENTEMENT COOKIES & SUIVI GOOGLE GA4
   ═════════════════════════════════════════════════════════════════════ */
(function() {
  var CONSENT_KEY = 'baobab_cookie_consent';
  var savedConsent = localStorage.getItem(CONSENT_KEY);

  if (savedConsent === 'accepted' || savedConsent === 'declined') {
    return;
  }

  function initBanner() {
    if (document.getElementById('bhaCookieBanner')) return;

    var banner = document.createElement('div');
    banner.id = 'bhaCookieBanner';
    banner.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999999;max-width:420px;width:calc(100% - 48px);background:#0F1A17;border:1px solid rgba(214,175,92,0.4);border-radius:6px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,0.6);font-family:"Poppins",sans-serif;color:#F8F4EC;animation:bhaSlideUp 0.5s ease;';

    banner.innerHTML = 
      '<div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">' +
        '<span style="font-size:1.4rem;">🍪</span>' +
        '<strong style="font-family:Lora,serif;font-size:1.1rem;color:#D6AF5C;font-weight:500;">Gestion des cookies</strong>' +
      '</div>' +
      '<p style="font-size:0.8rem;line-height:1.6;color:rgba(248,244,236,0.85);margin-bottom:18px;">' +
        'Nous utilisons des cookies pour améliorer votre expérience et mesurer l\'audience de notre site.' +
      '</p>' +
      '<div style="display:flex;gap:10px;flex-wrap:wrap;">' +
        '<button id="bhaAcceptCookies" style="flex:1;min-width:120px;background:#9C6F1C;color:#0F1A17;border:0;padding:12px 18px;font-size:0.7rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;cursor:pointer;border-radius:3px;transition:0.3s;">Accepter</button>' +
        '<button id="bhaDeclineCookies" style="flex:1;min-width:120px;background:transparent;color:#F8F4EC;border:1px solid rgba(214,175,92,0.3);padding:12px 18px;font-size:0.7rem;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;cursor:pointer;border-radius:3px;transition:0.3s;">Refuser</button>' +
      '</div>';

    var style = document.createElement('style');
    style.innerHTML = '@keyframes bhaSlideUp{from{transform:translateY(100px);opacity:0;}to{transform:translateY(0);opacity:1;}} #bhaAcceptCookies:hover{background:#D6AF5C!important;} #bhaDeclineCookies:hover{border-color:#D6AF5C!important;color:#D6AF5C!important;} @media(max-width:600px){#bhaCookieBanner{bottom:12px;right:12px;left:12px;width:auto;}}';
    document.head.appendChild(style);

    document.body.appendChild(banner);

    document.getElementById('bhaAcceptCookies').addEventListener('click', function() {
      localStorage.setItem(CONSENT_KEY, 'accepted');
      banner.style.display = 'none';
    });

    document.getElementById('bhaDeclineCookies').addEventListener('click', function() {
      localStorage.setItem(CONSENT_KEY, 'declined');
      banner.style.display = 'none';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBanner);
  } else {
    initBanner();
  }
})();
