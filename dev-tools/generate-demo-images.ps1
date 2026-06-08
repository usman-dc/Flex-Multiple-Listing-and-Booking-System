# Generates bundled demo placeholder JPEGs in assets/demo/
# Run from plugin root: powershell -File dev-tools/generate-demo-images.ps1

Add-Type -AssemblyName System.Drawing

$root = Split-Path -Parent $PSScriptRoot
$dir  = Join-Path $root 'assets/demo'
New-Item -ItemType Directory -Force -Path $dir | Out-Null

function New-DemoImage {
    param(
        [int]$Index,
        [string]$Title,
        [string]$Subtitle,
        [System.Drawing.Color]$TopColor,
        [System.Drawing.Color]$BottomColor,
        [scriptblock]$DrawScene
    )

    $w   = 1200
    $h   = 800
    $bmp = New-Object System.Drawing.Bitmap $w, $h
    $g   = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit

    $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush (
        (New-Object System.Drawing.Rectangle 0, 0, $w, $h),
        $TopColor,
        $BottomColor,
        90
    )
    $g.FillRectangle($brush, 0, 0, $w, $h)
    $brush.Dispose()

    & $DrawScene $g $w $h

    $overlay = [System.Drawing.Color]::FromArgb(140, 15, 23, 42)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush $overlay), 0, ($h - 200), $w, 200)

    $family    = [System.Drawing.FontFamily]::GenericSansSerif
    $titleFont = [System.Drawing.Font]::new($family, 42, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $subFont   = [System.Drawing.Font]::new($family, 22, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
    $white     = [System.Drawing.Brushes]::White
    $muted     = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 226, 232, 240))

    $g.DrawString($Title, $titleFont, $white, 48, ($h - 155))
    $g.DrawString($Subtitle, $subFont, $muted, 48, ($h - 88))

    $badge     = "DEMO $Index"
    $badgeFont = [System.Drawing.Font]::new($family, 14, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $badgeSize = $g.MeasureString($badge, $badgeFont)
    $bx        = $w - $badgeSize.Width - 56
    $by        = 40
    $g.FillRectangle((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(200, 255, 255, 255))), $bx - 12, $by - 6, $badgeSize.Width + 24, $badgeSize.Height + 12)
    $g.DrawString($badge, $badgeFont, (New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(30, 41, 59))), $bx, $by)

    $path = Join-Path $dir ("placeholder-{0:D2}.jpg" -f $Index)
    $encoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }
    $params  = New-Object System.Drawing.Imaging.EncoderParameters 1
    $params.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter ([System.Drawing.Imaging.Encoder]::Quality, 88L)
    $bmp.Save($path, $encoder, $params)

    $titleFont.Dispose()
    $subFont.Dispose()
    $badgeFont.Dispose()
    $muted.Dispose()
    $g.Dispose()
    $bmp.Dispose()
    Write-Output $path
}

$scenes = @(
    @{
        Title    = 'Coastal Villa'
        Subtitle = 'Oceanfront rental - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(56, 189, 248)
        Bottom   = [System.Drawing.Color]::FromArgb(14, 116, 144)
        Draw     = {
            param($g, $w, $h)
            $sand = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(180, 250, 204, 21))
            $g.FillRectangle($sand, 0, 520, $w, 280)
            $sun  = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(120, 254, 240, 138))
            $g.FillEllipse($sun, 880, 80, 180, 180)
            $house = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 255, 255, 255))
            $g.FillRectangle($house, 420, 340, 360, 180)
            $roof  = New-Object System.Drawing.Drawing2D.GraphicsPath
            $roof.AddPolygon(@(
                (New-Object System.Drawing.Point 380, 340),
                (New-Object System.Drawing.Point 600, 240),
                (New-Object System.Drawing.Point 820, 340)
            ))
            $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 248, 113, 113))), $roof)
            $sand.Dispose(); $sun.Dispose(); $house.Dispose(); $roof.Dispose()
        }
    },
    @{
        Title    = 'Mountain Cabin'
        Subtitle = 'Woodland retreat - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(129, 140, 248)
        Bottom   = [System.Drawing.Color]::FromArgb(30, 58, 138)
        Draw     = {
            param($g, $w, $h)
            $peak1 = New-Object System.Drawing.Drawing2D.GraphicsPath
            $peak1.AddPolygon(@(
                (New-Object System.Drawing.Point 0, 500),
                (New-Object System.Drawing.Point 280, 220),
                (New-Object System.Drawing.Point 560, 500)
            ))
            $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(160, 100, 116, 139))), $peak1)
            $peak2 = New-Object System.Drawing.Drawing2D.GraphicsPath
            $peak2.AddPolygon(@(
                (New-Object System.Drawing.Point 400, 520),
                (New-Object System.Drawing.Point 700, 180),
                (New-Object System.Drawing.Point 1000, 520)
            ))
            $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(180, 71, 85, 105))), $peak2)
            $cabin = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(230, 180, 83, 9))
            $g.FillRectangle($cabin, 480, 380, 240, 140)
            $peak1.Dispose(); $peak2.Dispose(); $cabin.Dispose()
        }
    },
    @{
        Title    = 'City Apartment'
        Subtitle = 'Downtown stay - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(148, 163, 184)
        Bottom   = [System.Drawing.Color]::FromArgb(51, 65, 85)
        Draw     = {
            param($g, $w, $h)
            $rng = New-Object System.Random 42
            for ($i = 0; $i -lt 9; $i++) {
                $bw = 70 + ($i % 3) * 25
                $bh = 180 + ($i % 4) * 60
                $bx = 120 + $i * 110
                $by = 520 - $bh
                $c  = [System.Drawing.Color]::FromArgb(200, 226 - ($i * 8), 232 - ($i * 8), 240 - ($i * 8))
                $g.FillRectangle((New-Object System.Drawing.SolidBrush $c), $bx, $by, $bw, $bh)
            }
            $win = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(100, 251, 191, 36)), 2
            for ($y = 0; $y -lt 8; $y++) {
                for ($x = 0; $x -lt 12; $x++) {
                    if (($x + $y) % 3 -eq 0) {
                        $g.DrawRectangle($win, (140 + $x * 78), (280 + $y * 28), 24, 18)
                    }
                }
            }
            $win.Dispose()
        }
    },
    @{
        Title    = 'Premium Sedan'
        Subtitle = 'Car hire - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(100, 116, 139)
        Bottom   = [System.Drawing.Color]::FromArgb(15, 23, 42)
        Draw     = {
            param($g, $w, $h)
            $road = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(180, 51, 65, 85))
            $g.FillRectangle($road, 0, 560, $w, 240)
            $body = New-Object System.Drawing.Drawing2D.GraphicsPath
            $body.AddBezier(280, 480, 360, 360, 840, 360, 920, 480)
            $body.AddLine(920, 480, 920, 520)
            $body.AddLine(280, 520, 280, 480)
            $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(230, 59, 130, 246))), $body)
            $wheel = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(240, 30, 41, 59))
            $g.FillEllipse($wheel, 360, 500, 90, 90)
            $g.FillEllipse($wheel, 750, 500, 90, 90)
            $road.Dispose(); $body.Dispose(); $wheel.Dispose()
        }
    },
    @{
        Title    = 'Guided Tour'
        Subtitle = 'Adventure experience - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(52, 211, 153)
        Bottom   = [System.Drawing.Color]::FromArgb(6, 95, 70)
        Draw     = {
            param($g, $w, $h)
            $trail = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(180, 254, 243, 199)), 8
            $trail.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
            $trail.EndCap   = [System.Drawing.Drawing2D.LineCap]::Round
            $g.DrawBezier($trail, 80, 600, 400, 300, 700, 500, 1100, 280)
            $pin = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(230, 239, 68, 68))
            $g.FillEllipse($pin, 1040, 240, 48, 48)
            $g.FillRectangle($pin, 1058, 280, 12, 40)
            $trail.Dispose(); $pin.Dispose()
        }
    },
    @{
        Title    = 'Wellness Spa'
        Subtitle = 'Service booking - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(196, 181, 253)
        Bottom   = [System.Drawing.Color]::FromArgb(91, 33, 182)
        Draw     = {
            param($g, $w, $h)
            $leaf = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(160, 167, 243, 208))
            for ($i = 0; $i -lt 6; $i++) {
                $g.FillEllipse($leaf, (200 + $i * 140), (180 + ($i % 2) * 40), 120, 80)
            }
            $pool = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(180, 224, 242, 254))
            $g.FillEllipse($pool, 380, 360, 440, 180)
            $leaf.Dispose(); $pool.Dispose()
        }
    },
    @{
        Title    = 'Event Venue'
        Subtitle = 'Conference and events - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(251, 191, 36)
        Bottom   = [System.Drawing.Color]::FromArgb(180, 83, 9)
        Draw     = {
            param($g, $w, $h)
            $stage = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(200, 30, 41, 59))
            $g.FillRectangle($stage, 0, 480, $w, 320)
            $arch  = New-Object System.Drawing.Drawing2D.GraphicsPath
            $arch.AddArc(360, 200, 480, 360, 180, 180)
            $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 254, 249, 195))), $arch)
            $lights = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(120, 253, 224, 71))
            for ($i = 0; $i -lt 5; $i++) {
                $g.FillEllipse($lights, (280 + $i * 160), 120, 40, 40)
            }
            $stage.Dispose(); $arch.Dispose(); $lights.Dispose()
        }
    },
    @{
        Title    = 'Beach Resort'
        Subtitle = 'Holiday package - Demo listing image'
        Top      = [System.Drawing.Color]::FromArgb(34, 211, 238)
        Bottom   = [System.Drawing.Color]::FromArgb(8, 145, 178)
        Draw     = {
            param($g, $w, $h)
            $sea = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(120, 14, 165, 233))
            $g.FillRectangle($sea, 0, 420, $w, 200)
            $palm = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(200, 22, 101, 52)), 14
            $g.DrawLine($palm, 220, 520, 220, 300)
            $g.DrawBezier($palm, 220, 320, 120, 260, 80, 200, 60, 180)
            $g.DrawBezier($palm, 220, 340, 320, 280, 360, 220, 380, 200)
            $umbrella = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 244, 63, 94))
            $g.FillEllipse($umbrella, 700, 360, 200, 100)
            $pole = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(200, 100, 116, 139)), 6
            $g.DrawLine($pole, 800, 460, 800, 540)
            $sea.Dispose(); $palm.Dispose(); $umbrella.Dispose(); $pole.Dispose()
        }
    }
)

for ($i = 0; $i -lt $scenes.Count; $i++) {
    $s = $scenes[$i]
    New-DemoImage -Index ($i + 1) -Title $s.Title -Subtitle $s.Subtitle -TopColor $s.Top -BottomColor $s.Bottom -DrawScene $s.Draw
}

Set-Content -Path (Join-Path $dir 'index.php') -Value "<?php`n// Silence is golden.`n"
Write-Output "Done. $($scenes.Count) images in $dir"
