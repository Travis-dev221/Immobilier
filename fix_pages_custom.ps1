$files = @("ventes.html", "vacances.html", "contact.html")

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # 1. LOGO
        $content = $content -replace '<img src="LOGO.jpg" alt="Baobab Horizon">', '<img src="LOGO.jpg" alt="Baobab Horizon" style="height: 95px; width: auto;">'
        
        # 2. HERO BG
        $content = $content -replace "url\('accueil.png'\) center top/100% auto no-repeat !important!important", "url('accueil.png') center center / cover no-repeat !important"
        $content = $content -replace "url\('accueil.png'\) center top/100% auto no-repeat !important", "url('accueil.png') center center / cover no-repeat !important"
        
        # 3. TEXTS - Ventes
        if ($file -eq "ventes.html") {
            $content = $content -replace '<p class="eyebrow">Accompagnement premium</p>', '<p class="eyebrow" style="color: #000; font-weight: 600;">Accompagnement premium</p>'
            $content = $content -replace '<h2 class="section-title">Acheter avec une vision claire de <em>votre projet</em></h2>', '<h2 class="section-title" style="color: #000;">Acheter avec une vision claire de <em>votre projet</em></h2>'
        }
        
        # 4. TEXTS - Vacances
        if ($file -eq "vacances.html") {
            $content = $content -replace '<p class="eyebrow">Notre sélection</p>', '<p class="eyebrow" style="color: #000; font-weight: 600;">Notre sélection</p>'
            $content = $content -replace '<h2 class="section-title">Des adresses pensées pour <em>l''exception</em></h2>', '<h2 class="section-title" style="color: #000;">Des adresses pensées pour <em>l''exception</em></h2>'
            $content = $content -replace '<p class="eyebrow">Longue durée</p>', '<p class="eyebrow" style="color: #000; font-weight: 600;">Longue durée</p>'
        }
        
        # 5. TEXTS - Contact
        if ($file -eq "contact.html") {
            $content = $content -replace '<p class="eyebrow">Coordonnées</p>', '<p class="eyebrow" style="color: #000; font-weight: 600;">Coordonnées</p>'
            $content = $content -replace '<h2 class="section-title">Une demande, une visite ou un <em>projet</em></h2>', '<h2 class="section-title" style="color: #000;">Une demande, une visite ou un <em>projet</em></h2>'
        }
        
        Set-Content -Path $file -Value $content -Encoding UTF8
    }
}
Write-Host "Modifications terminées."
