/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    // AVIF/WebP вместо исходных JPEG/PNG из Laravel — основная экономия
    // трафика и главный рычаг для LCP.
    formats: ['image/avif', 'image/webp'],
    // Картинки товаров/категорий меняются редко, а оптимизация каждой стоит
    // процессорного времени: держим результат в кэше сутки.
    minimumCacheTTL: 60 * 60 * 24,
    remotePatterns: [
      {
        // Боевой бэкенд. Раньше здесь стоял optech.kz:8000 — такого хоста нет,
        // и оптимизация картинок не заработала бы вообще.
        protocol: 'https',
        hostname: 'api.optech.kz',
        pathname: '/**',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/**',
      },
    ],
  },
}

module.exports = nextConfig
