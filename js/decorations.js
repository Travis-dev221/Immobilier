/**
 * Baobab Horizon — Global Decorations & Animations Engine v2
 * Pack complet : splash, scroll-progress, typewriter, magnetic buttons,
 * stats, grain, gradient animé, kente, feuilles, carte SVG, citations, parallax
 */
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════════════
     0. GLOBAL CSS
  ══════════════════════════════════════════════════════════════ */
  const css = `
    /* SCROLL PROGRESS */
    #bha-scroll-progress {
      position: fixed; top: 0; left: 0; height: 3px; width: 0%;
      background: linear-gradient(90deg, #9C6F1C, #D6AF5C, #9C6F1C);
      z-index: 999999; transition: width 0.1s linear;
      box-shadow: 0 0 8px rgba(214,175,92,0.6);
    }

    /* SPLASH SCREEN */
    #bha-splash {
      position: fixed; inset: 0; z-index: 9999999;
      background: #0B1613;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      transition: opacity 0.8s ease, visibility 0.8s ease;
    }
    #bha-splash.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
    #bha-splash-name { font-family: 'Lora','Cormorant Garamond',Georgia,serif; font-size: 1.8rem; color: #D6AF5C; letter-spacing: 0.25em; text-transform: uppercase; margin-bottom: 8px; }
    #bha-splash-sub { font-size: 0.62rem; letter-spacing: 0.4em; text-transform: uppercase; color: rgba(248,244,236,0.45); margin-bottom: 40px; }
    #bha-splash-bar-wrap { width: 160px; height: 2px; background: rgba(197,160,89,0.15); border-radius: 2px; overflow: hidden; }
    #bha-splash-bar { height: 100%; width: 0%; background: linear-gradient(90deg,#9C6F1C,#D6AF5C); border-radius: 2px; transition: width 0.04s linear; }
    #bha-splash-baobab { margin-bottom: 20px; animation: bhaSplashTree 1.2s ease-out forwards; opacity: 0; }
    @keyframes bhaSplashTree { 0%{opacity:0;transform:scaleY(0.2) translateY(40px);} 100%{opacity:1;transform:scaleY(1) translateY(0);} }

    /* SCROLL REVEAL */
    .bha-reveal { opacity:0; transform:translateY(30px); transition:opacity 0.75s cubic-bezier(.4,0,.2,1),transform 0.75s cubic-bezier(.4,0,.2,1); }
    .bha-reveal.bha-visible { opacity:1; transform:translateY(0); }
    .bha-reveal-left { opacity:0; transform:translateX(-40px); transition:opacity 0.75s cubic-bezier(.4,0,.2,1),transform 0.75s cubic-bezier(.4,0,.2,1); }
    .bha-reveal-left.bha-visible { opacity:1; transform:translateX(0); }
    .bha-reveal-right { opacity:0; transform:translateX(40px); transition:opacity 0.75s cubic-bezier(.4,0,.2,1),transform 0.75s cubic-bezier(.4,0,.2,1); }
    .bha-reveal-right.bha-visible { opacity:1; transform:translateX(0); }
    .bha-delay-1{transition-delay:0.1s!important}.bha-delay-2{transition-delay:0.2s!important}
    .bha-delay-3{transition-delay:0.32s!important}.bha-delay-4{transition-delay:0.45s!important}
    .bha-delay-5{transition-delay:0.6s!important}

    /* GRAIN TEXTURE */
    #bha-grain {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      opacity: 0.032; mix-blend-mode: overlay;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
      background-size: 200px 200px;
    }

    /* FLOATING PARTICLES */
    #bha-particles-canvas { position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:0.5; }

    /* CURSOR SPARKLE & FOLLOWER */
    #bha-cursor-follower {
      position: fixed;
      width: 24px;
      height: 24px;
      border: 1px solid rgba(214, 175, 92, 0.85);
      border-radius: 50%;
      pointer-events: none;
      z-index: 999998;
      transition: transform 0.08s cubic-bezier(0.23, 1, 0.32, 1);
      transform: translate(-50%, -50%);
      display: block;
    }
    .bha-sparkle {
      position: fixed;
      pointer-events: none;
      z-index: 999999;
      clip-path: polygon(50% 0%, 61% 39%, 100% 50%, 61% 61%, 50% 100%, 39% 61%, 0% 50%, 39% 39%);
      animation: bhaSparkleAnim 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    }
    @keyframes bhaSparkleAnim {
      0% { transform: scale(0) rotate(0deg); opacity: 0; }
      15% { transform: scale(1.2) rotate(45deg); opacity: 1; }
      100% { transform: scale(0) rotate(180deg) translateY(-30px); opacity: 0; }
    }

    /* MAGNETIC BUTTON */
    .bha-magnetic { transition: transform 0.2s cubic-bezier(.175,.885,.32,1.275) !important; }

    /* CARD HOVER GLOW */
    .bha-card-hover { transition:transform 0.35s ease,box-shadow 0.35s ease !important; }
    .bha-card-hover:hover { transform:translateY(-6px) !important; box-shadow:0 20px 45px rgba(0,0,0,0.35),0 0 0 1px rgba(197,160,89,0.2) !important; }

    /* SHIMMER */
    .bha-shimmer { position:relative;overflow:hidden; }
    .bha-shimmer::after { content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.14),transparent);animation:bhaShimmerAnim 3s infinite 1.5s; }
    @keyframes bhaShimmerAnim { 0%{left:-60%} 50%,100%{left:120%} }

    /* MOTIF DIVIDER */
    .bha-motif-divider { display:flex;align-items:center;justify-content:center;gap:14px;padding:16px 0;width:100%;overflow:hidden;pointer-events:none; }
    .bha-md-line { flex:1;max-width:120px;height:1px;background:linear-gradient(90deg,transparent,rgba(197,160,89,0.45)); }
    .bha-md-line.right { background:linear-gradient(90deg,rgba(197,160,89,0.45),transparent); }

    /* STATS SECTION — DISABLED */
    #bha-stats-section {
      background: #122520;
      border-top: 1px solid rgba(197,160,89,0.15);
      border-bottom: 1px solid rgba(197,160,89,0.15);
      padding: 60px;
      text-align: center;
      position: relative; overflow: hidden;
    }
    .bha-stats-grid { display:none; }
    #bha-stats-section { display:none !important; }

    /* TESTIMONIALS */
    #bha-testimonials {
      background: #0B1613; padding: 80px 60px; text-align: center;
      border-top:1px solid rgba(197,160,89,0.12);
      border-bottom:1px solid rgba(197,160,89,0.12);
      position:relative;overflow:hidden;
    }
    #bha-testimonials::before { content:'\u201C';position:absolute;top:20px;left:50%;transform:translateX(-50%);font-family:'Lora',serif;font-size:12rem;color:rgba(197,160,89,0.05);line-height:1;pointer-events:none; }
    .bha-testi-wrap { position:relative;max-width:700px;margin:0 auto; }
    .bha-testi-slide { display:none;animation:bhaTestiFade 0.6s ease; }
    .bha-testi-slide.active { display:block; }
    @keyframes bhaTestiFade { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
    .bha-testi-text { font-family:'Lora',serif;font-size:1.15rem;color:rgba(248,244,236,0.85);line-height:1.8;font-style:italic;margin-bottom:24px; }
    .bha-testi-author { font-size:0.65rem;letter-spacing:0.25em;text-transform:uppercase;color:rgba(197,160,89,0.7); }
    .bha-testi-dots { display:flex;justify-content:center;gap:8px;margin-top:28px; }
    .bha-testi-dot { width:6px;height:6px;border-radius:50%;background:rgba(197,160,89,0.25);cursor:pointer;transition:0.3s; }
    .bha-testi-dot.active { background:#D6AF5C;transform:scale(1.4); }
    .bha-testi-eyebrow { font-size:0.6rem;letter-spacing:0.3em;text-transform:uppercase;color:rgba(197,160,89,0.6);margin-bottom:32px; }

    /* MAP SVG */
    #bha-map-section {
      background: #FAF6F0; padding: 80px 60px; text-align:center;
      position:relative;overflow:hidden;
    }
    #bha-map-section h2 { font-family:'Lora',serif;font-size:2rem;color:#1a2e28;margin-bottom:8px;font-weight:400; }
    #bha-map-section p { font-size:0.85rem;color:#5a7a70;margin-bottom:40px; }
    .bha-map-svg-wrap { max-width:700px;margin:0 auto;position:relative; }
    .bha-map-pin { cursor:pointer; }
    .bha-map-pin circle.pulse { animation:bhaPinPulse 2s ease-in-out infinite; }
    @keyframes bhaPinPulse { 0%,100%{r:8;opacity:0.2} 50%{r:14;opacity:0} }
    .bha-map-pin:hover text { fill:#D6AF5C; }
    .bha-map-tooltip { position:absolute;background:#0B1613;border:1px solid rgba(197,160,89,0.3);color:#fff;font-size:0.72rem;padding:6px 12px;border-radius:3px;pointer-events:none;opacity:0;transition:0.2s;white-space:nowrap; }
    @media(max-width:768px){#bha-map-section,#bha-testimonials{padding:50px 24px;}}

    /* BAOBAB SILHOUETTE */
    #bha-baobab-deco { position:fixed;bottom:-10px;left:-30px;pointer-events:none;z-index:0;opacity:0.022; }

    /* ANIMATED TITLE UNDERLINE */
    .bha-animated-title { position:relative; }
    .bha-animated-title::after { content:'';position:absolute;bottom:-3px;left:0;width:0;height:2px;background:linear-gradient(90deg,rgba(197,160,89,0.8),rgba(197,160,89,0.2));transition:width 0.9s cubic-bezier(.4,0,.2,1) 0.2s; }
    .bha-animated-title.bha-visible::after { width:60%; }

    /* CORNER MOTIFS */
    .bha-corner-motif { position:absolute;pointer-events:none;opacity:0.16; }
  `;
  const st = document.createElement('style');
  st.textContent = css;
  document.head.appendChild(st);

  /* ══════════════════════════════════════════════════════════════
     1. SPLASH SCREEN
  ══════════════════════════════════════════════════════════════ */
  function initSplash() {
    // Skip on detail page (loads differently)
    if (window.location.pathname.includes('detail')) return;

    const splash = document.createElement('div');
    splash.id = 'bha-splash';
    splash.innerHTML = `
      <svg id="bha-splash-baobab" width="80" height="100" viewBox="0 0 80 100" fill="none">
        <rect x="35" y="55" width="10" height="40" rx="3" fill="rgba(197,160,89,0.7)"/>
        <path d="M40 55 Q20 45 5 30 M40 55 Q60 45 75 30 M40 55 Q32 40 20 28 M40 55 Q48 40 60 28" stroke="rgba(197,160,89,0.7)" stroke-width="3" stroke-linecap="round" fill="none"/>
        <circle cx="10" cy="26" r="14" fill="rgba(197,160,89,0.5)"/>
        <circle cx="70" cy="26" r="14" fill="rgba(197,160,89,0.5)"/>
        <circle cx="40" cy="18" r="18" fill="rgba(197,160,89,0.6)"/>
        <circle cx="24" cy="24" r="12" fill="rgba(197,160,89,0.4)"/>
        <circle cx="56" cy="24" r="12" fill="rgba(197,160,89,0.4)"/>
      </svg>
      <div id="bha-splash-name">Baobab Horizon</div>
      <div id="bha-splash-sub">Petite Côte · Sénégal</div>
      <div id="bha-splash-bar-wrap"><div id="bha-splash-bar"></div></div>
    `;
    document.body.prepend(splash);
    document.body.style.overflow = 'hidden';

    const bar = document.getElementById('bha-splash-bar');
    let progress = 0;
    const timer = setInterval(() => {
      progress += Math.random() * 6 + 2;
      if (progress >= 100) {
        progress = 100;
        clearInterval(timer);
        bar.style.width = '100%';
        setTimeout(() => {
          splash.classList.add('hidden');
          document.body.style.overflow = '';
          setTimeout(() => splash.remove(), 900);
        }, 350);
      }
      bar.style.width = Math.min(progress, 100) + '%';
    }, 40);
  }

  /* ══════════════════════════════════════════════════════════════
     2. SCROLL PROGRESS BAR
  ══════════════════════════════════════════════════════════════ */
  function initScrollProgress() {
    const bar = document.createElement('div');
    bar.id = 'bha-scroll-progress';
    document.body.prepend(bar);
    window.addEventListener('scroll', () => {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.width = (max > 0 ? (window.scrollY / max) * 100 : 0) + '%';
    }, { passive: true });
  }

  /* ══════════════════════════════════════════════════════════════
     3. TYPEWRITER EFFECT ON HERO TITLE
  ══════════════════════════════════════════════════════════════ */
  function initTypewriter() {
    const hero = document.querySelector('.hero-h1, .hero-title, h1');
    if (!hero || hero.dataset.typed) return;
    hero.dataset.typed = '1';
    const html = hero.innerHTML;
    hero.innerHTML = '';
    hero.style.borderRight = '2px solid rgba(197,160,89,0.8)';
    hero.style.animation = 'none';

    let i = 0;
    const plain = hero.textContent; // will be empty, use stored
    // For HTML content, use a simpler fade-in word-by-word
    const words = html.split(/(\s+)/);
    hero.innerHTML = '';
    let wi = 0;
    function typeWord() {
      if (wi >= words.length) {
        hero.style.borderRight = 'none';
        return;
      }
      const span = document.createElement('span');
      span.innerHTML = words[wi];
      span.style.opacity = '0';
      span.style.transition = 'opacity 0.18s ease';
      hero.appendChild(span);
      requestAnimationFrame(() => { span.style.opacity = '1'; });
      wi++;
      setTimeout(typeWord, 60 + Math.random() * 40);
    }
    setTimeout(typeWord, 800);
  }

  /* ══════════════════════════════════════════════════════════════
     4. MAGNETIC BUTTONS
  ══════════════════════════════════════════════════════════════ */
  function initMagneticButtons() {
    if (window.matchMedia('(hover: none)').matches) return;
    document.querySelectorAll('.btn-primary, .btn-gold, .nav-cta, [class*="btn-primary-guede"]').forEach(btn => {
      btn.classList.add('bha-magnetic');
      btn.addEventListener('mousemove', e => {
        const r = btn.getBoundingClientRect();
        const dx = (e.clientX - (r.left + r.width / 2)) * 0.28;
        const dy = (e.clientY - (r.top + r.height / 2)) * 0.28;
        btn.style.transform = `translate(${dx}px, ${dy}px)`;
      });
      btn.addEventListener('mouseleave', () => {
        btn.style.transform = '';
      });
    });
  }

  /* Stats section — DISABLED (removed per user request) */
  function injectStatsSection() { /* disabled */ }

  /* ══════════════════════════════════════════════════════════════
     6. GRAIN TEXTURE
  ══════════════════════════════════════════════════════════════ */
  function initGrain() {
    const grain = document.createElement('div');
    grain.id = 'bha-grain';
    document.body.appendChild(grain);
    // Animate grain position for liveliness
    let gx = 0, gy = 0;
    setInterval(() => {
      gx = Math.random() * 100;
      gy = Math.random() * 100;
      grain.style.backgroundPosition = gx + 'px ' + gy + 'px';
    }, 80);
  }

  /* ══════════════════════════════════════════════════════════════
  /* Hero gradient — DISABLED (so slideshow shows bright and clear as before) */
  function initHeroGradient() { /* disabled */ }

  /* Kente bands — DISABLED (removed per user request) */
  function initKenteBands() { /* disabled */ }

  /* Falling leaves — DISABLED (reduced clarity per user request) */
  function initFallingLeaves() { /* disabled */ }

  /* ══════════════════════════════════════════════════════════════
     10. CARTE SVG INTERACTIVE — Petite Côte
  ══════════════════════════════════════════════════════════════ */
  function injectMapSection() {
    if (document.getElementById('bha-map-section')) return;

    const zones = [
      { x: 200, y: 160, name: 'Ngaparou', count: '12 biens' },
      { x: 260, y: 200, name: 'Somone',   count: '8 biens' },
      { x: 310, y: 240, name: 'Saly',     count: '15 biens' },
      { x: 165, y: 210, name: 'Nguérigne',count: '6 biens' },
      { x: 340, y: 180, name: 'Mbour',    count: '9 biens' },
    ];

    const sec = document.createElement('section');
    sec.id = 'bha-map-section';
    sec.innerHTML = `
      <h2>Nos zones d'activité</h2>
      <p>Petite Côte du Sénégal — Ngaparou · Somone · Saly · Nguérigne · Mbour</p>
      <div class="bha-map-svg-wrap">
        <div class="bha-map-tooltip" id="bhaMapTip"></div>
        <svg width="100%" viewBox="0 80 500 280" fill="none" xmlns="http://www.w3.org/2000/svg">
          <!-- Océan Atlantique -->
          <rect x="0" y="80" width="500" height="280" fill="rgba(26,96,128,0.08)" rx="8"/>
          <!-- Contour simplifié Petite Côte -->
          <path d="M80 120 Q100 110 130 115 Q160 118 185 125 Q220 130 250 145 Q280 155 310 165 Q340 172 370 178 Q400 182 430 190 L430 340 Q400 345 370 342 Q340 338 310 330 Q280 320 250 310 Q220 298 185 285 Q160 275 130 265 Q100 255 80 250 Z"
            fill="rgba(26,96,128,0.06)" stroke="rgba(26,96,128,0.3)" stroke-width="1.5"/>
          <!-- Trait de côte -->
          <path d="M80 180 Q130 170 185 178 Q240 185 290 195 Q340 202 390 210"
            stroke="rgba(197,160,89,0.4)" stroke-width="1.2" stroke-dasharray="5 3" fill="none"/>
          <!-- Label mer -->
          <text x="200" y="120" font-family="'Lora',serif" font-size="11" fill="rgba(26,96,128,0.5)" text-anchor="middle" font-style="italic">Océan Atlantique</text>
          <!-- Zones / pins -->
          ${zones.map((z, i) => `
            <g class="bha-map-pin" data-name="${z.name}" data-count="${z.count}">
              <circle cx="${z.x}" cy="${z.y}" r="20" fill="rgba(197,160,89,0.06)"/>
              <circle class="pulse" cx="${z.x}" cy="${z.y}" r="8" fill="rgba(197,160,89,0.15)" style="animation-delay:${i*0.4}s"/>
              <circle cx="${z.x}" cy="${z.y}" r="6" fill="rgba(197,160,89,0.85)" stroke="#fff" stroke-width="1.5"/>
              <text x="${z.x}" y="${z.y + 22}" font-family="'Poppins',sans-serif" font-size="9" fill="rgba(197,160,89,0.9)" text-anchor="middle" font-weight="600">${z.name}</text>
            </g>`).join('')}
        </svg>
      </div>`;

    const footer = document.querySelector('footer');
    const testimonials = document.getElementById('bha-testimonials');
    const sponsors = document.querySelector('.sponsors-bar');
    const statsSection = document.getElementById('bha-stats-section');
    const insertBefore = testimonials || sponsors || statsSection || footer;
    if (insertBefore) insertBefore.parentNode.insertBefore(sec, insertBefore);
    else document.body.appendChild(sec);

    // Tooltip
    const tip = document.getElementById('bhaMapTip');
    const wrap = sec.querySelector('.bha-map-svg-wrap');
    sec.querySelectorAll('.bha-map-pin').forEach(pin => {
      pin.addEventListener('mouseenter', e => {
        const r = wrap.getBoundingClientRect();
        const pr = pin.getBoundingClientRect();
        tip.textContent = pin.dataset.name + ' — ' + pin.dataset.count;
        tip.style.left = (pr.left - r.left + pr.width / 2) + 'px';
        tip.style.top = (pr.top - r.top - 36) + 'px';
        tip.style.opacity = '1';
      });
      pin.addEventListener('mouseleave', () => { tip.style.opacity = '0'; });
    });
  }

  /* ══════════════════════════════════════════════════════════════
     11. TESTIMONIALS ROTATIVES
  ══════════════════════════════════════════════════════════════ */
  function injectTestimonials() {
    if (document.getElementById('bha-testimonials')) return;

    const testimonials = [
      { text: "Baobab Horizon nous a trouvé la villa de nos rêves en moins d'une semaine. Un accompagnement rare, humain et d'une efficacité remarquable.", author: "Sophie M. — Paris" },
      { text: "Je cherchais un investissement sûr sur la Petite Côte. Grâce à l'équipe, j'ai aujourd'hui une villa qui se loue à 90% du temps. Merci Dani !", author: "Karim D. — Dakar" },
      { text: "La villa était parfaite, le chef cuisinier exceptionnel, et l'équipe toujours disponible. On reviendra sans hésiter !", author: "Marie & Jean — Lyon" },
      { text: "Une agence qui tient ses promesses. Sérieux, réactif et une connaissance parfaite du marché sénégalais.", author: "Ahmed B. — Dubai" },
    ];

    const sec = document.createElement('section');
    sec.id = 'bha-testimonials';
    sec.innerHTML = `
      <div class="bha-testi-eyebrow">Ce que disent nos clients</div>
      <div class="bha-testi-wrap">
        ${testimonials.map((t, i) => `
          <div class="bha-testi-slide ${i === 0 ? 'active' : ''}">
            <p class="bha-testi-text">${t.text}</p>
            <span class="bha-testi-author">— ${t.author}</span>
          </div>`).join('')}
        <div class="bha-testi-dots">
          ${testimonials.map((_, i) => `<div class="bha-testi-dot ${i === 0 ? 'active' : ''}" data-idx="${i}"></div>`).join('')}
        </div>
      </div>`;

    const sponsors = document.querySelector('.sponsors-bar');
    const mapSection = document.getElementById('bha-map-section');
    const statsSection = document.getElementById('bha-stats-section');
    const footer = document.querySelector('footer');
    const insertBefore = sponsors || mapSection || statsSection || footer;
    if (insertBefore) insertBefore.parentNode.insertBefore(sec, insertBefore);
    else document.body.appendChild(sec);

    // Auto-rotate
    let cur = 0;
    const slides = sec.querySelectorAll('.bha-testi-slide');
    const dots = sec.querySelectorAll('.bha-testi-dot');

    function goTo(idx) {
      slides[cur].classList.remove('active');
      dots[cur].classList.remove('active');
      cur = (idx + slides.length) % slides.length;
      slides[cur].classList.add('active');
      dots[cur].classList.add('active');
    }
    dots.forEach(d => d.addEventListener('click', () => goTo(+d.dataset.idx)));
    setInterval(() => goTo(cur + 1), 5000);
  }

  /* ══════════════════════════════════════════════════════════════
     12. FLOATING PARTICLES
  ══════════════════════════════════════════════════════════════ */
  function initParticles() {
    const canvas = document.createElement('canvas');
    canvas.id = 'bha-particles-canvas';
    document.body.prepend(canvas);
    const ctx = canvas.getContext('2d');
    let W, H, particles = [];
    const N = 22;
    function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
    resize();
    window.addEventListener('resize', resize, { passive: true });
    function mkP() {
      return { x: Math.random() * W, y: Math.random() * H + H, r: Math.random() * 1.4 + 0.4,
        speed: Math.random() * 0.3 + 0.08, drift: (Math.random() - 0.5) * 0.25,
        opacity: Math.random() * 0.35 + 0.08,
        color: Math.random() > 0.4 ? 'rgba(197,160,89,' : 'rgba(255,255,255,' };
    }
    for (let i = 0; i < N; i++) { const p = mkP(); p.y = Math.random() * H; particles.push(p); }
    function draw() {
      ctx.clearRect(0, 0, W, H);
      particles.forEach((p, i) => {
        p.y -= p.speed; p.x += p.drift;
        if (p.y < -10) particles[i] = mkP();
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color + p.opacity + ')'; ctx.fill();
      });
      requestAnimationFrame(draw);
    }
    draw();
  }

  /* ══════════════════════════════════════════════════════════════
     13. CURSOR SPARKLE
  ══════════════════════════════════════════════════════════════ */
  function initCursorSparkle() {
    if (window.matchMedia('(hover: none)').matches) return;

    // 1. Create cursor follower circle
    const follower = document.createElement('div');
    follower.id = 'bha-cursor-follower';
    document.body.appendChild(follower);

    let last = 0;
    document.addEventListener('mousemove', e => {
      // Smooth follow
      follower.style.left = e.clientX + 'px';
      follower.style.top = e.clientY + 'px';

      const now = Date.now();
      if (now - last < 40) return; // Spawns way more frequently
      last = now;

      const sp = document.createElement('div');
      sp.className = 'bha-sparkle';
      const sz = Math.random() * 12 + 10; // Bigger: 10px to 22px
      const colors = ['#D6AF5C', '#EAD5A8', '#FFFFFF', '#9C6F1C'];
      const c = colors[Math.floor(Math.random() * colors.length)];
      sp.style.cssText = `
        width: ${sz}px;
        height: ${sz}px;
        left: ${e.clientX - sz/2}px;
        top: ${e.clientY - sz/2}px;
        background: ${c};
        box-shadow: 0 0 10px ${c};
      `;
      document.body.appendChild(sp);
      setTimeout(() => sp.remove(), 800);
    }, { passive: true });
  }

  /* ══════════════════════════════════════════════════════════════
     14. SCROLL REVEAL
  ══════════════════════════════════════════════════════════════ */
  function initScrollReveal() {
    const sel = '.prop-card,.card,.listing-card,.property-card,.section-title,h2:not(.bha-reveal),.stat-item,.features-grid>div,.amenities-grid>div,.contact-form,.quick-stats';
    document.querySelectorAll(sel).forEach((el, i) => {
      if (!el.closest('#bha-splash') && !el.classList.contains('bha-reveal')) {
        el.classList.add('bha-reveal');
        if (i % 5 > 0) el.classList.add('bha-delay-' + Math.min(i % 5, 5));
      }
    });
    const obs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('bha-visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
    document.querySelectorAll('.bha-reveal,.bha-reveal-left,.bha-reveal-right').forEach(el => obs.observe(el));
  }

  /* ══════════════════════════════════════════════════════════════
     15. BAOBAB DECO + MOTIF DIVIDERS + CARDS + SHIMMER + CORNER
  ══════════════════════════════════════════════════════════════ */
  function initMiscDecos() {
    // Baobab silhouette
    const bsv = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    bsv.id = 'bha-baobab-deco';
    bsv.setAttribute('width', '260'); bsv.setAttribute('height', '360');
    bsv.setAttribute('viewBox', '0 0 260 360'); bsv.setAttribute('fill', 'none'); bsv.setAttribute('aria-hidden', 'true');
    bsv.innerHTML = `<rect x="112" y="185" width="36" height="160" rx="8" fill="#C5A059"/><path d="M130 185 Q95 165 55 145 M130 185 Q165 165 205 145 M130 185 Q115 150 88 130 M130 185 Q145 150 172 130 M130 185 Q130 150 130 120" stroke="#C5A059" stroke-width="5" stroke-linecap="round" fill="none"/><circle cx="45" cy="105" r="34" fill="#C5A059" opacity="0.65"/><circle cx="215" cy="105" r="34" fill="#C5A059" opacity="0.65"/><circle cx="130" cy="95" r="38" fill="#C5A059" opacity="0.7"/><circle cx="92" cy="115" r="26" fill="#C5A059" opacity="0.5"/><circle cx="168" cy="115" r="26" fill="#C5A059" opacity="0.5"/>`;
    document.body.appendChild(bsv);

    // Card hover
    document.querySelectorAll('.prop-card,.card,.listing-card').forEach(c => c.classList.add('bha-card-hover'));

    // Button shimmer
    document.querySelectorAll('.btn-primary,.btn-gold,.nav-cta').forEach(b => b.classList.add('bha-shimmer'));

    // Motif dividers
    const MOTIF = `<svg width="26" height="26" viewBox="0 0 26 26" fill="none"><path d="M13 1 L25 13 L13 25 L1 13 Z" stroke="rgba(197,160,89,0.65)" stroke-width="1.2" fill="none"/><path d="M13 6 L20 13 L13 20 L6 13 Z" fill="rgba(197,160,89,0.1)" stroke="rgba(197,160,89,0.45)" stroke-width="0.8"/><circle cx="13" cy="13" r="2.5" fill="rgba(197,160,89,0.7)"/></svg>`;
    document.querySelectorAll('section:not(#bha-stats-section):not(#bha-testimonials):not(#bha-map-section)').forEach((sec, i) => {
      if (i === 0 || i % 3 !== 0 || sec.querySelector('.bha-motif-divider')) return;
      const d = document.createElement('div');
      d.className = 'bha-motif-divider'; d.setAttribute('aria-hidden', 'true');
      d.innerHTML = `<div class="bha-md-line"></div>${MOTIF}<div class="bha-md-line"></div>${MOTIF}<div class="bha-md-line right"></div>`;
      sec.parentNode.insertBefore(d, sec);
    });

    // Animated title underlines
    document.querySelectorAll('h2').forEach(h => h.classList.add('bha-animated-title'));
    const tObs = new IntersectionObserver(e => e.forEach(en => { if (en.isIntersecting) { en.target.classList.add('bha-visible'); tObs.unobserve(en.target); } }), { threshold: 0.2 });
    document.querySelectorAll('.bha-animated-title').forEach(h => tObs.observe(h));
  }

  /* ══════════════════════════════════════════════════════════════
     INIT
  ══════════════════════════════════════════════════════════════ */
  function init() {
    try { initSplash(); } catch(e) {}
    try { initScrollProgress(); } catch(e) {}
    try { initGrain(); } catch(e) {}
    try { initParticles(); } catch(e) {}
    try { initCursorSparkle(); } catch(e) {}
    try { initScrollReveal(); } catch(e) {}
    try { injectTestimonials(); } catch(e) {}
    // try { injectMapSection(); } catch(e) {}
    try { initMiscDecos(); } catch(e) {}
    // Magnetic & typewriter need fonts/images loaded
    window.addEventListener('load', () => {
      try { initMagneticButtons(); } catch(e) {}
      try { initTypewriter(); } catch(e) {}
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

})();
