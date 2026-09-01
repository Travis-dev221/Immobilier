$bytes = [System.IO.File]::ReadAllBytes('style.css')
if ($bytes.Length -gt 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    [System.IO.File]::WriteAllBytes('style.css', $bytes[3..($bytes.Length - 1)])
    Write-Host "Removed BOM from style.css"
} else {
    Write-Host "No BOM found in style.css"
}

$files = Get-ChildItem -Filter "*.html"
foreach ($file in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    if ($bytes.Length -gt 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        [System.IO.File]::WriteAllBytes($file.FullName, $bytes[3..($bytes.Length - 1)])
        Write-Host "Removed BOM from $($file.Name)"
    }
}
