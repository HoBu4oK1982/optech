export const dynamic = 'force-dynamic';

import { Metadata } from 'next';
import { getTranslations } from '@/i18n/translations';
import { getSettings, getPartners, getLicenses } from '@/lib/api';
import { BACKEND_URL } from '@/lib/constants';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import PartnerLogos from '@/components/info/PartnerLogos';
import LicenseGrid from '@/components/info/LicenseGrid';
import '@/components/info/infoPages.css';

// Аккуратные stroke-иконки (тонкая обводка, currentColor) вместо эмодзи.
const ICONS: Record<string, React.ReactNode> = {
  distribution: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z" />
      <path d="M3 7.5v9L12 21l9-4.5v-9" />
      <path d="M12 12v9" />
    </svg>
  ),
  integration: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6 3 3 6-6a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z" />
    </svg>
  ),
  support: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M12 3 2 8l10 5 10-5-10-5Z" />
      <path d="M6 10.5V15c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5" />
      <path d="M22 8v5" />
    </svg>
  ),
};

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  const desc =
    params.locale === 'en'
      ? 'OPTECH — distributor and integrator of technological solutions in Kazakhstan: measuring instruments, telecom and engineering equipment from leading manufacturers.'
      : params.locale === 'kz'
      ? 'OPTECH — Қазақстандағы технологиялық шешімдердің дистрибьюторы және интеграторы: өлшеу аспаптары, телеком және инженерлік жабдық.'
      : 'OPTECH — дистрибьютор и интегратор технологических решений в Казахстане: измерительные приборы, телеком- и инженерное оборудование от ведущих производителей.';
  return {
    title: `${t.nav.about} — OPTECH`,
    description: desc,
    openGraph: { title: `${t.nav.about} — OPTECH`, description: desc },
  };
}

// Локализованный контент страницы. Держим прямо здесь (а не в общем dict),
// т.к. это разовый маркетинговый текст конкретной страницы.
function content(locale: string) {
  if (locale === 'en') {
    return {
      kicker: 'About the company',
      title: ['We supply the ', 'technologies', ' industry runs on'],
      lead: 'OPTECH is a distributor and integrator of technological solutions. We deliver measuring instruments, telecom and engineering equipment from the world\u2019s leading manufacturers to businesses across Kazakhstan and Central Asia — with expert selection, official warranty and full technical support.',
      stats: [
        { num: '11', suf: '', label: 'years of expertise in optical technologies' },
        { num: '1000', suf: '+', label: 'units of equipment sold' },
        { num: '30', suf: '+', label: 'regular clients and partners' },
        { num: '100', suf: '+', label: 'completed projects' },
      ],
      whatEyebrow: 'What we do',
      whatTitle: 'Equipment, expertise, integration',
      cards: [
        { icon: 'distribution', title: 'Distribution', text: 'Official supply of measuring, telecom and engineering equipment with warranty and documentation.' },
        { icon: 'integration', title: 'Integration', text: 'We design and assemble ready-to-run solutions for the client\u2019s task, not just sell boxes.' },
        { icon: 'support', title: 'Support', text: 'Consulting, commissioning, staff training and after-sales service.' },
      ],
      partners: 'Partners & manufacturers',
      licenses: 'Certificates & licenses',
    };
  }
  if (locale === 'kz') {
    return {
      kicker: 'Компания туралы',
      title: ['Индустрия сүйенетін ', 'технологияларды', ' жеткіземіз'],
      lead: 'OPTECH — технологиялық шешімдердің дистрибьюторы және интеграторы. Біз Қазақстан мен Орталық Азия бизнесіне әлемнің жетекші өндірушілерінің өлшеу аспаптарын, телеком және инженерлік жабдығын жеткіземіз — сараптамалық таңдау, ресми кепілдік және толық техникалық қолдаумен.',
      stats: [
        { num: '11', suf: '', label: 'оптикалық технологиялардағы тәжірибе жылы' },
        { num: '1000', suf: '+', label: 'бірлік жабдық сатылды' },
        { num: '30', suf: '+', label: 'тұрақты клиент пен серіктес' },
        { num: '100', suf: '+', label: 'жүзеге асырылған жоба' },
      ],
      whatEyebrow: 'Біз не істейміз',
      whatTitle: 'Жабдық, сараптама, интеграция',
      cards: [
        { icon: 'distribution', title: 'Дистрибуция', text: 'Өлшеу, телеком және инженерлік жабдықты кепілдік пен құжаттамамен ресми жеткізу.' },
        { icon: 'integration', title: 'Интеграция', text: 'Клиенттің міндетіне дайын шешімдерді жобалаймыз және құрастырамыз.' },
        { icon: 'support', title: 'Қолдау', text: 'Кеңес беру, іске қосу, қызметкерлерді оқыту және сатудан кейінгі қызмет.' },
      ],
      partners: 'Серіктестер мен өндірушілер',
      licenses: 'Сертификаттар мен лицензиялар',
    };
  }
  return {
    kicker: 'О компании',
    title: ['Поставляем ', 'технологии', ', на которых работает индустрия'],
    lead: 'OPTECH — дистрибьютор и интегратор технологических решений. Мы поставляем бизнесу Казахстана и Центральной Азии измерительные приборы, телеком- и инженерное оборудование от ведущих мировых производителей — с экспертным подбором, официальной гарантией и полной технической поддержкой.',
    stats: [
      { num: '11', suf: '', label: 'лет опыта в оптических технологиях и решениях' },
      { num: '1000', suf: '+', label: 'единиц оборудования продано' },
      { num: '30', suf: '+', label: 'постоянных клиентов и партнёров' },
      { num: '100', suf: '+', label: 'реализованных проектов' },
    ],
    whatEyebrow: 'Чем мы занимаемся',
    whatTitle: 'Оборудование, экспертиза, интеграция',
    cards: [
      { icon: 'distribution', title: 'Дистрибуция', text: 'Официальная поставка измерительного, телеком- и инженерного оборудования с гарантией и документами.' },
      { icon: 'integration', title: 'Интеграция', text: 'Проектируем и собираем готовые решения под задачу клиента, а не просто продаём «коробки».' },
      { icon: 'support', title: 'Поддержка', text: 'Консультации, ввод в эксплуатацию, обучение персонала и сервис после продажи.' },
    ],
    partners: 'Партнёры и производители',
    licenses: 'Сертификаты и лицензии',
  };
}

export default async function AboutPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const c = content(locale);
  const [partners, licenses] = await Promise.all([
    getPartners().catch(() => []),
    getLicenses().catch(() => []),
  ]);

  return (
    <div className="infoPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.about }]} />

        <section className="infoHero infoHero--wide">
          <h1 className="infoHero__title">
            {c.title[0]}
            <span>{c.title[1]}</span>
            {c.title[2]}
          </h1>
          <p className="infoHero__lead">{c.lead}</p>
        </section>

        <div className="infoStats">
          {c.stats.map((s) => (
            <div className="infoStat" key={s.label}>
              <div className="infoStat__num">
                {s.num}
                <em>{s.suf}</em>
              </div>
              <div className="infoStat__label">{s.label}</div>
            </div>
          ))}
        </div>

        <section className="infoSection">
          <span className="infoSection__eyebrow">{c.whatEyebrow}</span>
          <h2 className="infoSection__title">{c.whatTitle}</h2>
          <div className="infoCards">
            {c.cards.map((card) => (
              <article className="infoCard" key={card.title}>
                <div className="infoCard__icon" aria-hidden>{ICONS[card.icon] || null}</div>
                <h3 className="infoCard__title">{card.title}</h3>
                <p className="infoCard__text">{card.text}</p>
              </article>
            ))}
          </div>
        </section>

        {partners && partners.length > 0 && (
          <section className="infoSection">
            <h2 className="infoSection__title">{c.partners}</h2>
            <PartnerLogos
              items={partners.map((p: any) => ({
                id: p.id,
                src: `${BACKEND_URL}/assets/images/partners/${p.image}`,
                alt: p.alt || '',
              }))}
            />
          </section>
        )}

        {licenses && licenses.length > 0 && (
          <section className="infoSection">
            <h2 className="infoSection__title">{c.licenses}</h2>
            <LicenseGrid
              items={licenses.map((l: any) => ({
                id: l.id,
                src: `${BACKEND_URL}/assets/images/licenses/${l.image}`,
                alt: l.alt || l.type || '',
              }))}
            />
          </section>
        )}
      </div>
    </div>
  );
}
