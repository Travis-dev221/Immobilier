$files = Get-ChildItem -Path . -Recurse -Include *.html,*.php

foreach ($file in $files) {
    if ($file.FullName -match "\\admin\\" -or $file.FullName -match "panel.js") {
        continue
    }

    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $modified = $false

    # Remove Admin button variants
    if ($content -match '<a[^>]*class="nav-admin-btn"[^>]*>Admin</a>') {
        $content = $content -replace '<a[^>]*class="nav-admin-btn"[^>]*>Admin</a>', ''
        $modified = $true
    }
    if ($content -match '<button[^>]*class="nav-admin-btn"[^>]*>Admin</button>') {
        $content = $content -replace '<button[^>]*class="nav-admin-btn"[^>]*>Admin</button>', ''
        $modified = $true
    }

    if ($modified) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
    }
}
Write-Host "Admin buttons removed."
