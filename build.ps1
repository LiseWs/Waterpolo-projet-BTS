# ============================================================
#  build.ps1 - Prepare le runtime Python + compile l'installeur
#  Utilise par BUILD_INSTALLER.bat (ne pas lancer directement)
# ============================================================
Set-Location $PSScriptRoot
$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "=== WATERPOLO BTS - Script de build ===" -ForegroundColor Cyan
Write-Host ""

$pyVersion = "3.12.10"
$pyZip     = "python-$pyVersion-embed-amd64.zip"
$pyUrl     = "https://www.python.org/ftp/python/$pyVersion/$pyZip"
$pyRuntime = "$PSScriptRoot\python-runtime"

# -- 1. Telecharger Python embeddable --
if (Test-Path "$pyRuntime\python.exe") {
    Write-Host "[OK] Python embeddable deja present" -ForegroundColor Green
} else {
    Write-Host "[1/5] Telechargement Python $pyVersion embeddable..." -ForegroundColor Yellow
    $pyZipPath = "$PSScriptRoot\$pyZip"
    Invoke-WebRequest -Uri $pyUrl -OutFile $pyZipPath -UseBasicParsing
    Write-Host "      Extraction..." -ForegroundColor Yellow
    Expand-Archive -Path $pyZipPath -DestinationPath $pyRuntime -Force
    Remove-Item $pyZipPath
    Write-Host "[OK] Python extrait dans python-runtime\" -ForegroundColor Green
}

# -- 2. Corriger le .pth : activer import site ET ajouter Lib\site-packages --
Write-Host "[2/5] Configuration du runtime Python..." -ForegroundColor Yellow
$pthFile = Get-ChildItem "$pyRuntime\python3*._pth" | Select-Object -First 1
if (-not $pthFile) {
    Write-Host "[ERREUR] Fichier .pth introuvable dans python-runtime\" -ForegroundColor Red
    pause; exit 1
}

# Reecrire completement le .pth avec les bonnes entrees (sans here-string pour eviter les problemes d'encodage)
$pthLines = @("python312.zip", ".", "Lib\site-packages", "", "import site")
$pthLines | Set-Content -Path $pthFile.FullName -Encoding ASCII
Write-Host "[OK] $($pthFile.Name) configure (import site + Lib\site-packages)" -ForegroundColor Green

# -- 3. Installer pip (forcer si python -m pip ne fonctionne pas) --
Write-Host "[3/5] Verification de pip..." -ForegroundColor Yellow
$pipOk = & "$pyRuntime\python.exe" -m pip --version 2>&1
if ($pipOk -notlike "*pip*") {
    Write-Host "      pip non fonctionnel, reinstallation..." -ForegroundColor Yellow
    $getPip = "$PSScriptRoot\get-pip.py"
    Invoke-WebRequest -Uri "https://bootstrap.pypa.io/get-pip.py" -OutFile $getPip -UseBasicParsing
    & "$pyRuntime\python.exe" $getPip --no-warn-script-location
    Remove-Item $getPip -ErrorAction SilentlyContinue
    $pipOk2 = & "$pyRuntime\python.exe" -m pip --version 2>&1
    if ($pipOk2 -notlike "*pip*") {
        Write-Host "[ERREUR] pip toujours non fonctionnel apres reinstallation" -ForegroundColor Red
        pause; exit 1
    }
    Write-Host "[OK] pip installe" -ForegroundColor Green
} else {
    Write-Host "[OK] pip fonctionnel : $pipOk" -ForegroundColor Green
}

# -- 4. Installer les dependances --
Write-Host "[4/5] Installation des dependances (django, waitress, openpyxl...)..." -ForegroundColor Yellow
& "$pyRuntime\python.exe" -m pip install `
    django==6.0.2 `
    openpyxl==3.1.5 `
    waitress `
    pillow `
    pyserial `
    --no-warn-script-location `
    --quiet

$pipResult = $LASTEXITCODE
if ($pipResult -ne 0) {
    Write-Host "[ERREUR] pip install a echoue (code $pipResult)" -ForegroundColor Red
    pause; exit 1
}
Write-Host "[OK] Dependances installees" -ForegroundColor Green

# -- 5. Creer l'icone .ico --
Write-Host "[5/5] Creation de l'icone .ico..." -ForegroundColor Yellow
$pngSrc = "$PSScriptRoot\Django\gestion\static\gestion\WaterpoloManager.png"
$icoDst = "$PSScriptRoot\waterpolo.ico"
if (Test-Path $pngSrc) {
    $pyCode = "from PIL import Image; img=Image.open(r'$pngSrc').convert('RGBA'); img.save(r'$icoDst',format='ICO',sizes=[(16,16),(32,32),(48,48),(256,256)]); print('OK')"
    $result = & "$pyRuntime\python.exe" -c $pyCode 2>&1
    if ("$result" -eq "OK") {
        Write-Host "[OK] waterpolo.ico cree" -ForegroundColor Green
    } else {
        Write-Host "[WARN] Icone non creee : $result" -ForegroundColor Yellow
    }
} else {
    Write-Host "[WARN] Image source introuvable, icone ignoree" -ForegroundColor Yellow
}

# -- Verification finale --
Write-Host ""
Write-Host "Verification finale..." -ForegroundColor Yellow
$test = & "$pyRuntime\python.exe" -c "import django, openpyxl, waitress; print('OK')" 2>&1
if ("$test" -eq "OK") {
    Write-Host "[OK] Tous les modules sont importables" -ForegroundColor Green
} else {
    Write-Host "[ERREUR] $test" -ForegroundColor Red
    pause; exit 1
}

Write-Host ""
Write-Host "=== RUNTIME PRET ===" -ForegroundColor Cyan
