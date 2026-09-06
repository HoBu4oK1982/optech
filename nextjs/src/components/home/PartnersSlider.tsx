'use client';

import Slider from 'react-slick';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import { BACKEND_URL } from '@/lib/constants';

// Слайдер партнёров — настройки один в один с React Home.js
export default function PartnersSlider({ partners }: { partners: any[] }) {
  const settings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 4,
    slidesToScroll: 2,
    initialSlide: 0,
    autoplay: true,
    responsive: [
      { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 3, infinite: true, dots: true } },
      { breakpoint: 600, settings: { slidesToShow: 2, slidesToScroll: 2, initialSlide: 2 } },
      { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1, autoplay: true, arrows: false } },
    ],
  };

  return (
    <Slider {...settings}>
      {(partners || []).map((item, idx) => (
        <img
          key={idx}
          src={`${BACKEND_URL}/assets/images/partners/${item.image}`}
          alt={item.alt || 'Наши партнёры'}
          className="articlesItemImg"
          loading="lazy"
          decoding="async"
        />
      ))}
    </Slider>
  );
}
