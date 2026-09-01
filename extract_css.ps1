$files = Get-ChildItem -Filter "*.html"
$indexContent = Get-Content "index.html" -Raw

$styleRegex = '(?s)<style>.*?</style>'
$match = [regex]::Match($indexContent, $styleRegex)

if ($match.Success) {
    $fullStyleBlock = $match.Value
    $cssContent = $fullStyleBlock.Substring(7, $fullStyleBlock.Length - 15)
    
    Set-Content -Path "style.css" -Encoding UTF8 -Value $cssContent
    Write-Host "style.css created."
    
    foreach ($file in $files) {
        $content = Get-Content $file.FullName -Raw
        if ($content -match $styleRegex) {
            $content = $content -replace '(?s)<style>.*?</style>', ''
            $content = $content -replace '</head>', "<link rel=`"stylesheet`" href=`"style.css`">`r`n</head>"
            Set-Content -Path $file.FullName -Encoding UTF8 -Value $content
            Write-Host "Updated $($file.Name)"
        }
    }
} else {
    Write-Host "No <style> block found in index.html"
}
