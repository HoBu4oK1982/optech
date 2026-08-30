import { API_URL } from '@/lib/constants';
import HomeSliderView, { type Slide } from './HomeSliderView';

type GetSliderResponse = {
  status: number;
  sliders?: Slide[];
};

export default async function HomeSlider({ locale = 'ru' }: { locale?: string }) {
  let sliders: Slide[] = [];

  try {
    const res = await fetch(`${API_URL}/getSlider`, {
      // ISR: кэшируем ответ на 5 минут (подстрой под свою частоту обновления)
      next: { revalidate: 300 },
      headers: { 'Accept-Language': locale },
    });

    if (res.ok) {
      const data = (await res.json()) as GetSliderResponse;
      if (data.status === 200 && Array.isArray(data.sliders)) {
        sliders = data.sliders;
      }
    }
  } catch (err) {
    // тихо деградируем: слайдер просто не покажется, страница не падает
    console.error('Ошибка загрузки слайдов:', err);
  }

  if (sliders.length === 0) return null;

  return <HomeSliderView sliders={sliders} locale={locale} />;
}