$ErrorActionPreference = 'Stop'
$raiz = $PSScriptRoot
$python = Join-Path $raiz '.venv\Scripts\python.exe'
if (!(Test-Path $python)) { throw 'Rode install-integrations.ps1 primeiro para instalar dependências.' }
function Servico-Ativo([string]$url) {
    try { Invoke-RestMethod $url -TimeoutSec 2 | Out-Null; return $true } catch { return $false }
}
if (!(Servico-Ativo 'http://127.0.0.1:8765/health')) {
    Start-Process -FilePath (Get-Command node).Source -ArgumentList @('integrations\stitch.mjs') -WorkingDirectory $raiz -WindowStyle Hidden
}
if (!(Servico-Ativo 'http://127.0.0.1:8766/health')) {
    Start-Process -FilePath $python -ArgumentList @('integrations\antigravity.py') -WorkingDirectory $raiz -WindowStyle Hidden
}
Start-Sleep -Seconds 2
Invoke-RestMethod 'http://127.0.0.1:8765/health' -TimeoutSec 5 | Out-Null
Invoke-RestMethod 'http://127.0.0.1:8766/health' -TimeoutSec 5 | Out-Null
