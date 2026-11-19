$ErrorActionPreference = 'Stop'
$raiz = $PSScriptRoot
Set-Location $raiz
$pythonInstalado = Join-Path $env:LOCALAPPDATA 'Programs\Python\Python312\python.exe'
if (!(Test-Path $pythonInstalado)) { throw 'Python 3.12 não encontrado. Instale-o e rode novamente.' }
& $pythonInstalado -m venv .venv
$pythonVenv = Join-Path $raiz '.venv\Scripts\python.exe'
& $pythonVenv -m pip install --upgrade pip
& $pythonVenv -m pip install -r requirements-integrations.txt
npm install --cache .npm-cache
& (Join-Path $raiz 'start-integrations.ps1')
Write-Host 'Integrações Stitch e Antigravity iniciadas.'
