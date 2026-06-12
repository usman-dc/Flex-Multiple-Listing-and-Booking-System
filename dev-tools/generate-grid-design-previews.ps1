# Generate admin preview PNGs for grid card designs (1280x800 @ 4x — sharp on retina admin UI).
# Output: assets/grid-designs/preview-{slug}.png

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing
. (Join-Path $PSScriptRoot 'generate-wporg-assets.ps1')

$OutDir = Join-Path (Split-Path -Parent $PSScriptRoot) 'assets\grid-designs'
New-Item -ItemType Directory -Path $OutDir -Force | Out-Null

$PreviewScale = 4
$BaseW = 320
$BaseH = 200
$W = $BaseW * $PreviewScale
$H = $BaseH * $PreviewScale

function Start-PreviewGraphics {
    param([System.Drawing.Bitmap]$Bmp)
    $g = [System.Drawing.Graphics]::FromImage($Bmp)
    Set-Quality $g
    $g.ScaleTransform($PreviewScale, $PreviewScale)
    return $g
}

function New-Color([string]$Hex) {
    return [System.Drawing.ColorTranslator]::FromHtml($Hex)
}

function Draw-PhotoPlaceholder {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X, [float]$Y, [float]$W, [float]$H,
        [float]$Radius = 0
    )
    $rect = New-Object System.Drawing.RectangleF $X, $Y, $W, $H
    if ($Radius -gt 0) {
        $path = New-RoundedRectPath $X $Y $W $H $Radius
        $G.SetClip($path)
    }
    $G.FillRectangle((New-LinearBrush $rect '#38bdf8' '#6366f1' 135), $X, $Y, $W, $H)
    $G.FillRectangle((New-LinearBrush $rect '#ffffff22' '#00000000' 180), $X, $Y, $W, $H * 0.55)
    $pen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(60, 255, 255, 255), 1.5)
    $G.DrawLine($pen, $X + $W * 0.15, $Y + $H * 0.72, $X + $W * 0.42, $Y + $H * 0.48)
    $G.DrawLine($pen, $X + $W * 0.42, $Y + $H * 0.48, $X + $W * 0.58, $Y + $H * 0.62)
    $G.DrawLine($pen, $X + $W * 0.58, $Y + $H * 0.62, $X + $W * 0.85, $Y + $H * 0.28)
    $sun = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(180, 255, 255, 255))
    $G.FillEllipse($sun, $X + $W * 0.72, $Y + $H * 0.12, $W * 0.14, $W * 0.14)
    $pen.Dispose(); $sun.Dispose()
    if ($Radius -gt 0) {
        $G.ResetClip()
        $path.Dispose()
    }
}

function Draw-CardText {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X, [float]$Y,
        [System.Drawing.Color]$TitleColor,
        [System.Drawing.Color]$MutedColor,
        [System.Drawing.Color]$PriceColor,
        [switch]$Compact
    )
    $titleSize = if ($Compact) { 8.5 } else { 9.5 }
    $metaSize = if ($Compact) { 7 } else { 7.5 }
    $titleFont = New-Object System.Drawing.Font('Segoe UI', $titleSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $metaFont = New-Object System.Drawing.Font('Segoe UI', $metaSize, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
    $priceFont = New-Object System.Drawing.Font('Segoe UI', 9, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $titleBrush = New-Object System.Drawing.SolidBrush $TitleColor
    $metaBrush = New-Object System.Drawing.SolidBrush $MutedColor
    $priceBrush = New-Object System.Drawing.SolidBrush $PriceColor
    $G.DrawString('Oceanview Villa', $titleFont, $titleBrush, $X, $Y)
    $G.DrawString('Malibu, CA', $metaFont, $metaBrush, $X, $Y + 14)
    $G.DrawString('$189 / night', $priceFont, $priceBrush, $X, $Y + 28)
    $titleFont.Dispose(); $metaFont.Dispose(); $priceFont.Dispose()
    $titleBrush.Dispose(); $metaBrush.Dispose(); $priceBrush.Dispose()
}

function New-MarketplacePreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::FromArgb(245, 247, 250))

    $cx = 24; $cy = 16; $cw = 272; $ch = 168
    $shadow = New-RoundedRectPath ($cx + 3) ($cy + 4) $cw $ch 12
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(28, 15, 23, 42))), $shadow)
    $shadow.Dispose()

    $card = New-RoundedRectPath $cx $cy $cw $ch 12
    $g.FillPath([System.Drawing.Brushes]::White, $card)
    $g.DrawPath((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(20, 15, 23, 42), 1)), $card)
    $card.Dispose()

    Draw-PhotoPlaceholder $g ($cx + 1) ($cy + 1) ($cw - 2) 96 11
    Draw-CardText $g ($cx + 12) ($cy + 106) ([System.Drawing.Color]::FromArgb(15, 23, 42)) ([System.Drawing.Color]::FromArgb(100, 116, 139)) ([System.Drawing.Color]::FromArgb(79, 70, 229))

    $badge = New-RoundedRectPath ($cx + $cw - 58) ($cy + 10) 48 18 9
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 79, 70, 229))), $badge)
    $badgeFont = New-Object System.Drawing.Font('Segoe UI', 7, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('Featured', $badgeFont, [System.Drawing.Brushes]::White, $cx + $cw - 52, $cy + 13)
    $badge.Dispose(); $badgeFont.Dispose()

    $g.Dispose()
    return $bmp
}

function New-MinimalPreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::White)

    $cx = 24; $cy = 16; $cw = 272; $ch = 168
    $g.DrawRectangle((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(226, 232, 240), 1)), $cx, $cy, $cw, $ch)
    Draw-PhotoPlaceholder $g ($cx + 1) ($cy + 1) ($cw - 2) 92 0
    Draw-CardText $g ($cx + 12) ($cy + 102) ([System.Drawing.Color]::FromArgb(15, 23, 42)) ([System.Drawing.Color]::FromArgb(100, 116, 139)) ([System.Drawing.Color]::FromArgb(15, 23, 42))

    $g.Dispose()
    return $bmp
}

function New-LuxuryPreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::FromArgb(248, 250, 252))

    $cx = 24; $cy = 14; $cw = 272; $ch = 172
    $shadow = New-RoundedRectPath ($cx + 2) ($cy + 6) $cw $ch 20
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(45, 15, 23, 42))), $shadow)
    $shadow.Dispose()

    $card = New-RoundedRectPath $cx $cy $cw $ch 20
    $g.FillPath([System.Drawing.Brushes]::White, $card)
    $g.SetClip($card)
    Draw-PhotoPlaceholder $g ($cx + 1) ($cy + 1) ($cw - 2) 108 0
    $g.ResetClip()
    $card.Dispose()

    Draw-CardText $g ($cx + 16) ($cy + 118) ([System.Drawing.Color]::FromArgb(15, 23, 42)) ([System.Drawing.Color]::FromArgb(100, 116, 139)) ([System.Drawing.Color]::FromArgb(79, 70, 229))

    $g.Dispose()
    return $bmp
}

function New-CommercePreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::FromArgb(245, 247, 250))
    $cx = 20; $cy = 12; $cw = 280; $ch = 176
    $card = New-RoundedRectPath $cx $cy $cw $ch 14
    $g.FillPath([System.Drawing.Brushes]::White, $card)
    $g.DrawPath((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(18, 15, 23, 42), 1)), $card)
    Draw-PhotoPlaceholder $g ($cx + 8) ($cy + 8) ($cw - 16) 88 10
    $tab = New-RoundedRectPath ($cx + 8) ($cy + 8) 52 18 8
    $g.FillPath([System.Drawing.Brushes]::White, $tab)
    $tabFont = New-Object System.Drawing.Font('Segoe UI', 7, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('RENTAL', $tabFont, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(79, 70, 229))), $cx + 14, $cy + 12)
    $titleFont = New-Object System.Drawing.Font('Segoe UI', 9, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('Oceanview Villa', $titleFont, [System.Drawing.Brushes]::Black, $cx + 14, $cy + 104)
    $badge = New-RoundedRectPath ($cx + $cw - 68) ($cy + 102) 54 18 9
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(79, 70, 229))), $badge)
    $g.DrawString('$189', $tabFont, [System.Drawing.Brushes]::White, $cx + $cw - 58, $cy + 105)
    $btn = New-RoundedRectPath ($cx + 14) ($cy + 148) ($cw - 28) 22 11
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(79, 70, 229))), $btn)
    $g.DrawString('View Details', $tabFont, [System.Drawing.Brushes]::White, $cx + 98, $cy + 152)
    $tabFont.Dispose(); $titleFont.Dispose(); $card.Dispose(); $tab.Dispose(); $badge.Dispose(); $btn.Dispose()
    $g.Dispose()
    return $bmp
}

function New-PillPreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::White)
    $cx = 24; $cy = 14; $cw = 272; $ch = 172
    $card = New-RoundedRectPath $cx $cy $cw $ch 18
    $g.FillPath([System.Drawing.Brushes]::White, $card)
    $g.DrawPath((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(15, 15, 23, 42), 1)), $card)
    Draw-PhotoPlaceholder $g ($cx + 1) ($cy + 1) ($cw - 2) 86 17
    $titleFont = New-Object System.Drawing.Font('Segoe UI', 9.5, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $metaFont = New-Object System.Drawing.Font('Segoe UI', 7.5, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
    $sz = $g.MeasureString('Oceanview Villa', $titleFont)
    $g.DrawString('Oceanview Villa', $titleFont, [System.Drawing.Brushes]::Black, $cx + ($cw - $sz.Width) / 2, $cy + 96)
    $g.DrawString('Stunning coastal retreat...', $metaFont, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(100, 116, 139))), $cx + 36, $cy + 114)
    $pill = New-RoundedRectPath ($cx + 96) ($cy + 148) 80 20 10
    $g.FillPath([System.Drawing.Brushes]::Black, $pill)
    $g.DrawString('Learn More', $metaFont, [System.Drawing.Brushes]::White, $cx + 108, $cy + 151)
    $titleFont.Dispose(); $metaFont.Dispose(); $card.Dispose(); $pill.Dispose()
    $g.Dispose()
    return $bmp
}

function New-EditorialPreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::FromArgb(245, 247, 250))
    $cx = 22; $cy = 14; $cw = 276; $ch = 172
    $card = New-RoundedRectPath $cx $cy $cw $ch 14
    $g.FillPath((New-LinearBrush (New-Object System.Drawing.RectangleF $cx, $cy, $cw, $ch) '#6366f1' '#4f46e5' 135), $card)
    $badge = New-RoundedRectPath ($cx + 16) ($cy + 16) 58 16 8
    $g.DrawPath((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(180, 255, 255, 255), 1)), $badge)
    $f = New-Object System.Drawing.Font('Segoe UI', 7, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('RENTAL', $f, [System.Drawing.Brushes]::White, $cx + 22, $cy + 19)
    $tf = New-Object System.Drawing.Font('Segoe UI', 10, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('Oceanview Villa', $tf, [System.Drawing.Brushes]::White, $cx + 16, $cy + 42)
    $g.DrawString('Short description text...', $f, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(230, 255, 255, 255))), $cx + 16, $cy + 62)
    $g.DrawString('View more', $f, [System.Drawing.Brushes]::White, $cx + 16, $cy + 148)
    $arrow = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    $g.FillEllipse($arrow, $cx + $cw - 36, $cy + 142, 20, 20)
    $g.DrawString('>', $f, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(79, 70, 229))), $cx + $cw - 28, $cy + 145)
    $f.Dispose(); $tf.Dispose(); $card.Dispose(); $badge.Dispose(); $arrow.Dispose()
    $g.Dispose()
    return $bmp
}

function New-StatsPreview {
    $bmp = New-Object System.Drawing.Bitmap $W, $H
    $g = Start-PreviewGraphics $bmp
    $g.Clear([System.Drawing.Color]::FromArgb(245, 247, 250))
    $cx = 24; $cy = 14; $cw = 272; $ch = 172
    $card = New-RoundedRectPath $cx $cy $cw $ch 12
    $g.FillPath([System.Drawing.Brushes]::White, $card)
    Draw-PhotoPlaceholder $g ($cx + 1) ($cy + 1) ($cw - 2) 72 11
    $f = New-Object System.Drawing.Font('Segoe UI', 7, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('4 days ago', $f, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(233, 30, 99))), $cx + 98, $cy + 80)
    $tf = New-Object System.Drawing.Font('Segoe UI', 9.5, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $g.DrawString('Oceanview Villa', $tf, [System.Drawing.Brushes]::Black, $cx + 78, $cy + 96)
    $bar = New-Object System.Drawing.RectangleF ($cx + 1), ($cy + $ch - 28), ($cw - 2), 27
    $g.FillRectangle((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(233, 30, 99))), $bar)
    $g.DrawString('6    4.8    $189', $f, [System.Drawing.Brushes]::White, $cx + 72, $cy + 156)
    $f.Dispose(); $tf.Dispose(); $card.Dispose()
    $g.Dispose()
    return $bmp
}

$designs = @{
    marketplace = { New-MarketplacePreview }
    minimal     = { New-MinimalPreview }
    luxury      = { New-LuxuryPreview }
    commerce    = { New-CommercePreview }
    pill        = { New-PillPreview }
    editorial   = { New-EditorialPreview }
    stats       = { New-StatsPreview }
}

foreach ($slug in $designs.Keys) {
    $bmp = & $designs[$slug]
    $path = Join-Path $OutDir "preview-$slug.png"
    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
    Write-Host "Wrote $path"
}

Write-Host 'Grid design previews generated.'
