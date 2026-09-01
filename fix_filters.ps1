$files = @("index.html", "vacances.html", "ventes.html")

foreach ($file in $files) {
    $content = Get-Content $file -Raw -Encoding UTF8
    
    # Fix vacances.html filter
    if ($file -eq "vacances.html") {
        $content = $content -replace "data\[k\]\.in_vacances \|\| data\[k\]\.section==='location'", "(data[k].in_vacances === true || (data[k].in_vacances !== false && data[k].section === 'location'))"
    }
    
    # Fix ventes.html filter
    if ($file -eq "ventes.html") {
        $content = $content -replace "data\[k\]\.in_ventes \|\| data\[k\]\.section==='vente'", "(data[k].in_ventes === true || (data[k].in_ventes !== false && data[k].section === 'vente'))"
    }
    
    # Fix index.html filter
    if ($file -eq "index.html") {
        # Note: in index.html, the script is formatted slightly differently
        $content = $content -replace "data\[k\]\.in_accueil \|\| data\[k\]\.section === 'accueil'", "(data[k].in_accueil === true || (data[k].in_accueil !== false && data[k].section === 'accueil'))"
    }
    
    Set-Content $file -Value $content -Encoding UTF8
    Write-Host "Updated $file"
}
