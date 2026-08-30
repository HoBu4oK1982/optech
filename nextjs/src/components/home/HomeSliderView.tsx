'use client';

import Link from 'next/link';
import Slider, { type Settings } from 'react-slick';
import { BACKEND_URL } from '@/lib/constants';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';

const LOCALES = ['ru', 'en', 'kz'];

export type Slide = {
  id: number;
  image: string | null;
  alt?: string | null;
  link?: string | null;
  position?: number | null;
};

const settings: Settings = {
  dots: true,
  arrows: true,
  infinite: true,
  autoplay: true,
  autoplaySpeed: 5000,
  speed: 600,
  slidesToShow: 1,
  slidesToScroll: 1,
  pauseOnHover: true,
};


function sliderImageUrl(image: string | null): string {
  if (!image) return '';
  if (/^https?:\/\//i.test(image)) return image;
  return `${BACKEND_URL}/assets/images/sliders/${image}`;
}

// Раньше слайд-баннер всегда рендерился обычным <a href>, даже когда
// admin указывал ВНУТРЕННЮЮ ссылку (например "/catalog/telecom") — клик
// по такому баннеру вызывал полную перезагрузку страницы (весь прелоадер
// проигрывался заново), а не быструю SPA-навигацию. Теперь: относительные
// пути (внутренние) идут через next/link (+ добавляем префикс локали,
// если админ его не проставил), а внешние http(s)-ссылки остаются обычным
// <a target="_blank"> как и раньше.
function resolveSlideLink(link: string, locale: string): { href: string; external: boolean } {
  if (/^https?:\/\//i.test(link)) return { href: link, external: true };
  const path = link.startsWith('/') ? link : `/${link}`;
  const hasLocale = LOCALES.some((l) => path === `/${l}` || path.startsWith(`/${l}/`));
  return { href: hasLocale ? path : `/${locale}${path}`, external: false };
}

export default function HomeSliderView({ sliders, locale }: { sliders: Slide[]; locale: string }) {
  if (!sliders?.length) return null;

  return (
    <div className="homeSlider">
      <Slider {...settings}>
        {sliders.map((slide) => {
          const img = (
            <img
              src={sliderImageUrl(slide.image)}
              alt={slide.alt ?? ''}
              loading="lazy"
              decoding="async"
              onError={(e) => {
                (e.currentTarget as HTMLImageElement).style.display = 'none';
              }}
            />
          );

          const link = slide.link;

          return (
            <div className="homeSlider__item" key={slide.id}>
              {link ? (
                (() => {
                  const { href, external } = resolveSlideLink(link, locale);
                  return external ? (
                    <a href={href} className="homeSlider__link" target="_blank" rel="noopener noreferrer">
                      {img}
                    </a>
                  ) : (
                    <Link href={href} className="homeSlider__link">
                      {img}
                    </Link>
                  );
                })()
              ) : (
                img
              )}
            </div>
          );
        })}
      </Slider>
    </div>
  );
}