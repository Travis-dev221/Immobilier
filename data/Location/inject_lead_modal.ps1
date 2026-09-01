$base = "c:\Users\dia9a\OneDrive\Bureau\Location"
$files = @(
  (Join-Path $base "index.html")
  (Join-Path $base "vacances.html")
  (Join-Path $base "contact.html")
  (Join-Path $base "ventes.html")
)

$leadCss = @'
.lead-modal{position:fixed;inset:0;z-index:9400;background:rgba(10,8,6,.92);display:none;align-items:center;justify-content:center;padding:20px}.lead-modal.open{display:flex}.lead-box{position:relative;width:min(760px,100%);max-height:min(92vh,900px);overflow:auto;background:var(--night-mid);border:1px solid rgba(184,147,90,.2);padding:32px 28px;box-shadow:0 30px 80px rgba(0,0,0,.45)}.lead-close{position:absolute;top:14px;right:14px;width:42px;height:42px;border:1px solid rgba(184,147,90,.25);background:transparent;color:var(--sand);font-size:1.4rem;line-height:1;cursor:pointer}.lead-close:hover{border-color:var(--gold);color:var(--gold)}.lead-eyebrow{font-size:.62rem;letter-spacing:.24em;text-transform:uppercase;color:var(--gold-light);margin-bottom:10px}.lead-title{font-family:var(--font-display);font-size:2rem;font-weight:300;line-height:1.05;color:var(--cream);margin-bottom:10px}.lead-sub{color:var(--text-muted);line-height:1.7;margin-bottom:22px}.lead-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:22px}.lead-summary div{padding:14px 16px;border:1px solid rgba(184,147,90,.14);background:rgba(255,255,255,.02)}.lead-summary span{display:block;font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px}.lead-summary strong{font-size:1rem;font-weight:400;color:var(--sand)}.lead-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.lead-field{display:flex;flex-direction:column;gap:8px}.lead-field.full{grid-column:1/-1}.lead-field label{font-size:.68rem;letter-spacing:.18em;text-transform:uppercase;color:var(--sand)}.lead-field input,.lead-field select,.lead-field textarea{width:100%;border:1px solid rgba(184,147,90,.2);background:rgba(255,255,255,.03);color:var(--cream);padding:14px 15px;font:inherit;outline:none}.lead-field input:focus,.lead-field select:focus,.lead-field textarea:focus{border-color:var(--gold);box-shadow:0 0 0 1px rgba(184,147,90,.2)}.lead-field textarea{min-height:110px;resize:vertical}.lead-field select option{color:#111}.lead-field input[readonly]{opacity:.8;background:rgba(255,255,255,.02)}.lead-note{margin-top:18px;color:var(--text-muted);line-height:1.6}.lead-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:22px}.lead-cancel,.lead-submit{border:0;font-family:var(--font-body);font-size:.68rem;letter-spacing:.18em;text-transform:uppercase;padding:14px 22px;cursor:pointer}.lead-cancel{background:transparent;color:var(--sand);border:1px solid rgba(184,147,90,.25)}.lead-submit{background:var(--gold);color:var(--night)}.lead-cancel:hover{border-color:var(--gold);color:var(--gold)}.lead-submit:hover{background:var(--gold-light)}.lead-hidden{display:none!important}@media(max-width:700px){.lead-box{padding:28px 18px}.lead-summary,.lead-grid{grid-template-columns:1fr}.lead-actions{flex-direction:column}.lead-cancel,.lead-submit{width:100%}}
'@

$leadHtml = @'
<div class="lead-modal" id="leadModal" onclick="if(event.target===this)closeLeadModal()">
  <div class="lead-box">
    <button type="button" class="lead-close" onclick="closeLeadModal()">&times;</button>
    <p class="lead-eyebrow">Pré-réservation WhatsApp</p>
    <h2 class="lead-title">Avant de continuer sur WhatsApp</h2>
    <p class="lead-sub">Le client remplit ici ses informations avant l'ouverture de WhatsApp. Vous recevez donc un message plus complet avec les coordonnées, les dates et les preferences.</p>
    <div class="lead-summary">
      <div><span>Bien concerné</span><strong id="leadContext">Demande générale</strong></div>
      <div><span>Budget indicatif</span><strong id="leadPrice">À confirmer</strong></div>
    </div>
    <div class="lead-grid">
      <div class="lead-field"><label for="leadFirstName">Prénom</label><input type="text" id="leadFirstName" placeholder="Votre prénom"></div>
      <div class="lead-field"><label for="leadLastName">Nom</label><input type="text" id="leadLastName" placeholder="Votre nom"></div>
      <div class="lead-field"><label for="leadPhone">Numéro de téléphone</label><input type="tel" id="leadPhone" placeholder="77 000 00 00"></div>
      <div class="lead-field"><label for="leadEmail">Adresse email</label><input type="email" id="leadEmail" placeholder="votre@email.com"></div>
      <div class="lead-field full"><label for="leadAddress">Adresse</label><input type="text" id="leadAddress" placeholder="Votre adresse complète"></div>
      <div class="lead-field"><label for="leadProjectType">Type de demande</label><select id="leadProjectType"><option>Location vacances</option><option>Location longue durée</option><option>Achat immobilier</option><option>Local commercial</option><option>Renseignement général</option></select></div>
      <div class="lead-field"><label for="leadPaymentMethod">Méthode de paiement souhaitée</label><select id="leadPaymentMethod"><option>Wave</option><option>Orange Money</option><option>Virement bancaire</option><option>Carte bancaire</option><option>Espèces</option><option>À définir</option></select></div>
      <div class="lead-field lead-rental-only"><label for="leadStartDate">Date d'arrivée</label><input type="date" id="leadStartDate"></div>
      <div class="lead-field lead-rental-only"><label for="leadEndDate">Date de départ</label><input type="date" id="leadEndDate"></div>
      <div class="lead-field lead-rental-only"><label for="leadNights">Nombre de jours</label><input type="number" id="leadNights" min="1" readonly placeholder="Calculé automatiquement"></div>
      <div class="lead-field lead-rental-only"><label for="leadGuests">Nombre de personnes</label><input type="number" id="leadGuests" min="1" placeholder="Ex: 6"></div>
      <div class="lead-field lead-rental-only"><label for="leadChef">Option chef cuisinier</label><select id="leadChef"><option>Oui</option><option>Non</option><option>À confirmer</option></select></div>
      
      <div class="lead-field full"><label for="leadMessage">Informations complementaires</label><textarea id="leadMessage" placeholder="Precisez vos besoins, vos horaires, votre budget ou toute information utile."></textarea></div>
    </div>
    <p class="lead-note" id="leadNote">Aucune donnée n'est stockée sur le site. Le formulaire sert uniquement à préparer votre message WhatsApp.</p>
    <div class="lead-actions">
      <button type="button" class="lead-cancel" onclick="closeLeadModal()">Annuler</button>
      <button type="button" class="lead-submit" onclick="submitLeadWhatsApp()">Continuer sur WhatsApp</button>
    </div>
  </div>
</div>
'@

$leadScript = @'
<script>
var LEAD_WHATSAPP_NUMBER='221780140942';
var leadData={context:'Demande générale',amount:0,category:'Renseignement général'};
function formatLeadAmount(n){return Number(n).toLocaleString('fr-FR')+' FCFA';}
function normalizeLeadText(v){return (v||'').replace(/\s+/g,' ').trim();}
function normalizeLeadCategory(v){var allowed=['Location vacances','Location longue durée','Achat immobilier','Local commercial','Renseignement général'];return allowed.indexOf(v)>=0?v:'Renseignement général';}
function isRentalLeadCategory(v){return v==='Location vacances'||v==='Location longue durée';}
function parseLeadAmount(text){var clean=(text||'').replace(/\u00a0/g,' ');var match=clean.match(/(\d[\d\s]{2,})/);return match?parseInt(match[1].replace(/\s+/g,''),10)||0:0;}
function setLeadMinDates(){var today=new Date().toISOString().split('T')[0];var start=document.getElementById('leadStartDate');var end=document.getElementById('leadEndDate');if(start)start.min=today;if(end)end.min=today;}
function updateLeadDuration(){var start=document.getElementById('leadStartDate').value;var end=document.getElementById('leadEndDate').value;var nights=document.getElementById('leadNights');nights.value='';if(!start||!end)return;var startDate=new Date(start+'T00:00:00');var endDate=new Date(end+'T00:00:00');var diff=Math.round((endDate-startDate)/86400000);if(diff>0){nights.value=String(diff);}}
function updateLeadRentalFields(){var category=document.getElementById('leadProjectType').value;document.querySelectorAll('.lead-rental-only').forEach(function(el){el.classList.toggle('lead-hidden',!isRentalLeadCategory(category));});document.getElementById('leadNote').textContent=isRentalLeadCategory(category)?"Les dates, le nombre de jours, le nombre de personnes et l'option chef seront inclus dans le message WhatsApp.":"Aucune donnée n'est stockée sur le site. Le formulaire sert uniquement à préparer votre message WhatsApp.";}
function openLeadModal(context,amount,category){leadData.context=normalizeLeadText(context)||'Demande générale';leadData.amount=parseInt(amount,10)||0;leadData.category=normalizeLeadCategory(category);document.getElementById('leadContext').textContent=leadData.context;document.getElementById('leadPrice').textContent=leadData.amount?formatLeadAmount(leadData.amount):'À confirmer';document.getElementById('leadFirstName').value='';document.getElementById('leadLastName').value='';document.getElementById('leadPhone').value='';document.getElementById('leadEmail').value='';document.getElementById('leadAddress').value='';document.getElementById('leadProjectType').value=leadData.category;document.getElementById('leadPaymentMethod').value='Wave';document.getElementById('leadStartDate').value='';document.getElementById('leadEndDate').value='';document.getElementById('leadNights').value='';document.getElementById('leadGuests').value='';document.getElementById('leadChef').value='Oui';document.getElementById('leadMessage').value='';updateLeadRentalFields();setLeadMinDates();document.getElementById('leadModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLeadModal(){document.getElementById('leadModal').classList.remove('open');document.body.style.overflow='';}
function detectLeadCategory(link,context){var block=link.closest('.vcard,.rental-card,.property-card,.ld,.long-stay,.comm,.commercial,.quick-card,.contact-card,.map-copy,.cta');var haystack=normalizeLeadText((link.textContent||'')+' '+(link.getAttribute('href')||'')+' '+(context||'')).toLowerCase();if(block&&block.matches('.vcard,.rental-card'))return 'Location vacances';if(block&&block.matches('.ld,.long-stay'))return 'Location longue durée';if(block&&block.matches('.comm,.commercial'))return 'Local commercial';if(block&&block.matches('.property-card'))return 'Achat immobilier';if(/vente|acheter|terrain|dossier/.test(haystack))return 'Achat immobilier';if(/magasin|commercial|local/.test(haystack))return 'Local commercial';if(/location|villa|vacances|sejour|conditions/.test(haystack))return 'Location vacances';return 'Renseignement général';}
function detectLeadContext(link){var block=link.closest('.vcard,.rental-card,.property-card,.ld,.long-stay,.comm,.commercial,.quick-card,.contact-card,.map-copy,.cta,.contact-section,.hero');var context='Demande générale';var amount=0;if(block){var title=block.querySelector('.vname,.card-title,.ld-title,.comm-title,.cta-title,.section-title,.contact-label,.hero-title');if(title)context=normalizeLeadText(title.innerText||title.textContent);var priceNode=block.querySelector('.price-a,.price,.ld-pa,.comm-price,.price-box .price');if(priceNode)amount=parseLeadAmount(priceNode.textContent);}if(context==='Demande générale'){var href=link.getAttribute('href')||'';var queryMatch=href.match(/[?&]text=([^&]+)/);if(queryMatch){context=normalizeLeadText(decodeURIComponent(queryMatch[1]).replace(/Bonjour,?/gi,''));}}return {context:context,amount:amount,category:detectLeadCategory(link,context)};}
function submitLeadWhatsApp(){var firstName=normalizeLeadText(document.getElementById('leadFirstName').value);var lastName=normalizeLeadText(document.getElementById('leadLastName').value);var phone=normalizeLeadText(document.getElementById('leadPhone').value);var email=normalizeLeadText(document.getElementById('leadEmail').value);var address=normalizeLeadText(document.getElementById('leadAddress').value);var projectType=document.getElementById('leadProjectType').value;var paymentMethod=document.getElementById('leadPaymentMethod').value;var startDate=document.getElementById('leadStartDate').value;var endDate=document.getElementById('leadEndDate').value;var nights=document.getElementById('leadNights').value;var guests=normalizeLeadText(document.getElementById('leadGuests').value);var chef=document.getElementById('leadChef').value;var message=normalizeLeadText(document.getElementById('leadMessage').value);if(!firstName||!lastName||!phone||!email||!address){alert('Veuillez remplir le prénom, le nom, le numéro de téléphone, l\'adresse email et l\'adresse.');return;}if(isRentalLeadCategory(projectType)){if(!startDate||!endDate){alert('Veuillez sélectionner vos dates via le calendrier.');return;}if(!nights||parseInt(nights,10)<1){alert('La date de départ doit être après la date d\'arrivée.');return;}if(!guests||parseInt(guests,10)<1){alert('Veuillez indiquer le nombre de personnes.');return;}}var lines=['Bonjour Baobab Horizon, je souhaite continuer ma demande via WhatsApp.','','Bien concerné: '+leadData.context,'Type de demande: '+projectType];if(leadData.amount){lines.push('Budget indicatif: '+formatLeadAmount(leadData.amount));}if(isRentalLeadCategory(projectType)){lines.push('Date d\'arrivée: '+startDate,'Date de départ: '+endDate,'Nombre de jours: '+nights,'Nombre de personnes: '+guests,'Option chef cuisinier: '+chef);}lines.push('Méthode de paiement souhaitée: '+paymentMethod,'','Prénom: '+firstName,'Nom: '+lastName,'Téléphone: '+phone,'Email: '+email,'Adresse: '+address);if(message){lines.push('','Informations complémentaires: '+message);}window.open('https://wa.me/'+LEAD_WHATSAPP_NUMBER+'?text='+encodeURIComponent(lines.join('\n')),'_blank');closeLeadModal();}
(function(){setLeadMinDates();var projectField=document.getElementById('leadProjectType');if(projectField){projectField.addEventListener('change',updateLeadRentalFields);}var startField=document.getElementById('leadStartDate');var endField=document.getElementById('leadEndDate');if(startField)startField.addEventListener('change',function(){if(startField.value){endField.min=startField.value;}updateLeadDuration();});if(endField)endField.addEventListener('change',updateLeadDuration);document.querySelectorAll('a[href*="wa.me/'+LEAD_WHATSAPP_NUMBER+'"]').forEach(function(link){link.addEventListener('click',function(event){event.preventDefault();var info=detectLeadContext(link);openLeadModal(info.context,info.amount,info.category);});});updateLeadRentalFields();})();
</script>
'@

foreach ($file in $files) {
  $text = Get-Content $file -Raw -Encoding UTF8
  $original = $text

  if (-not $text.Contains(".lead-modal{")) {
    $text = $text.Replace("</style>", $leadCss + "`n</style>")
  }

  if (-not $text.Contains('id="leadModal"')) {
    if ($text.Contains('<div class="pay-modal"')) {
      $marker = '<div class="pay-modal"'
      $idx = $text.IndexOf($marker)
      if ($idx -ge 0) {
        $text = $text.Substring(0, $idx) + $leadHtml + "`n" + $text.Substring($idx)
      }
    } else {
      $text = $text.Replace("<footer>", $leadHtml + "`n<footer>")
    }
  }

  if (-not $text.Contains("var LEAD_WHATSAPP_NUMBER='221780140942';")) {
    $firstScript = $text.IndexOf("<script>")
    if ($firstScript -ge 0) {
      $text = $text.Substring(0, $firstScript) + $leadScript + "`n" + $text.Substring($firstScript)
    } else {
      $text = $text.Replace("</body>", $leadScript + "`n</body>")
    }
  }

  if ($text -ne $original) {
    [System.IO.File]::WriteAllText($file, $text, [System.Text.Encoding]::UTF8)
    Write-Output "updated $(Split-Path $file -Leaf)"
  } else {
    Write-Output "nochange $(Split-Path $file -Leaf)"
  }
}
