# Generate WordPress.org launch-style banners (772x250 + 1544x500).
# Matches the Flex Listings & Booking Manager promo design.
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'generate-wporg-assets.ps1')

function Draw-GlowCard {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$W,
        [float]$H,
        [float]$Radius,
        [float]$Scale
    )

    $glowColor = [System.Drawing.Color]::FromArgb(56, 189, 248)
    foreach ($layer in @(14, 10, 6, 3)) {
        $expand = $layer * $Scale
        $alpha = [int](18 + (14 - $layer) * 6)
        $path = New-RoundedRectPath ($X - $expand) ($Y - $expand) ($W + $expand * 2) ($H + $expand * 2) ($Radius + $expand)
        $brush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb($alpha, $glowColor))
        $G.FillPath($brush, $path)
        $brush.Dispose()
        $path.Dispose()
    }

    $cardRect = New-Object System.Drawing.RectangleF $X, $Y, $W, $H
    $cardPath = New-RoundedRectPath $X $Y $W $H $Radius
    $G.FillPath((New-LinearBrush $cardRect '#1e40af88' '#0ea5e988' 135), $cardPath)
    $borderPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(180, 125, 211, 252), [float](2.5 * $Scale))
    $G.DrawPath($borderPen, $cardPath)
    $borderPen.Dispose()
    $cardPath.Dispose()
}

function Draw-LaunchBadge {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$Scale
    )

    $label = 'NEW LAUNCH'
    $fontSize = [float][Math]::Round(7.5 * $Scale)
    $font = New-Object System.Drawing.Font('Segoe UI', $fontSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $textSize = $G.MeasureString($label, $font)
    $padX = 10 * $Scale
    $padY = 5 * $Scale
    $badgeW = $textSize.Width + ($padX * 2)
    $badgeH = $textSize.Height + ($padY * 2)
    $radius = $badgeH / 2

    $path = New-RoundedRectPath $X $Y $badgeW $badgeH $radius
    $G.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(55, 255, 255, 255))), $path)
    $G.DrawPath((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(100, 186, 230, 253), [float](1 * $Scale))), $path)
    $path.Dispose()

    $G.DrawString($label, $font, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(240, 255, 255, 255))), $X + $padX, $Y + $padY)
    $font.Dispose()
    return $badgeH
}

function Draw-FeaturePill {
    param(
        [System.Drawing.Graphics]$G,
        [float]$X,
        [float]$Y,
        [float]$Scale,
        [string]$Label,
        [scriptblock]$IconDrawer
    )

    $fontSize = [float][Math]::Round(8 * $Scale)
    $font = New-Object System.Drawing.Font('Segoe UI Semibold', $fontSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $textSize = $G.MeasureString($Label, $font)
    $iconSize = 12 * $Scale
    $padX = 8 * $Scale
    $gap = 5 * $Scale
    $badgeH = 24 * $Scale
    $badgeW = ($padX * 2) + $iconSize + $gap + $textSize.Width
    $radius = $badgeH / 2

    $path = New-RoundedRectPath $X $Y $badgeW $badgeH $radius
    $G.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(45, 255, 255, 255))), $path)
    $G.DrawPath((New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(55, 255, 255, 255), [float](1 * $Scale))), $path)
    $path.Dispose()

    $iconY = $Y + ($badgeH - $iconSize) / 2
    & $IconDrawer $G ($X + $padX) $iconY $iconSize

    $textY = $Y + ($badgeH - $textSize.Height) / 2
    $G.DrawString($Label, $font, [System.Drawing.Brushes]::White, $X + $padX + $iconSize + $gap, $textY)
    $font.Dispose()
    return $badgeW
}

function Draw-MiniBuildingIcon {
    param([System.Drawing.Graphics]$G, [float]$X, [float]$Y, [float]$Size)
    $pen = New-Object System.Drawing.Pen ([System.Drawing.Color]::White, [Math]::Max(1.2, $Size * 0.12))
    $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $G.DrawRectangle($pen, $X + $Size * 0.2, $Y + $Size * 0.25, $Size * 0.6, $Size * 0.65)
    $G.DrawLine($pen, $X + $Size * 0.5, $Y + $Size * 0.08, $X + $Size * 0.5, $Y + $Size * 0.25)
    $G.DrawRectangle($pen, $X + $Size * 0.38, $Y + $Size * 0.62, $Size * 0.24, $Size * 0.28)
    $pen.Dispose()
}

function Draw-MiniCalendarIcon {
    param([System.Drawing.Graphics]$G, [float]$X, [float]$Y, [float]$Size)
    $pen = New-Object System.Drawing.Pen ([System.Drawing.Color]::White, [Math]::Max(1.2, $Size * 0.12))
    $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $G.DrawRectangle($pen, $X + $Size * 0.12, $Y + $Size * 0.22, $Size * 0.76, $Size * 0.68)
    $G.DrawLine($pen, $X + $Size * 0.12, $Y + $Size * 0.38, $X + $Size * 0.88, $Y + $Size * 0.38)
    $pen.Dispose()
}

function Draw-MiniUserIcon {
    param([System.Drawing.Graphics]$G, [float]$X, [float]$Y, [float]$Size)
    $brush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    $G.FillEllipse($brush, $X + $Size * 0.32, $Y + $Size * 0.1, $Size * 0.36, $Size * 0.36)
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $path.AddArc($X + $Size * 0.15, $Y + $Size * 0.42, $Size * 0.7, $Size * 0.55, 0, 180)
    $G.FillPath($brush, $path)
    $brush.Dispose()
    $path.Dispose()
}

function New-LaunchBanner {
    param([int]$Width, [int]$Height)

    $bmp = New-Object System.Drawing.Bitmap $Width, $Height
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    Set-Quality $g

    $s = $Height / 250.0
    $rect = New-Object System.Drawing.RectangleF 0, 0, $Width, $Height

    # Deep navy base + corner glows (matches LinkedIn promo).
    $g.FillRectangle((New-LinearBrush $rect '#060d1f' '#0f1e3d' 25), 0, 0, $Width, $Height)
    $g.FillRectangle((New-LinearBrush $rect '#0ea5e922' '#00000000' 45), 0, 0, $Width, $Height)

    $glowBL = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(55, 14, 165, 233))
    $glowTR = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(45, 59, 130, 246))
    $g.FillEllipse($glowBL, -(80 * $s), $Height - (120 * $s), 260 * $s, 200 * $s)
    $g.FillEllipse($glowTR, $Width - (200 * $s), -(60 * $s), 240 * $s, 180 * $s)
    $glowBL.Dispose()
    $glowTR.Dispose()

    # Subtle dot grid.
    $dotBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(14, 255, 255, 255))
    for ($dx = 0; $dx -lt $Width; $dx += [int](24 * $s)) {
        for ($dy = 0; $dy -lt $Height; $dy += [int](24 * $s)) {
            $g.FillEllipse($dotBrush, $dx, $dy, 1.2 * $s, 1.2 * $s)
        }
    }
    $dotBrush.Dispose()

    # Icon card (left).
    $cardSize = 148 * $s
    $cardX = 22 * $s
    $cardY = ($Height - $cardSize) / 2
    Draw-GlowCard $g $cardX $cardY $cardSize $cardSize (20 * $s) $s
    Draw-ListingIcons $g ($cardX + 24 * $s) ($cardY + 32 * $s) ($s * 0.92) ([System.Drawing.Color]::White)

    $tx = $cardX + $cardSize + (26 * $s)

    # NEW LAUNCH badge.
    $badgeH = Draw-LaunchBadge $g $tx (28 * $s) $s

    # Title.
    $titleSize = [float][Math]::Round(21 * $s)
    $titleFont = New-Object System.Drawing.Font('Segoe UI', $titleSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $titleY = 28 * $s + $badgeH + (8 * $s)
    $g.DrawString('Flex Listings & Booking Manager', $titleFont, [System.Drawing.Brushes]::White, $tx, $titleY)

    # Subtitle: "WordPress Plugin - Now Live" (ASCII dash avoids font encoding glitches).
    $subSize = [float][Math]::Round(10.5 * $s)
    $subFont = New-Object System.Drawing.Font('Segoe UI', $subSize, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
    $subBold = New-Object System.Drawing.Font('Segoe UI', $subSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $subY = $titleY + ($titleSize * 1.25)
    $part1 = 'WordPress Plugin - '
    $part2 = 'Now Live'
    $g.DrawString($part1, $subFont, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 226, 232, 240))), $tx, $subY)
    $part1W = $g.MeasureString($part1, $subFont).Width
    $g.DrawString($part2, $subBold, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 56, 189, 248))), $tx + $part1W, $subY)

    # Feature pills.
    $pillY = $subY + ($subSize * 1.85)
    $pillGap = 8 * $s
    $pillX = $tx
    $w1 = Draw-FeaturePill $g $pillX $pillY $s 'Listings' { param($Gfx, $Ix, $Iy, $Is) Draw-MiniBuildingIcon $Gfx $Ix $Iy $Is }
    $pillX += $w1 + $pillGap
    $w2 = Draw-FeaturePill $g $pillX $pillY $s 'Bookings' { param($Gfx, $Ix, $Iy, $Is) Draw-MiniCalendarIcon $Gfx $Ix $Iy $Is }
    $pillX += $w2 + $pillGap
    $null = Draw-FeaturePill $g $pillX $pillY $s 'Partner Portal' { param($Gfx, $Ix, $Iy, $Is) Draw-MiniUserIcon $Gfx $Ix $Iy $Is }

    # Integration badges.
    $intY = $pillY + (34 * $s)
    $wG = Draw-IntegrationBadge $g $tx $intY $s 'Gutenberg' '#1e293b' {
        param($Gfx, $Ix, $Iy, $Is)
        $bg = New-RoundedRectPath $Ix $Iy $Is $Is (6 * $s)
        $Gfx.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 56, 88, 233))), $bg)
        Draw-GutenbergIcon $Gfx ($Ix + 2 * $s) ($Iy + 2 * $s) ($Is - 4 * $s)
        $bg.Dispose()
    }
    $null = Draw-IntegrationBadge $g ($tx + $wG + 10 * $s) $intY $s 'Elementor' '#92003b' {
        param($Gfx, $Ix, $Iy, $Is)
        Draw-ElementorIcon $Gfx $Ix $Iy $Is '#db2777' '#ffffff'
    }

    $titleFont.Dispose()
    $subFont.Dispose()
    $subBold.Dispose()
    $g.Dispose()
    return $bmp
}

Write-Host 'Generating WordPress.org launch banners...'
Save-Png (New-LaunchBanner 772 250) (Join-Path $OutDir 'banner-772x250.png')
Save-Png (New-LaunchBanner 1544 500) (Join-Path $OutDir 'banner-1544x500.png')

foreach ($f in @('banner-772x250.png', 'banner-1544x500.png')) {
    $p = Join-Path $OutDir $f
    $i = [System.Drawing.Image]::FromFile($p)
    Write-Host "  $f -> $($i.Width)x$($i.Height)"
    $i.Dispose()
}
Write-Host 'Done.'
