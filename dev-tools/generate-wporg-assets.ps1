# Generate WordPress.org icon and banner PNGs at required dimensions.
# Output: assets/wporg/

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$OutDir = Join-Path (Split-Path -Parent $PSScriptRoot) 'assets\wporg'
New-Item -ItemType Directory -Path $OutDir -Force | Out-Null

function New-LinearBrush {
    param(
        [System.Drawing.RectangleF]$Rect,
        [string]$ColorStart,
        [string]$ColorEnd,
        [float]$Angle = 0
    )
    $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        $Rect,
        [System.Drawing.ColorTranslator]::FromHtml($ColorStart),
        [System.Drawing.ColorTranslator]::FromHtml($ColorEnd),
        $Angle
    )
    return $brush
}

function Set-Quality {
    param([System.Drawing.Graphics]$G)
    $G.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $G.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    $G.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $G.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
}

function Draw-ListingIcons {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$Scale,
        [System.Drawing.Color]$Color
    )

    $pen = New-Object System.Drawing.Pen($Color, [Math]::Max(2, 2.8 * $Scale))
    $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.LineJoin = [System.Drawing.Drawing2D.LineJoin]::Round

    # Calendar (back)
    $calW = 52 * $Scale
    $calH = 48 * $Scale
    $calX = $X
    $calY = $Y + 8 * $Scale
    $G.DrawRectangle($pen, $calX, $calY + 10 * $Scale, $calW, $calH)
    $G.DrawLine($pen, $calX, $calY + 22 * $Scale, $calX + $calW, $calY + 22 * $Scale)
    foreach ($dx in @(14, 28, 42)) {
        $G.DrawLine($pen, $calX + $dx * $Scale, $calY, $calX + $dx * $Scale, $calY + 14 * $Scale)
    }
    foreach ($row in 0..1) {
        foreach ($col in 0..2) {
            $dotX = $calX + (12 + $col * 14) * $Scale
            $dotY = $calY + (30 + $row * 10) * $Scale
            $G.FillEllipse((New-Object System.Drawing.SolidBrush($Color)), $dotX, $dotY, 4 * $Scale, 4 * $Scale)
        }
    }

    # Building (front)
    $bX = $X + 38 * $Scale
    $bY = $Y + 2 * $Scale
    $bW = 58 * $Scale
    $bH = 62 * $Scale
    $G.DrawRectangle($pen, $bX, $bY + 12 * $Scale, $bW, $bH)
    $G.DrawLine($pen, $bX + $bW * 0.5, $bY, $bX + $bW * 0.5, $bY + 12 * $Scale)
    foreach ($row in 0..2) {
        foreach ($col in 0..1) {
            $wx = $bX + (12 + $col * 22) * $Scale
            $wy = $bY + (22 + $row * 14) * $Scale
            $G.DrawRectangle($pen, $wx, $wy, 14 * $Scale, 10 * $Scale)
        }
    }
    $G.DrawRectangle($pen, $bX + $bW * 0.38, $bY + 52 * $Scale, 14 * $Scale, 22 * $Scale)

    $pen.Dispose()
}

function Add-RoundedRect {
    param(
        [System.Drawing.Drawing2D.GraphicsPath]$Path,
        [float]$X,
        [float]$Y,
        [float]$W,
        [float]$H,
        [float]$R
    )
    $r = [Math]::Min($R, [Math]::Min($W, $H) / 2)
    $Path.AddArc($X, $Y, $r * 2, $r * 2, 180, 90)
    $Path.AddArc($X + $W - $r * 2, $Y, $r * 2, $r * 2, 270, 90)
    $Path.AddArc($X + $W - $r * 2, $Y + $H - $r * 2, $r * 2, $r * 2, 0, 90)
    $Path.AddArc($X, $Y + $H - $r * 2, $r * 2, $r * 2, 90, 90)
    $Path.CloseFigure()
}

function New-RoundedRectPath {
    param([float]$X, [float]$Y, [float]$W, [float]$H, [float]$R)
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    Add-RoundedRect $path $X $Y $W $H $R
    return $path
}

function Draw-GutenbergIcon {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$Size
    )

    $gap = $Size * 0.12
    $block = ($Size - $gap) / 2
    $brush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 255, 255))
    $accent = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(56, 88, 233))

    $G.FillRectangle($brush, $X, $Y, $block, $block)
    $G.FillRectangle($accent, $X + $block + $gap, $Y, $block, $block)
    $G.FillRectangle($accent, $X, $Y + $block + $gap, $block, $block)
    $G.FillRectangle($brush, $X + $block + $gap, $Y + $block + $gap, $block, $block)

    $brush.Dispose()
    $accent.Dispose()
}

function Draw-ElementorIcon {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$Size,
        [string]$CircleColor = '#e9207e',
        [string]$BarColor = '#ffffff'
    )

    $G.FillEllipse(
        (New-Object System.Drawing.SolidBrush ([System.Drawing.ColorTranslator]::FromHtml($CircleColor))),
        $X, $Y, $Size, $Size
    )

    $barH = $Size * 0.1
    $barW = $Size * 0.5
    $left = $X + $Size * 0.25
    $top = $Y + $Size * 0.27
    $brush = New-Object System.Drawing.SolidBrush ([System.Drawing.ColorTranslator]::FromHtml($BarColor))
    $G.FillRectangle($brush, $left, $top, $barW, $barH)
    $G.FillRectangle($brush, $left, $top + $Size * 0.21, $barW * 0.68, $barH)
    $G.FillRectangle($brush, $left, $top + $Size * 0.42, $barW, $barH)
    $brush.Dispose()
}

function Draw-IntegrationBadge {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$Scale,
        [string]$Label,
        [string]$BgColor,
        [scriptblock]$IconDrawer
    )

    $badgeH = 34 * $Scale
    $iconSize = 18 * $Scale
    $padX = 10 * $Scale
    $gap = 8 * $Scale
    $labelSize = [float][Math]::Max(9, [Math]::Round(10.5 * $Scale))
    $font = New-Object System.Drawing.Font('Segoe UI Semibold', $labelSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $labelSizeF = $G.MeasureString($Label, $font)
    $badgeW = $padX * 2 + $iconSize + $gap + $labelSizeF.Width + (4 * $Scale)
    $radius = $badgeH / 2

    $path = New-RoundedRectPath $X $Y $badgeW $badgeH $radius
    $G.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(38, [System.Drawing.ColorTranslator]::FromHtml($BgColor)))), $path)
    $borderPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(70, 255, 255, 255), [float](1.2 * $Scale))
    $G.DrawPath($borderPen, $path)
    $borderPen.Dispose()

    $iconY = $Y + ($badgeH - $iconSize) / 2
    & $IconDrawer $G ($X + $padX) $iconY $iconSize

    $textBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    $textY = $Y + ($badgeH - $labelSizeF.Height) / 2
    $G.DrawString($Label, $font, $textBrush, $X + $padX + $iconSize + $gap, $textY)

    $font.Dispose()
    $textBrush.Dispose()
    $path.Dispose()
    return $badgeW
}

function Save-Png {
    param(
        [System.Drawing.Bitmap]$Bitmap,
        [string]$Path
    )
    $Bitmap.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)
    $Bitmap.Dispose()
}

function New-PluginIcon {
    param([int]$Size)

    $bmp = New-Object System.Drawing.Bitmap $Size, $Size
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    Set-Quality $g

    $rect = New-Object System.Drawing.RectangleF 0, 0, $Size, $Size
    $g.FillRectangle((New-LinearBrush $rect '#0f172a' '#1e293b' 135), 0, 0, $Size, $Size)

    $pad = $Size * 0.1
    $inner = $Size - ($pad * 2)
    $innerRect = New-Object System.Drawing.RectangleF $pad, $pad, $inner, $inner
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $radius = $Size * 0.14
    $path.AddArc($pad, $pad, $radius * 2, $radius * 2, 180, 90)
    $path.AddArc($pad + $inner - $radius * 2, $pad, $radius * 2, $radius * 2, 270, 90)
    $path.AddArc($pad + $inner - $radius * 2, $pad + $inner - $radius * 2, $radius * 2, $radius * 2, 0, 90)
    $path.AddArc($pad, $pad + $inner - $radius * 2, $radius * 2, $radius * 2, 90, 90)
    $path.CloseFigure()
    $g.FillPath((New-LinearBrush $innerRect '#2563eb' '#1d4ed8' 45), $path)

    $scale = $Size / 128.0 * 0.72
    $iconX = $pad + ($inner - 96 * $scale) / 2
    $iconY = $pad + ($inner - 72 * $scale) / 2
    Draw-ListingIcons $g $iconX $iconY $scale ([System.Drawing.Color]::White)

    $g.Dispose()
    return $bmp
}

function New-PluginBanner {
    param([int]$Width, [int]$Height)

    $bmp = New-Object System.Drawing.Bitmap $Width, $Height
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    Set-Quality $g

    $scale = $Height / 250.0
    $rect = New-Object System.Drawing.RectangleF 0, 0, $Width, $Height
    $g.FillRectangle((New-LinearBrush $rect '#0b1220' '#1e1b4b' 35), 0, 0, $Width, $Height)
    $g.FillRectangle((New-LinearBrush $rect '#312e8100' '#2563eb44' 0), 0, 0, $Width, $Height)

    # Soft accent blobs
    $blob1 = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(42, 37, 99, 235))
    $blob2 = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(34, 219, 39, 119))
    $g.FillEllipse($blob1, -40 * $scale, -30 * $scale, 220 * $scale, 180 * $scale)
    $g.FillEllipse($blob2, $Width - (180 * $scale), $Height - (120 * $scale), 200 * $scale, 160 * $scale)
    $blob1.Dispose()
    $blob2.Dispose()

    # Glass card for listing icons
    $cardW = 188 * $scale
    $cardH = 168 * $scale
    $cardX = 34 * $scale
    $cardY = ($Height - $cardH) / 2
    $cardPath = New-RoundedRectPath $cardX $cardY $cardW $cardH (18 * $scale)
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(48, 255, 255, 255))), $cardPath)
    $cardBorder = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(90, 255, 255, 255), [float](1.5 * $scale))
    $g.DrawPath($cardBorder, $cardPath)
    $cardBorder.Dispose()
    $cardPath.Dispose()

    $iconScale = $scale * 1.05
    $iconX = $cardX + (34 * $scale)
    $iconY = $cardY + (34 * $scale)
    Draw-ListingIcons $g $iconX $iconY $iconScale ([System.Drawing.Color]::White)

    # Mini integration icons inside card footer
    $mini = 22 * $scale
    $miniY = $cardY + $cardH - (38 * $scale)
    $miniGut = New-RoundedRectPath ($cardX + 18 * $scale) $miniY $mini $mini (6 * $scale)
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 30, 41, 59))), $miniGut)
    Draw-GutenbergIcon $g ($cardX + 20 * $scale) ($miniY + 2 * $scale) ($mini - 4 * $scale)
    $miniGut.Dispose()

    $miniEl = New-RoundedRectPath ($cardX + 48 * $scale) $miniY $mini $mini (6 * $scale)
    $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 146, 0, 59))), $miniEl)
    Draw-ElementorIcon $g ($cardX + 50 * $scale) ($miniY + 2 * $scale) ($mini - 4 * $scale)
    $miniEl.Dispose()

    $titleSize = [float][Math]::Round(24 * $scale)
    $subSize = [float][Math]::Round(11 * $scale)
    $titleFont = New-Object System.Drawing.Font('Segoe UI', $titleSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $subFont = New-Object System.Drawing.Font('Segoe UI', $subSize, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
    $white = [System.Drawing.Brushes]::White
    $muted = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(210, 199, 210, 254))

    $textX = $cardX + $cardW + (32 * $scale)
    $titleY = $Height * 0.22
    $g.DrawString('Flex Listings & Booking', $titleFont, $white, $textX, $titleY)
    $g.DrawString('Manager', $titleFont, $white, $textX, $titleY + ($titleSize * 1.2))
    $dot = [char]0x00B7
    $g.DrawString("Listings  $dot  Bookings  $dot  Partner Portal", $subFont, $muted, $textX, $titleY + ($titleSize * 2.45))

    # Integration badges
    $badgeY = $titleY + ($titleSize * 3.55)
    $badgeGap = 10 * $scale
    $badgeX = $textX
    $w1 = Draw-IntegrationBadge $g $badgeX $badgeY $scale 'Gutenberg' '#1e293b' {
        param($Gfx, $Ix, $Iy, $Is)
        $bg = New-RoundedRectPath $Ix $Iy $Is $Is (5 * $scale)
        $Gfx.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 56, 88, 233))), $bg)
        Draw-GutenbergIcon $Gfx ($Ix + 2 * $scale) ($Iy + 2 * $scale) ($Is - 4 * $scale)
        $bg.Dispose()
    }
    $null = Draw-IntegrationBadge $g ($badgeX + $w1 + $badgeGap) $badgeY $scale 'Elementor' '#92003b' {
        param($Gfx, $Ix, $Iy, $Is)
        Draw-ElementorIcon $Gfx $Ix $Iy $Is
    }

    $titleFont.Dispose()
    $subFont.Dispose()
    $muted.Dispose()
    $g.Dispose()
    return $bmp
}

if ($MyInvocation.InvocationName -ne '.') {
    Write-Host "Generating WordPress.org assets in $OutDir"

    Save-Png (New-PluginIcon 128) (Join-Path $OutDir 'icon-128x128.png')
    Save-Png (New-PluginIcon 256) (Join-Path $OutDir 'icon-256x256.png')

    $bannerScript = Join-Path $PSScriptRoot 'generate-banners.ps1'
    if (Test-Path $bannerScript) {
        & $bannerScript
    } else {
        Save-Png (New-PluginBanner 772 250) (Join-Path $OutDir 'banner-772x250.png')
        Save-Png (New-PluginBanner 1544 500) (Join-Path $OutDir 'banner-1544x500.png')
    }

    foreach ($file in @('icon-128x128.png', 'icon-256x256.png', 'banner-772x250.png', 'banner-1544x500.png')) {
        $path = Join-Path $OutDir $file
        $img = [System.Drawing.Image]::FromFile($path)
        Write-Host "  $file -> $($img.Width)x$($img.Height)"
        $img.Dispose()
    }

    Write-Host 'Done.'
}
