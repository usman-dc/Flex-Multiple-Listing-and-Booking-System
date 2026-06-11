# Builds a WordPress.org-ready ZIP using .distignore rules.
# Output: ../flex-multiple-listing-and-booking-system.zip (sibling of plugin folder)

$ErrorActionPreference = 'Stop'

$PluginRoot = Split-Path -Parent $PSScriptRoot
$PluginName = Split-Path -Leaf $PluginRoot
$DistIgnore = Join-Path $PluginRoot '.distignore'
$OutZip     = Join-Path (Split-Path -Parent $PluginRoot) "$PluginName.zip"
$Staging    = Join-Path $env:TEMP "ulbm-zip-staging-$(Get-Random)"

function Test-DistIgnoreMatch {
    param(
        [string]$RelativePath,
        [string[]]$Patterns
    )
    $rel = $RelativePath -replace '\\', '/'
    foreach ($raw in $Patterns) {
        $line = $raw.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { continue }

        if ($line -match '^\*\.([a-z0-9]+)$') {
            $ext = $Matches[1]
            if ($rel -like "*.$ext") { return $true }
            continue
        }

        $pat = $line.TrimStart('/')
        if ($pat.EndsWith('/')) {
            $dir = $pat.TrimEnd('/')
            if ($rel -eq $dir -or $rel.StartsWith("$dir/")) { return $true }
            continue
        }

        if ($rel -eq $pat -or $rel -like $pat) { return $true }
        if ($rel -like "*/$pat") { return $true }
    }
    return $false
}

$patterns = @()
if (Test-Path $DistIgnore) {
    $patterns = Get-Content $DistIgnore -Encoding UTF8
}

# Exclude hidden files/folders (WordPress.org guidance).
$patterns += '/.git/'
$patterns += '/.gitignore'
$patterns += '/.distignore'
$patterns += '/.cursor/'
$patterns += '/.github/'

Write-Host "Staging plugin to: $Staging"
New-Item -ItemType Directory -Path (Join-Path $Staging $PluginName) -Force | Out-Null
$DestRoot = Join-Path $Staging $PluginName

Get-ChildItem -Path $PluginRoot -Force | ForEach-Object {
    $rel = $_.Name
    if (Test-DistIgnoreMatch -RelativePath $rel -Patterns $patterns) {
        Write-Host "  skip: $rel"
        return
    }
    Copy-Item -Path $_.FullName -Destination (Join-Path $DestRoot $rel) -Recurse -Force
}

function Remove-ExcludedRecursive {
    param([string]$Dir)

    Get-ChildItem -Path $Dir -Recurse -Force | Sort-Object { $_.FullName.Length } -Descending | ForEach-Object {
        $full = $_.FullName
        $rel  = $full.Substring($DestRoot.Length + 1) -replace '\\', '/'
        if (Test-DistIgnoreMatch -RelativePath $rel -Patterns $patterns) {
            if ($_.PSIsContainer) {
                Remove-Item $full -Recurse -Force -ErrorAction SilentlyContinue
            } else {
                Remove-Item $full -Force -ErrorAction SilentlyContinue
            }
        }
    }
}

Remove-ExcludedRecursive -Dir $DestRoot

if (Test-Path $OutZip) {
    Remove-Item $OutZip -Force
}

Write-Host "Creating ZIP: $OutZip"
Compress-Archive -Path (Join-Path $Staging $PluginName) -DestinationPath $OutZip -CompressionLevel Optimal

Remove-Item $Staging -Recurse -Force

# Verify dev-tools not in archive.
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($OutZip)
$bad = $zip.Entries | Where-Object { $_.FullName -match '(^|/)dev-tools/' }
$zip.Dispose()

if ($bad) {
    Write-Error 'ZIP still contains dev-tools/ - check .distignore'
}

$sizeMb = [math]::Round((Get-Item $OutZip).Length / 1MB, 2)
Write-Host ('Done. ' + $OutZip + ' (' + $sizeMb + ' MB) - dev-tools excluded.')
