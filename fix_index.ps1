$file = "index.html"
$content = Get-Content $file -Raw -Encoding UTF8

# The unwanted duplicate starts at line 159 (<!DOCTYPE html>) and ends just before line 318 (<!-- ACCOMPAGNEMENT -->).
# We can find the index of "<!DOCTYPE html>" after the first occurrence, or just split by lines.
$lines = $content -split "`r`n|`n"

# Check if line 158 is the start of the duplicate. Arrays are 0-indexed.
# Line 159 is index 158.
if ($lines[158] -match "<!DOCTYPE html>") {
    # Find the <!-- ACCOMPAGNEMENT --> that follows
    $endIdx = -1
    for ($i = 159; $i -lt $lines.Length; $i++) {
        if ($lines[$i] -match "<!-- ACCOMPAGNEMENT -->") {
            $endIdx = $i
            break
        }
    }
    
    if ($endIdx -gt -1) {
        $newLines = $lines[0..157] + $lines[$endIdx..($lines.Length - 1)]
        $newContent = $newLines -join "`r`n"
        Set-Content $file -Value $newContent -Encoding UTF8
        Write-Host "Fixed duplicates!"
    } else {
        Write-Host "Could not find ACCOMPAGNEMENT"
    }
} else {
    Write-Host "Line 159 is not DOCTYPE. It is: $($lines[158])"
}
