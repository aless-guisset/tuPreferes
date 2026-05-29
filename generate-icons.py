#!/usr/bin/env python3
"""
Génère les icônes PWA pour TuPréfères.
Zéro dépendance externe — Python pur uniquement.
Usage : python generate-icons.py
"""
import struct
import zlib
import os
import math

SIZES  = [72, 96, 128, 144, 152, 192, 384, 512]
ORANGE = (249, 115, 22)   # #f97316
DARK   = (15,  15,  19)   # #0f0f13
WHITE  = (255, 255, 255)

os.makedirs('public/icons', exist_ok=True)

def clamp(v): return max(0, min(255, v))

def make_png(width, height, pixels):
    """Génère un PNG RGB depuis un tableau de pixels (r,g,b) par ligne."""
    def chunk(ctype, data):
        c = ctype + data
        return struct.pack('>I', len(data)) + c + struct.pack('>I', zlib.crc32(c) & 0xFFFFFFFF)

    sig  = b'\x89PNG\r\n\x1a\n'
    ihdr = chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0))

    raw = b''
    for row in pixels:
        raw += b'\x00' + bytes([v for px in row for v in px])

    idat = chunk(b'IDAT', zlib.compress(raw, 9))
    iend = chunk(b'IEND', b'')
    return sig + ihdr + idat + iend

def make_icon(size):
    pixels = []
    cx = cy = size / 2
    r_outer = size / 2           # rayon fond arrondi
    r_circle = size * 0.38       # rayon cercle orange
    corner   = size / 5          # rayon coins arrondis

    for y in range(size):
        row = []
        for x in range(size):
            dx, dy = x - cx, y - cy

            # Coins arrondis : teste si le pixel est dans le rectangle arrondi
            nx = abs(dx) - (cx - corner)
            ny = abs(dy) - (cy - corner)
            in_rect = not (nx > 0 and ny > 0 and math.hypot(nx, ny) > corner)

            if not in_rect:
                row.append((0, 0, 0))   # transparent → noir (sera ignoré, fond transparent)
                continue

            dist = math.hypot(dx, dy)

            if dist <= r_circle:
                # Cercle orange avec léger dégradé
                t  = dist / r_circle
                rr = clamp(int(ORANGE[0] - t * 20))
                gg = clamp(int(ORANGE[1] - t * 15))
                bb = clamp(int(ORANGE[2] + t * 10))
                row.append((rr, gg, bb))
            else:
                row.append(DARK)

        pixels.append(row)
    return pixels

for size in SIZES:
    pixels = make_icon(size)
    data   = make_png(size, size, pixels)
    path   = f'public/icons/pwa-{size}x{size}.png'
    with open(path, 'wb') as f:
        f.write(data)
    print(f'✅  {path}')

# apple-touch-icon = copie du 192
import shutil
shutil.copy('public/icons/pwa-192x192.png', 'public/icons/apple-touch-icon.png')
print('✅  public/icons/apple-touch-icon.png')
print('\nDone! Replace icons with your real logo before publishing to the Play Store.')
