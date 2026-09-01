$targets = (Get-ChildItem -Path . -Filter *.html).FullName + (Resolve-Path .\inject_lead_modal.ps1).Path

foreach ($file in $targets) {
  $t = Get-Content $file -Raw -Encoding UTF8
  $t = $t.Replace(",ref:''", "")
  $t = [regex]::Replace($t, "function makeLeadRef\(\)\{[^}]+\}\r?\n", "")
  $t = $t.Replace("leadData.ref=makeLeadRef();", "")
  $t = $t.Replace("document.getElementById('leadRef').value=leadData.ref;", "")
  $t = $t.Replace('<div class="lead-field"><label for="leadRef">Reference</label><input type="text" id="leadRef" readonly></div>', "")
  $t = $t.Replace("'','Reference: '+leadData.ref,'Bien concerné: '+leadData.context", "'','Bien concerné: '+leadData.context")
  [System.IO.File]::WriteAllText($file, $t, [System.Text.Encoding]::UTF8)
}
