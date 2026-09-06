// ISR: статическая страница, пересборка не чаще раза в час.
export const revalidate = 3600;

import { Metadata } from 'next';
import { buildMetadata } from '@/lib/seo';
import { getTranslations } from '@/i18n/translations';
import { getSettings } from '@/lib/api';
import Breadcrumbs from '@/components/ui/Breadcrumbs';
import ContactForm from '@/components/ui/ContactForm';
import '@/components/info/infoPages.css';

// Фиксированные контакты OPTECH (из официальных реквизитов). Телефоны и
// e-mail можно позже вынести в настройки; координаты офиса в Алматы —
// с карты, предоставленной заказчиком.
const OFFICE = {
  // Координаты здания 83/1 (не 83/5 — метка была ошибочно севернее).
  lat: 43.168832,
  lng: 76.873993,
  // ID организации Optech в 2ГИС (ЖК Аскар Тау, Кенесары хана 83/1, офис 1Б).
  gis2Id: '70000001080959925',
  phones: ['+7 (727) 338 35 08', '+7 (771) 759-59-49'],
  email: 'info@optech.kz',
  addressRu: 'г. Алматы, ул. Кенесары хана, 83/1, 1 этаж, офис 1Б',
  addressEn: 'Almaty, Kenesary khana st. 83/1, 1st floor, office 1B',
  addressKz: 'Алматы қ., Кенесары хана к-сі, 83/1, 1-қабат, 1Б кеңсе',
  workRu: 'Пн–Пт: 9:00–18:00',
  workEn: 'Mon–Fri: 9:00–18:00',
  workKz: 'Дс–Жм: 9:00–18:00',
  routeUrl: 'https://go.2gis.com/sijyi',
};

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const t = getTranslations(params.locale);
  const desc =
    params.locale === 'en'
      ? 'Contact OPTECH: phone, email, office address in Almaty and a request form. We\u2019ll help you choose equipment and prepare a quote.'
      : params.locale === 'kz'
      ? 'OPTECH байланыстары: телефон, email, Алматыдағы кеңсе мекенжайы және өтінім формасы.'
      : 'Контакты OPTECH: телефон, email, адрес офиса в Алматы и форма заявки. Поможем подобрать оборудование и подготовить коммерческое предложение.';
  return buildMetadata({
    locale: params.locale,
    path: '/contacts',
    fallbackTitle: t.nav.contacts,
    fallbackDescription: desc,
  });
}

function labels(locale: string) {
  if (locale === 'en') {
    return {
      kicker: 'Contacts',
      title: ['Let\u2019s discuss ', 'your task'],
      lead: 'Call, email or leave a request — we\u2019ll help you choose the right equipment, check availability and prepare a commercial quote.',
      phone: 'Phone',
      email: 'Email',
      address: 'Office',
      work: 'Working hours',
      route: 'Get directions',
      formTitle: 'Leave a request',
      formSub: 'Fill in the form and we\u2019ll get back to you shortly.',
      address_value: OFFICE.addressEn,
      work_value: OFFICE.workEn,
    };
  }
  if (locale === 'kz') {
    return {
      kicker: 'Байланыс',
      title: ['Міндетіңізді ', 'талқылайық'],
      lead: 'Қоңырау шалыңыз, жазыңыз немесе өтінім қалдырыңыз — жабдықты таңдауға, қолжетімділікті тексеруге және коммерциялық ұсыныс дайындауға көмектесеміз.',
      phone: 'Телефон',
      email: 'Email',
      address: 'Кеңсе',
      work: 'Жұмыс уақыты',
      route: 'Бағыт салу',
      formTitle: 'Өтінім қалдыру',
      formSub: 'Форманы толтырыңыз, жақын арада хабарласамыз.',
      address_value: OFFICE.addressKz,
      work_value: OFFICE.workKz,
    };
  }
  return {
    kicker: 'Контакты',
    title: ['Обсудим ', 'вашу задачу'],
    lead: 'Позвоните, напишите или оставьте заявку — поможем подобрать оборудование, проверим наличие и подготовим коммерческое предложение.',
    phone: 'Телефон',
    email: 'Email',
    address: 'Офис',
    work: 'Часы работы',
    route: 'Построить маршрут',
    formTitle: 'Оставить заявку',
    formSub: 'Заполните форму — свяжемся с вами в ближайшее время.',
    address_value: OFFICE.addressRu,
    work_value: OFFICE.workRu,
  };
}

export default async function ContactsPage({ params }: { params: { locale: string } }) {
  const { locale } = params;
  const t = getTranslations(locale);
  const l = labels(locale);
  const settings = await getSettings().catch(() => null);

  const email = settings?.email || OFFICE.email;
  const routeUrl = OFFICE.routeUrl;
  // Встраиваемая карта 2ГИС с меткой на самой организации Optech (по её ID
  // в 2ГИС), а не просто на доме — метка встаёт ровно на офис, как в 2ГИС.
  const mapSrc = `https://widgets.2gis.com/widget?type=firmsonmap&options=${encodeURIComponent(
    JSON.stringify({
      pos: { lat: OFFICE.lat, lon: OFFICE.lng, zoom: 17 },
      opt: { city: 'almaty' },
      org: OFFICE.gis2Id,
    })
  )}`;

  return (
    <div className="infoPage">
      <div className="container">
        <Breadcrumbs items={[{ label: t.nav.home, href: `/${locale}` }, { label: t.nav.contacts }]} />

        <section className="infoHero infoHero--wide">
          <h1 className="infoHero__title">
            {l.title[0]}
            <span>{l.title[1]}</span>
          </h1>
          <p className="infoHero__lead">{l.lead}</p>
        </section>

        <div className="contactsLayout">
          <div>
            <div className="contactCards">
              <div className="contactCard">
                <div className="contactCard__head">
                  <span className="contactCard__icon" aria-hidden>{'\u260E'}</span>
                  <span className="contactCard__label">{l.phone}</span>
                </div>
                <div className="contactCard__phones">
                  {OFFICE.phones.map((phone) => (
                    <a key={phone} href={`tel:${phone.replace(/[^\d+]/g, '')}`} className="contactCard__value">
                      {phone}
                    </a>
                  ))}
                </div>
              </div>

              <div className="contactCard">
                <div className="contactCard__head">
                  <span className="contactCard__icon" aria-hidden>{'\u2709'}</span>
                  <span className="contactCard__label">{l.email}</span>
                </div>
                <a href={`mailto:${email}`} className="contactCard__value">{email}</a>
              </div>

              <div className="contactCard contactCard--wide">
                <div className="contactCard__head">
                  <span className="contactCard__icon" aria-hidden>{'\uD83D\uDCCD'}</span>
                  <span className="contactCard__label">{l.address}</span>
                </div>
                <span className="contactCard__value" style={{ fontSize: '1.15rem' }}>{l.address_value}</span>
                <span className="contactCard__sub">{l.work}: {l.work_value}</span>
                <span className="contactCard__sub">
                  <a href={routeUrl} target="_blank" rel="noopener noreferrer" style={{ color: '#0847a2', fontWeight: 700 }}>
                    {l.route} →
                  </a>
                </span>
              </div>
            </div>

            <div className="contactFormCard">
              <h2 className="contactFormCard__title">{l.formTitle}</h2>
              <p className="contactFormCard__sub">{l.formSub}</p>
              <ContactForm locale={locale} />
            </div>
          </div>

          <div className="contactMap">
            <iframe
              className="contactMap__frame"
              src={mapSrc}
              title="OPTECH — карта офиса"
              loading="lazy"
              allowFullScreen
            />
          </div>
        </div>
      </div>
    </div>
  );
}
