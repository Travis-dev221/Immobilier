$c = Get-Content 'index_backup.html' -Raw
$matches = [regex]::Matches($c, '(?s)<style>.*?</style>')
Write-Host "Found $($matches.Count) style blocks."
if ($matches.Count -gt 1) {
    Write-Host "Block 2: $($matches[1].Value.Substring(0, 100))"
}
if ($matches.Count -gt 2) {
    Write-Host "Block 3: $($matches[2].Value.Substring(0, 100))"
}
