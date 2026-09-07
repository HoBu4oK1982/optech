"""Генератор иконок OPTECH из фирменного знака — трёх полос логотипа.

Геометрия взята один в один из OptechLogo.tsx (элементы .cls-2 внутри
<g transform="translate(-441.87 -337.19)">), приведена к началу координат:

    полоса 1: 0,   0.00  66.73 x 29.52
    полоса 2: 0,  61.60  82.13 x 29.52
    полоса 3: 0, 125.78  96.25 x 29.50   -> общий bbox 96.25 x 155.28

Полосы выровнены по левому краю и расширяются книзу — тот же знак, что был
в исходном favicon.ico 2011 года. Зависимостей нет (ни sharp, ни PIL в
проекте не установлены), PNG пишется вручную: покрытие пикселя считается
аналитически как площадь пересечения с прямоугольником, поэтому края
сглажены точно, без супер-сэмплинга.
"""
import struct, zlib

BARS = [(0.0, 0.00, 66.73, 29.52),
        (0.0, 61.60, 82.13, 29.52),
        (0.0, 125.78, 96.25, 29.50)]
MARK_W, MARK_H = 96.25, 155.28

BLUE = (0x74, 0xad, 0xf7)   # .cls-2 логотипа
PLATE = (0x12, 0x11, 0x23)  # --dark / theme-color


def overlap(a0, a1, b0, b1):
    return max(0.0, min(a1, b1) - max(a0, b0))


def render(size, plated, mark_h_ratio):
    """RGBA-буфер size x size с знаком по центру."""
    k = (size * mark_h_ratio) / MARK_H
    mw, mh = MARK_W * k, MARK_H * k
    ox, oy = (size - mw) / 2.0, (size - mh) / 2.0

    rects = [(ox + x * k, oy + y * k, ox + (x + w) * k, oy + (y + h) * k)
             for x, y, w, h in BARS]

    rows = []
    for py in range(size):
        row = bytearray()
        for px in range(size):
            cov = 0.0
            for x0, y0, x1, y1 in rects:
                cov += overlap(px, px + 1, x0, x1) * overlap(py, py + 1, y0, y1)
            cov = min(1.0, cov)
            if plated:
                r = round(PLATE[0] + (BLUE[0] - PLATE[0]) * cov)
                g = round(PLATE[1] + (BLUE[1] - PLATE[1]) * cov)
                b = round(PLATE[2] + (BLUE[2] - PLATE[2]) * cov)
                a = 255
            else:
                r, g, b = BLUE
                a = round(cov * 255)
            row += bytes((r, g, b, a))
        rows.append(bytes(row))
    return rows


def write_png(path, rows, size):
    raw = b''.join(b'\x00' + r for r in rows)

    def chunk(tag, data):
        c = tag + data
        return struct.pack('>I', len(data)) + c + struct.pack('>I', zlib.crc32(c) & 0xffffffff)

    png = (b'\x89PNG\r\n\x1a\n'
           + chunk(b'IHDR', struct.pack('>IIBBBBB', size, size, 8, 6, 0, 0, 0))
           + chunk(b'IDAT', zlib.compress(raw, 9))
           + chunk(b'IEND', b''))
    open(path, 'wb').write(png)
    print(path, size, len(png), 'bytes')


BASE = 'public/assets/images/'
# Прозрачный фон и знак во всю высоту — как в исходном favicon.ico.
for name, size in [('favicon-16x16.png', 16), ('favicon-32x32.png', 32)]:
    write_png(BASE + name, render(size, False, 1.0), size)

# Плитка под iOS/Android: фон обязан быть, а знак — уложиться в safe zone
# maskable-иконки (центральные 80%, т.е. радиус 0.4·size). При соотношении
# знака 0.62 половина диагонали = 0.588·H, поэтому H = 0.66·size проходит.
for name, size in [('apple-touch-icon.png', 180), ('icon-192.png', 192), ('icon-512.png', 512)]:
    write_png(BASE + name, render(size, True, 0.66), size)
