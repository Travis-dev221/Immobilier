$files = "ventes.html", "vacances.html", "contact.html", "hotel.html"
foreach ($file in $files) {
    if (Test-Path $file) {
        $content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

        # Fix the accidentally renamed eyebrow inside intro
        $content = $content -replace '<p class="hero-eye">Accompagnement premium</p>', '<p class="intro-label">Accompagnement premium</p>'
        
        # In contact.html it might be different text
        $content = $content -replace '<p class="hero-eye">Coordonn', '<p class="intro-label">Coordonn'
        $content = $content -replace '<p class="hero-eye">Parlons', '<p class="hero-eye">Parlons' # this one is in hero, keep it! wait, I should just target <section class="intro">
        $content = $content -replace '<section class="intro"><div><p class="hero-eye">', '<section class="intro"><div><p class="intro-label">'
        $content = $content -replace '<section class="contact-section"><div><p class="hero-eye">', '<section class="contact-section"><div><p class="intro-label">'

        [System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed label in $file"
    }
}
