// ISR: страница пересобирается не чаще раза в 5 минут. Раньше здесь стоял
// force-dynamic — он не только рендерил страницу на каждый запрос, но и
// отключал кэш fetch (next.revalidate в lib/api.ts игнорировался), так что
// каждый заход бота бил в Laravel напрямую.
export const revalidate = 300;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import Image from 'next/image';
import Link from 'next/link';
import { getTranslations } from '@/i18n/translations';
import { makeT } from '@/i18n/dict';
import { getAllCategories, getArticles, getSolCategories, getPartners, getSettings } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import HomeSlider from '@/components/home/HomeSlider';
import PartnersSlider from '@/components/home/PartnersSlider';

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const tm = getTranslations(params.locale);

  // default_meta_title / default_meta_description из админки (в трёх языковых
  // вариантах) задумывались как тайтл и описание главной, но на фронте не
  // читались — главная всегда показывала строку, зашитую в i18n. Шаблон в
  // layout здесь не помогает: title.default применяется только к страницам,
  // которые тайтл не задают, а главная задаёт его сама.
  const settings = await getSettings().catch(() => null);
  const suffix = params.locale === 'en' ? '_en' : params.locale === 'kz' ? '_kz' : '';
  const pick = (base: string) =>
    (suffix && settings?.[base + suffix]) || settings?.[base] || null;

  return buildMetadata({
    locale: params.locale,
    path: '',
    fallbackTitle: pick('default_meta_title') || tm.meta.homeTitle,
    fallbackDescription: pick('default_meta_description') || tm.meta.homeDescription,
    // Тайтл главной уже содержит «OPTECH» — шаблон `%s | OPTECH` из layout
    // дал бы «OPTECH — ... | OPTECH».
    titleAbsolute: true,
  });
}

export default async function HomePage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = makeT(locale);
  const L = (path: string) => `/${locale}${path}`;

  let categories: any[] = [];
  let articles: any[] = [];
  let solcategories: any[] = [];
  let partners: any[] = [];

  try {
    [categories, articles, solcategories, partners] = await Promise.all([
      getAllCategories(locale),
      getArticles(locale),
      getSolCategories(locale),
      getPartners(),
    ]);
  } catch (error) {
    console.error('Failed to fetch home data:', error);
  }

  const viewArticles = (articles || []).slice(0, 4).map((item: any, idx: number) => {
    let updatedHtmlContent = '';
    if (item.description != null) {
      updatedHtmlContent = item.description.split(' ').slice(0, 12).join(' ');
    }
    return (
      <Link key={idx} href={L(`/article/${item.slug}`)} className="articlesItem">
        <div className="articlesItemImgWrap">
          <img
            src={`${BACKEND_URL}/assets/images/articles/${item.image}`}
            alt={item.image_alt || item.title}
            title={item.image_title || undefined}
            className="articlesItemImg"
            loading="lazy"
            decoding="async"
          />
        </div>
        <div className="articlesItemContent">
          <h4>{item.title}</h4>
          <div className="articlesItemContentText" dangerouslySetInnerHTML={{ __html: updatedHtmlContent }}></div>
        </div>
      </Link>
    );
  });

  const viewSolCategories = (solcategories || []).map((item: any, idx: number) => (
    <Link key={idx} href={L(`/solutions/${item.slug}`)} className="solutionItem">
      <Image
        src={`${BACKEND_URL}/assets/images/solcategories/${item.image}`}
        alt={item.image_alt || `${item.title}`}
        title={item.image_title || undefined}
        width={293}
        height={189}
        className="solutionItemImg"
      />
      <p>{`${item.title}`}</p>
    </Link>
  ));

  const tm = getTranslations(locale);

  return (
    <main>
      {/* На главной не было <h1> вообще — только h2/h3. Заголовок скрыт
          визуально, чтобы не трогать существующий макет с слайдером во всю
          ширину, но он полноценно участвует в разметке страницы. */}
      <h1 className="visuallyHidden">{tm.meta.homeH1}</h1>

      <section>
        <div className="slider">
          <HomeSlider />
        </div>
      </section>

      <section className="homeBestWrap">
        <div className="container">
          <div className="homeBestCategories">
            {(categories || []).slice(0, -1).map((category: any) => (
              <div className="homeBestCatItem" key={category.id}>
                <h3>{category.name}</h3>
                <img
                  src={`${BACKEND_URL}/assets/images/categories/${category.image}`}
                  alt={category.image_alt || category.name}
                  title={category.image_title || undefined}
                  loading="lazy"
                  decoding="async"
                />
                <Link href={L(`/catalog/${category.slug}`)} className="homeBestBtn">
                  {t('go_to_section')}
                </Link>
              </div>
            ))}
          </div>
          <div className="homeBestAboutNumbers">
            <div className="homeBestAbout">
              <h2 className="homeH2">{t('about_page_title')}</h2>
              <div className="homeBestAboutContent">
                <p>
                  Компания ТОО «Оптические Технологии» — поставщик и интегратор технологических решений в области телекоммуникаций, энергетики и промышленной автоматизации. Компания реализует проекты для заказчиков, помогая предприятиям различных отраслей в Казахстане и странах Центральной Азии повысить операционную эффективность. Благодаря большому опыту и постоянному совершенствованию у компании сформировались отличительные преимущества, которые позволило стать официальным дистрибьютором ведущих мировых производителей.<br /><br />
                  ТОО «Оптические Технологии» сотрудничает с такими мировыми компаниями как Anritsu Corporation, Sumitomo Electric, Yokogawa Electric Corporation, Aaronia AG, Fluke, Rohde&amp;Schwarz, Triathlon Batterien GmbH и тд.
                </p>
                <Link href={L('/about')}>
                  <div className="homeBestBtn">{t('go_to_section')}</div>
                </Link>
              </div>
            </div>
            <div className="homeBestNumbers">
              <h2 className="homeH2">{t('bests_in_numbers_title')}</h2>
              <div className="homeBestNumberItemWrap">
                <div className="homeBestNumberItem">
                  <div className="homeBestNumberNum">11</div>
                  <p><strong>11 лет</strong> опыта в оптических <br /> технологиях и решениях</p>
                </div>
                <div className="homeBestNumberItem">
                  <div className="homeBestNumberNum">1000+</div>
                  <p>Проданно свыше <strong>1000</strong><br /> оборудования</p>
                </div>
                <div className="homeBestNumberItem">
                  <div className="homeBestNumberNum">30+</div>
                  <p>Более <strong>30</strong> постоянных<br />клиентов и партнеров</p>
                </div>
                <div className="homeBestNumberItem">
                  <div className="homeBestNumberNum">100+</div>
                  <p>Более <strong>100</strong> Реализованных<br /> проектов</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="container">
        <div className="specialService">
          <div className="special">
            <Link href={L('/offers')}>
              <Image src="/assets/images/special1.jpg" alt="Cпециальные предложения" width={640} height={244} />
              <div className="specialText">
                <p>{t('special_offers_title')}</p>
              </div>
            </Link>
          </div>
          <div className="special">
            <Link href={L('/services')}>
              <Image src="/assets/images/special2.jpg" alt="Сервис и услуги" width={640} height={244} />
              <div className="specialText">
                <p>{t('service_page_title')}</p>
              </div>
            </Link>
          </div>
        </div>
      </section>

      <section className="container">
        <div className="solutionsWrap">
          <h2 className="homeH2">{t('solutions_page_title')}</h2>
        </div>
        <div className="solutionContent">{viewSolCategories}</div>
      </section>

      <section className="container">
        <div className="articlesWrap">
          <h2 className="homeH2">{t('news_page_title')}</h2>
        </div>
        <div className="articlesContent">{viewArticles}</div>
        <div className="homeNewsBtn">
          <Link href={L('/articles')}>
            <div className="homeBestBtn">{t('news_page_title')}</div>
          </Link>
        </div>
      </section>

      <section>
        <div className="partnersWrap">
          <h2 className="homeH2">{t('partners_homepage_title')}</h2>
        </div>
        <div className="slider-container container">
          <PartnersSlider partners={partners} />
        </div>
      </section>
    </main>
  );
}
