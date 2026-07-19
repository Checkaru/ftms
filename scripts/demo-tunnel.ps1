# Exposes the local app at a public https://*.trycloudflare.com URL for demos.
#
#   powershell -ExecutionPolicy Bypass -File scripts\demo-tunnel.ps1
#
# Needs: php on PATH, cloudflared on PATH (https://github.com/cloudflare/cloudflared),
# MySQL already running, and assets built (npm run build).
# The URL changes on every start and dies when this window closes.

$root = Split-Path -Parent $PSScriptRoot

function Resolve-Tool([string] $name, [string[]] $fallbacks) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    foreach ($p in $fallbacks) { if (Test-Path $p) { return $p } }
    Write-Error "$name not found on PATH."; exit 1
}

$php = Resolve-Tool 'php' @("$env:USERPROFILE\toolchain\php\php.exe")
$cloudflared = Resolve-Tool 'cloudflared' @("$env:USERPROFILE\toolchain\cloudflared.exe")

# If `npm run dev` was left running, pages point assets at the local Vite dev
# server — invisible to remote visitors. Force built assets for the demo.
if (Test-Path "$root\public\hot") {
    Remove-Item "$root\public\hot" -Force
    Write-Host "Removed public\hot (Vite dev mode) — demo uses built assets."
}

if (-not (Test-Path "$root\public\build\manifest.json")) {
    Write-Error "No built assets found - run 'npm run build' first."; exit 1
}

$up = (Test-NetConnection 127.0.0.1 -Port 8000 -WarningAction SilentlyContinue).TcpTestSucceeded
if (-not $up) {
    $env:PHP_CLI_SERVER_WORKERS = '6'
    Start-Process -FilePath $php -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8000' -WorkingDirectory $root -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

Write-Host "App on http://127.0.0.1:8000 - starting tunnel (watch for the trycloudflare.com URL). Ctrl+C stops it."
& $cloudflared tunnel --url http://127.0.0.1:8000
