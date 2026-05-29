#!/usr/bin/env python3
"""
Génère les icônes PWA pour TuPréfères.
Usage : python generate-icons.py
Remplace ensuite public/icons/*.png par tes vraies icônes avant de publier sur le Play Store.
"""
import os

try:
    from PIL import Image, ImageDraw
except ImportError:
    print("Pillow manquant. Installation...")
    os.system("pip install pillow --break-system-packages")
    from PIL import Image, ImageDraw

SIZES   = [72, 96, 128, 144, 152, 192, 384, 512]
ACCENT  = (249, 115, 22, 255)   # #f97316 orange
BG      = (15,  15,  19,  255)  # #0f0f13 dark

os.makedirs('public/icons', exist_ok=True)

for size in SIZES:
    img  = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Fond arrondi sombre
    r = size // 5
    draw.rounded_rectangle([0, 0, size - 1, size - 1], radius=r, fill=BG)

    # Cercle accent au centre
    pad  = size // 6
    draw.ellipse([pad, pad, size - pad, size - pad], fill=ACCENT)

    # Lettre "T" blanche centrée
    font_size = size // 2
    try:
        from PIL import ImageFont
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", font_size)
    except Exception:
        font = ImageFont.load_default()

    text = "T"
    bbox = draw.textbbox((0, 0), text, font=font)
    tw   = bbox[2] - bbox[0]
    th   = bbox[3] - bbox[1]
    tx   = (size - tw) // 2 - bbox[0]
    ty   = (size - th) // 2 - bbox[1]
    draw.text((tx, ty), text, fill=(255, 255, 255, 255), font=font)

    path = f'public/icons/pwa-{size}x{size}.png'
    img.save(path)
    print(f'✅ {path}')

# apple-touch-icon (copie du 192)
img192 = Image.open('public/icons/pwa-192x192.png')
img192.save('public/icons/apple-touch-icon.png')
print('✅ public/icons/apple-touch-icon.png')

print('\nIcones générées ! Remplace-les par tes vraies icônes avant le Play Store.')
