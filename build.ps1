# ============================================================
#  build.ps1 — Construction automatique de WaterPolo-BTS
#  Lance ce script UNE FOIS pour préparer tout l'environnement.
#  Ensuite ouvre installer.iss dans Inno Setup et appuie sur F9.
# ============================================================
Set-Location $PSScriptRoot
$ErrorActionPreference = "Stop"

Write-Host "`n=== WATERPOLO BTS — Script de build ===" -ForegroundColor Cyan

# ── 1. Télécharger Python embeddable ─────────────────────────────────────────
$pyVersion  = "3.12.10"
$pyZip      = "python-$pyVersion-embed-amd64.zip"
$pyUrl      = "https://www.python.org/ftp/python/$pyVersion/$pyZip"
$pyRuntime  = "$PSScriptRoot\python-runtime"
$pyZipPath  = "$PSScriptRoot\$pyZip"

if (Test-Path "$pyRuntime\python.exe") {
    Write-Host "[OK] Python embeddable déjà présent" -ForegroundColor Green
} else {
    Write-Host "[1/5] Téléchargement Python $pyVersion embeddable..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri $pyUrl -OutFile $pyZipPath -UseBasicParsing
    Write-Host "[2/5] Extraction..." -ForegroundColor Yellow
    Expand-Archive -Path $pyZipPath -DestinationPath $pyRuntime -Force
    Remove-Item $pyZipPath
    Write-Host "[OK] Python extrait dans python-runtime\" -ForegroundColor Green
}

# ── 2. Activer pip (décommenter import site dans le .pth) ────────────────────
$pthFile = Get-ChildItem "$pyRuntime\python*.._pth" | Select-Object -First 1
if ($pthFile) {
    $content = Get-Content $pthFile.FullName
    $fixed   = $content | ForEach-Object { $_ -replace "^#import site", "import site" }
    $fixed | Set-Content $pthFile.FullName -Encoding ASCII
    Write-Host "[OK] import site activé dans $($pthFile.Name)" -ForegroundColor Green
}

# ── 3. Installer pip ──────────────────────────────────────────────────────────
$getPip = "$PSScriptRoot\get-pip.py"
if (-not (Test-Path "$pyRuntime\Scripts\pip.exe")) {
    Write-Host "[3/5] Installation de pip..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri "https://bootstrap.pypa.io/get-pip.py" -OutFile $getPip -UseBasicParsing
    & "$pyRuntime\python.exe" $getPip --no-warn-script-location
    Remove-Item $getPip -ErrorAction SilentlyContinue
    Write-Host "[OK] pip installé" -ForegroundColor Green
} else {
    Write-Host "[OK] pip déjà présent" -ForegroundColor Green
}

# ── 4. Installer les dépendances du projet ────────────────────────────────────
Write-Host "[4/5] Installation des dépendances..." -ForegroundColor Yellow
& "$pyRuntime\python.exe" -m pip install `
    django `
    openpyxl `
    waitress `
    --no-warn-script-location `
    --quiet
Write-Host "[OK] Dépendances installées" -ForegroundColor Green

# ── 5. Vérification finale ────────────────────────────────────────────────────
Write-Host "[5/5] Vérification..." -ForegroundColor Yellow
$test = & "$pyRuntime\python.exe" -c "import django, openpyxl, waitress; print('OK')" 2>&1
if ($test -eq "OK") {
    Write-Host "[OK] Tous les modules fonctionnent" -ForegroundColor Green
} else {
    Write-Host "[ERREUR] $test" -ForegroundColor Red
}

Write-Host "`n=== ENVIRONNEMENT PRÊT ===" -ForegroundColor Cyan
Write-Host "Prochaine étape :"
Write-Host "  1. Ouvre Inno Setup Compiler"
Write-Host "  2. File > Open > installer.iss"
Write-Host "  3. Build > Compile  (ou F9)"
Write-Host "  4. Le setup.exe est dans Output\"
Write-Host ""
pause
