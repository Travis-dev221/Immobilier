$html = Get-Content "index.html" -Raw -Encoding UTF8

$startIndex = $html.IndexOf("    <!-- Guédé Home -->")
if ($startIndex -eq -1) {
    Write-Host "Impossible de trouver le début des villas."
    exit 1
}

$endIndex = $html.IndexOf("  </section>", $startIndex)
if ($endIndex -eq -1) {
    Write-Host "Impossible de trouver la fin des villas."
    exit 1
}
$endIndex = $endIndex + 12

$scriptToAdd = @"
    <div id="accueilList" style="display: contents;"></div>
  </section>
  <script>
    function fmt(n){return Number(n).toLocaleString('fr-FR');}
    function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function buildSpecs(v){
      var specs=[];
      if(v.bedrooms)specs.push({val:v.bedrooms,label:'Chambres'});
      if(v.persons)specs.push({val:v.persons,label:'Personnes'});
      if(v.area)specs.push({val:esc(v.area),label:esc(v.areaLabel||'m²')});
      return specs.map(function(s){return '<div><div class="sv">'+s.val+'</div><div class="sl">'+s.label+'</div></div>';}).join('');
    }
    function buildAccueilCard(slug,v){
      var isVacances = v.type === 'vacances' || v.type === 'location' || v.section === 'location';
      var imgSrc=v.images&&v.images.length?esc(v.images[0]):'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80';
      var priceDisp=v.price?fmt(v.price):(v.priceUnit||'Sur demande');
      var priceSub=v.price?esc(v.priceUnit):'';
      var tags=(v.tags||[]).map(function(t){return '<span class="tag">'+esc(t)+'</span>';}).join('');
      var nameParts=v.name.split(' ');
      var nameHtml=nameParts.length>1?nameParts[0]+'<br><em>'+nameParts.slice(1).join(' ')+'</em>':('<em>'+esc(v.name)+'</em>');
      var reserveBtn = isVacances 
        ? '<button type="button" class="btn-s" onclick="openReservationModal(\''+slug+'\',\''+v.name.replace(/'/g,"\\'")+'\','+v.price+')" style="background:var(--gold);color:var(--night);border-color:var(--gold);font-weight:600">✉ Réserver</button>'
        : '<a class="btn-s" href="contact.html" style="background:transparent;color:var(--gold);border-color:var(--gold);font-weight:600;text-decoration:none;padding:13px 28px;font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;">Contact</a>';
      return '<article class="vcard" data-type="'+(isVacances?'vacances':'ventes')+'">'+
        '<div class="vgal" onclick="openModal(\''+slug+'\')">'+
          '<img id="'+slug+'-img" src="'+imgSrc+'" alt="'+esc(v.name)+'">'+
          '<div class="gcnt" id="'+slug+'-cnt">1 / '+(v.images?v.images.length:1)+'</div>'+
          '<div class="gnav">'+
            '<button class="gbtn" onclick="event.stopPropagation();chg(\''+slug+'\',-1)">&#8592;</button>'+
            '<button class="gbtn" onclick="event.stopPropagation();chg(\''+slug+'\',1)">&#8594;</button>'+
          '</div>'+
        '</div>'+
        '<div class="vinfo">'+
          '<p class="vzone">&#9679; '+esc(v.zone||'')+'</p>'+
          '<h3 class="vname"><a href="detail.php?key='+slug+'" style="color:inherit;text-decoration:none;">'+nameHtml+'</a></h3>'+
          '<div class="vspecs">'+buildSpecs(v)+'</div>'+
          '<div class="vtags">'+tags+'</div>'+
          '<p class="vdesc">'+esc(v.description||'')+'</p>'+
          '<div class="vfoot">'+
            '<div><div class="price-a">'+priceDisp+'</div><div class="price-u">'+priceSub+'</div></div>'+
            '<div class="vacts">'+reserveBtn+'</div>'+
          '</div>'+
        '</div>'+
      '</article>';
    }
    function loadAccueil(data) {
      var keys = Object.keys(data).filter(function(k){ return (data[k].in_accueil || data[k].section === 'accueil') && data[k].available !== false; });
      var list = document.getElementById('accueilList');
      var count = document.getElementById('vcnt');
      if (count) count.textContent = keys.length + ' propriété' + (keys.length > 1 ? 's' : '');
      list.innerHTML = keys.map(function(k) { return buildAccueilCard(k, data[k]); }).join('');
      if(window.loadGalleryData) window.loadGalleryData(data); // Setup gallery structure
      var obs = new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting)e.target.classList.add('vis');else e.target.classList.remove('vis');});},{threshold:.08});
      list.querySelectorAll('.vcard').forEach(function(el){obs.observe(el);});
    }
    document.addEventListener("DOMContentLoaded", function() {
        fetch('data/properties.json?'+Date.now()).then(function(r){return r.json();}).then(loadAccueil).catch(function(err){console.log("Error loading accueil properties", err);});
    });
  </script>
"@

$newHtml = $html.Substring(0, $startIndex) + $scriptToAdd + $html.Substring($endIndex)
Set-Content "index.html" -Value $newHtml -Encoding UTF8
Write-Host "index.html rendu dynamique."
