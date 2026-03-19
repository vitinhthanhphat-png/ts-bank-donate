#!/usr/bin/env pwsh
#
# build-release.ps1
# Build a release ZIP with the correct folder name (ts_bank_donate)
# and create/update a GitHub release with the ZIP attached.
#
# Usage: .\build-release.ps1 [version]
#   If no version specified, reads from ts-bank-donate.php header.
#

param(
    [string]$Version
)

$ErrorActionPreference = "Stop"
$PluginDir   = $PSScriptRoot
$PluginSlug  = "ts_bank_donate"
$MainFile    = Join-Path $PluginDir "ts-bank-donate.php"

# Auto-detect version from plugin header if not specified
if (-not $Version) {
    $header = Get-Content $MainFile -Raw
    if ($header -match "Version:\s*([\d.]+)") {
        $Version = $Matches[1]
    } else {
        Write-Error "Cannot detect version from $MainFile"
        exit 1
    }
}

Write-Host "Building release v$Version for $PluginSlug..." -ForegroundColor Cyan

# Prepare temp directory
$TempRoot = Join-Path $env:TEMP "tsbd_build_$(Get-Random)"
$TempPlugin = Join-Path $TempRoot $PluginSlug
$ZipName  = "$PluginSlug-v$Version.zip"
$ZipPath  = Join-Path $PluginDir $ZipName

# Clean previous build
if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
if (Test-Path $TempRoot) { Remove-Item $TempRoot -Recurse -Force }

# Copy plugin files (exclude dev/git files)
New-Item -ItemType Directory -Path $TempPlugin -Force | Out-Null

$excludeDirs = @(".git", "Yeu Cau", "node_modules", ".idea", ".vscode")
$excludeFiles = @(".gitignore", "REQUIREMENTS.md", "build-release.ps1", "*.log", "*.bak")

# Copy directories
Get-ChildItem $PluginDir -Directory | Where-Object {
    $excludeDirs -notcontains $_.Name
} | ForEach-Object {
    Copy-Item $_.FullName -Destination (Join-Path $TempPlugin $_.Name) -Recurse
}

# Copy files (excluding dev files and the ZIP itself)
Get-ChildItem $PluginDir -File | Where-Object {
    $name = $_.Name
    $excluded = $false
    foreach ($pattern in $excludeFiles) {
        if ($name -like $pattern) { $excluded = $true; break }
    }
    if ($name -like "*.zip") { $excluded = $true }
    -not $excluded
} | ForEach-Object {
    Copy-Item $_.FullName -Destination (Join-Path $TempPlugin $_.Name)
}

# Create ZIP
Write-Host "Creating $ZipName..." -ForegroundColor Yellow
Compress-Archive -Path $TempPlugin -DestinationPath $ZipPath -Force

# Cleanup temp
Remove-Item $TempRoot -Recurse -Force

$zipSize = [math]::Round((Get-Item $ZipPath).Length / 1KB, 1)
Write-Host "ZIP created: $ZipPath ($zipSize KB)" -ForegroundColor Green

# Check if release already exists
$releaseExists = gh release view $Version 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "Release $Version exists. Deleting and recreating..." -ForegroundColor Yellow
    gh release delete $Version --yes 2>$null
    # Also delete the tag so we can recreate it on current commit
    git tag -d $Version 2>$null
    git push origin --delete $Version 2>$null
}

# Create release with ZIP attached
Write-Host "Creating GitHub release v$Version..." -ForegroundColor Cyan
gh release create $Version $ZipPath --title "v$Version" --notes "## TS Bank Donate v$Version`n`nDownload **$ZipName** and extract to ``wp-content/plugins/``" --latest

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "Release v$Version created successfully!" -ForegroundColor Green
    Write-Host "Users can download $ZipName and extract directly to wp-content/plugins/" -ForegroundColor Cyan

    # Cleanup local ZIP
    Remove-Item $ZipPath -Force
    Write-Host "Local ZIP cleaned up." -ForegroundColor DarkGray
} else {
    Write-Error "Failed to create release"
}
