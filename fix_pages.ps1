$files = "ventes.html", "vacances.html", "contact.html", "hotel.html"
foreach ($file in $files) {
    if (Test-Path $file) {
        $content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

        # 1. Remove admin link
        $content = $content -replace '<a href="admin/" class="nav-admin-btn"[^>]*>Admin</a>', ''

        # 2. Fix Hero Section
        # Replace eyebrow -> hero-eye
        $content = $content -replace 'class="eyebrow"', 'class="hero-eye"'
        # Replace hero-title -> hero-h1
        $content = $content -replace 'class="hero-title"', 'class="hero-h1"'
        # Add hero-ov and wrap content in hero-cnt > hero-title-block
        $content = $content -replace '<div class="hero-content">', '<div class="hero-ov"></div><div class="hero-cnt"><div class="hero-title-block">'
        # We need to add the closing div for hero-cnt, which means replacing </div></section> with </div></div></section>
        $content = $content -replace '</div></section>', '</div></div></section>'

        [System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed $file"
    }
}
