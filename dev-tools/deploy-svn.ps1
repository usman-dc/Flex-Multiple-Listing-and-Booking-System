# Deploy plugin to WordPress.org SVN (trunk + new tag).

# Requires: svn CLI, WordPress.org SVN username + SVN password.

# Usage:

#   powershell -ExecutionPolicy Bypass -File dev-tools\deploy-svn.ps1

#   powershell -ExecutionPolicy Bypass -File dev-tools\deploy-svn.ps1 -Username usmanaliwpdeveloper

# Optional env: SVN_USERNAME, SVN_PASSWORD (avoid putting password on command line in shared shells)



param(

    [string]$Username = $env:SVN_USERNAME,

    [string]$Password = $env:SVN_PASSWORD,

    [string]$PluginSlug = 'flex-multiple-listing-and-booking-system',

    [string]$Checkout = (Join-Path $env:USERPROFILE "wporg-svn-$PluginSlug"),

    [switch]$TrunkOnly

)



$ErrorActionPreference = 'Stop'



function Get-SvnExe {

    $cmd = Get-Command svn -ErrorAction SilentlyContinue

    if ($cmd) { return $cmd.Source }

    $candidates = @(

        (Join-Path $env:USERPROFILE 'svn-portable\bin\svn.exe'),

        'C:\Program Files\SlikSvn\bin\svn.exe',

        'C:\Program Files\TortoiseSVN\bin\svn.exe',

        'C:\Program Files (x86)\Subversion\bin\svn.exe'

    )

    foreach ($p in $candidates) {

        if (Test-Path $p) { return $p }

    }

    throw 'Subversion (svn) not found. Install SlikSvn/TortoiseSVN or extract Apache Subversion binaries to %USERPROFILE%\svn-portable\bin\svn.exe'

}



function Sync-WporgAssets {

    param(

        [string]$CheckoutRoot,

        [string]$SvnExe

    )



    $wporg = Join-Path $CheckoutRoot 'trunk\assets\wporg'

    $assets = Join-Path $CheckoutRoot 'assets'

    if (-not (Test-Path $wporg)) { return }



    if (-not (Test-Path $assets)) { New-Item -ItemType Directory -Path $assets -Force | Out-Null }

    Get-ChildItem -Path $wporg -Filter '*.png' | Copy-Item -Destination $assets -Force

    Remove-Item $wporg -Recurse -Force



    Push-Location $CheckoutRoot

    try {

        & $SvnExe add --force assets/*

        if (Test-Path (Join-Path $CheckoutRoot 'trunk\assets\wporg')) {

            & $SvnExe delete --force trunk/assets/wporg 2>$null

        }

    }

    finally {

        Pop-Location

    }

}



$PluginRoot = Split-Path -Parent $PSScriptRoot

$PluginName = Split-Path -Leaf $PluginRoot

$MainFile   = Join-Path $PluginRoot "$PluginName.php"



if (-not (Test-Path $MainFile)) {

    throw "Main plugin file not found: $MainFile"

}



$version = $null

foreach ($line in Get-Content $MainFile -Encoding UTF8) {

    if ($line -match '^\s*\*\s*Version:\s*(.+)$') {

        $version = $Matches[1].Trim()

        break

    }

}

if (-not $version) { throw 'Could not read Version from main plugin file.' }



$readme = Join-Path $PluginRoot 'readme.txt'

$stable = $null

foreach ($line in Get-Content $readme -Encoding UTF8) {

    if ($line -match '^Stable tag:\s*(.+)$') {

        $stable = $Matches[1].Trim()

        break

    }

}

if ($stable -ne $version) {

    throw "Version mismatch: plugin header is $version but readme Stable tag is $stable"

}



Write-Host "Release version: $version"



& (Join-Path $PSScriptRoot 'build-plugin-zip.ps1') | Out-Null



$Staging = Join-Path $env:TEMP "ulbm-svn-staging-$(Get-Random)"

$ZipPath = Join-Path (Split-Path -Parent $PluginRoot) "$PluginName.zip"

if (-not (Test-Path $ZipPath)) { throw "ZIP not found: $ZipPath" }



Expand-Archive -Path $ZipPath -DestinationPath $Staging -Force

$ReleaseRoot = Join-Path $Staging $PluginName

if (-not (Test-Path $ReleaseRoot)) { throw "Unexpected ZIP layout: $ReleaseRoot" }



$SvnExe = Get-SvnExe

$SvnUrl = "https://plugins.svn.wordpress.org/$PluginSlug"



if (-not (Test-Path (Join-Path $Checkout '.svn'))) {

    Write-Host "Checking out $SvnUrl -> $Checkout"

    & $SvnExe co $SvnUrl $Checkout

} else {

    Write-Host "Updating existing checkout: $Checkout"

    & $SvnExe up $Checkout

}



$Trunk = Join-Path $Checkout 'trunk'

if (-not (Test-Path $Trunk)) { New-Item -ItemType Directory -Path $Trunk -Force | Out-Null }



Get-ChildItem -Path $Trunk -Force | ForEach-Object {

    if ($_.Name -eq '.svn') { return }

    Remove-Item $_.FullName -Recurse -Force

}



Copy-Item -Path (Join-Path $ReleaseRoot '*') -Destination $Trunk -Recurse -Force

Sync-WporgAssets -CheckoutRoot $Checkout -SvnExe $SvnExe



Push-Location $Checkout

try {

    & $SvnExe add --force trunk/*

    & $SvnExe status



    $tagPath = "tags/$version"

    if (-not $TrunkOnly) {

        $existingTag = Join-Path $Checkout $tagPath

        if (Test-Path $existingTag) {

            & $SvnExe rm --force $tagPath 2>$null

        }

        & $SvnExe cp trunk $tagPath

    }



    $msg = if ($TrunkOnly) { "Update trunk to $version" } else { "Release $version" }

    $ciArgs = @('ci', '-m', $msg)

    if ($Username) {

        $ciArgs += '--username', $Username

        if ($Password) { $ciArgs += '--password', $Password }

    }



    Write-Host "Committing: $msg"

    & $SvnExe @ciArgs

    Write-Host "Done. WordPress.org may take up to a few hours to rebuild download ZIPs."

}

finally {

    Pop-Location

}



Remove-Item $Staging -Recurse -Force -ErrorAction SilentlyContinue


