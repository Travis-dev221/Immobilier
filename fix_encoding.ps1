$files = Get-ChildItem -Filter "*.html"
foreach ($file in $files) {
    # Read the corrupted UTF-8 string
    $corruptedString = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    try {
        # Convert the string back to bytes using Windows-1252 (the encoding Get-Content likely used)
        $win1252 = [System.Text.Encoding]::GetEncoding(1252)
        $bytes = $win1252.GetBytes($corruptedString)
        
        # Parse those bytes as UTF-8
        $fixedString = [System.Text.Encoding]::UTF8.GetString($bytes)
        
        # If it doesn't contain weird characters anymore, write it back
        [System.IO.File]::WriteAllText($file.FullName, $fixedString, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed $($file.Name)"
    } catch {
        Write-Host "Failed to fix $($file.Name)"
    }
}
