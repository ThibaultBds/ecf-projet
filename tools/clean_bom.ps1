# clean_bom.ps1
# Scanne le projet et supprime les BOM UTF-8 et les espaces/lignes blanches en tête de fichier
# Crée un backup .bak pour chaque fichier modifié

$root = "c:\\xampp\\htdocs\\ecoride"
Write-Output "Scanning PHP files under $root..."
$files = Get-ChildItem -Path $root -Recurse -Include *.php -File -ErrorAction SilentlyContinue
if (-not $files) {
    Write-Output "No PHP files found under $root"
    exit 0
}

foreach ($f in $files) {
    try {
        $path = $f.FullName
        $bytes = [System.IO.File]::ReadAllBytes($path)
        $origBytes = $bytes
        $changed = $false

        # Detect and remove UTF-8 BOM (0xEF,0xBB,0xBF)
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $bytes = $bytes[3..($bytes.Length - 1)]
            $changed = $true
            Write-Output "BOM found in $path"
        }

        # Convert to string using UTF8 (no BOM)
        $text = [System.Text.Encoding]::UTF8.GetString($bytes)
        $newText = $text

        # Remove any leading Unicode BOM char if present
        $newText = $newText -replace "^\uFEFF", ""

        # Remove whitespace/newlines at start of file before <?php (only if <?php exists later)
        if ($newText -match "<\?php") {
            $newText = $newText -replace "^[\s\00]*?(?=<\?php)", ""
        }

        if ($newText -ne $text) { $changed = $true }

        if ($changed) {
            # backup
            $bak = $path + ".bak"
            Copy-Item -Path $path -Destination $bak -Force
            # write with UTF8 no BOM
            [System.IO.File]::WriteAllText($path, $newText, [System.Text.Encoding]::UTF8)
            Write-Output "Fixed: $path (backup: $bak)"
        }
    } catch {
        Write-Output "Error processing $path : $_"
    }
}

Write-Output "Scan complete."
