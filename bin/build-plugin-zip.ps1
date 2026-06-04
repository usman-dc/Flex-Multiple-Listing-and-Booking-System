# Builds a WordPress.org-ready plugin ZIP (no dotfiles or dev-only assets).
# Usage: powershell -ExecutionPolicy Bypass -File bin/build-plugin-zip.ps1
# Output: ../flex-multiple-listing-and-booking-system.zip (next to the plugin folder)

$ErrorActionPreference = 'Stop'

$pluginDir  = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$pluginSlug = Split-Path $pluginDir -Leaf
$outZip     = Join-Path (Split-Path $pluginDir -Parent) ($pluginSlug + '.zip')

$excludeDirNames = @(
	'.git', '.github', '.cursor', '.idea', '.vscode', '.vite',
	'node_modules', 'vendor', 'tests', 'bin', 'dev-tools', 'coverage'
)

$excludeFileNames = @(
	'.gitignore', '.distignore', '.editorconfig', '.env',
	'phpcs.xml.dist', 'package.json', 'package-lock.json',
	'vite.config.js', 'phpunit.xml', '.phpunit.result.cache'
)

function Test-ExcludedPath {
	param( [string]$RelativePath )
	$parts = $RelativePath -split '[\\/]'
	foreach ( $part in $parts ) {
		if ( $part.StartsWith( '.' ) ) {
			return $true
		}
		if ( $excludeDirNames -contains $part ) {
			return $true
		}
	}
	$fileName = Split-Path $RelativePath -Leaf
	if ( $excludeFileNames -contains $fileName ) {
		return $true
	}
	if ( $RelativePath -match '(^|[\\/])assets[\\/]src[\\/]' ) {
		return $true
	}
	if ( $fileName -match '\.(zip|log|map)$' ) {
		return $true
	}
	return $false
}

function Copy-FilteredTree {
	param(
		[string]$SourceDir,
		[string]$DestDir,
		[string]$RelativeBase = ''
	)
	Get-ChildItem -LiteralPath $SourceDir -Force | ForEach-Object {
		$rel = if ( $RelativeBase ) { "$RelativeBase\$($_.Name)" } else { $_.Name }
		if ( Test-ExcludedPath $rel ) {
			return
		}
		$target = Join-Path $DestDir $_.Name
		if ( $_.PSIsContainer ) {
			New-Item -ItemType Directory -Path $target -Force | Out-Null
			Copy-FilteredTree -SourceDir $_.FullName -DestDir $target -RelativeBase $rel
		} else {
			Copy-Item -LiteralPath $_.FullName -Destination $target -Force
		}
	}
}

$staging   = Join-Path $env:TEMP ('ulbm-zip-' + [guid]::NewGuid().ToString())
$stageRoot = Join-Path $staging $pluginSlug
New-Item -ItemType Directory -Path $stageRoot -Force | Out-Null
Copy-FilteredTree -SourceDir $pluginDir -DestDir $stageRoot

if ( Test-Path $outZip ) {
	Remove-Item -LiteralPath $outZip -Force
}
Compress-Archive -LiteralPath $stageRoot -DestinationPath $outZip -CompressionLevel Optimal
Remove-Item -LiteralPath $staging -Recurse -Force

Write-Host "Created: $outZip"
Write-Host "Upload this file to https://wordpress.org/plugins/developers/add/"
