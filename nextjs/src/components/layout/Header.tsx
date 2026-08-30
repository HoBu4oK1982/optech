'use client';

import { useEffect, useState, forwardRef, useImperativeHandle, useRef } from 'react';
import { createPortal } from 'react-dom';
import Link from 'next/link';
import { useRouter, usePathname } from 'next/navigation';
import { motion, AnimatePresence } from 'framer-motion';
import { useForm } from 'react-hook-form';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { API_URL, BACKEND_URL } from '@/lib/constants';
import { makeT, renderWithBr } from '@/i18n/dict';
import MegaMenu from './MegaMenu';
import SmartSearch from '@/components/search/SmartSearch';
import MobileSearchOverlay from '@/components/search/MobileSearchOverlay';
import MobileNavDrawer from './MobileNavDrawer';
import OptechLogo from '@/components/ui/OptechLogo';

export default function Header({
  locale,
  settings = {},
  categories = [],
}: {
  locale: string;
  settings?: any;
  categories?: any[];
}) {
  const t = makeT(locale);
  const router = useRouter();
  const pathname = usePathname();

  const [openMenu, setOpenMenu] = useState(false);
  const [openMenu2, setOpenMenu2] = useState(false);
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
  const [isSticky, setIsSticky] = useState(false);
  const { register, handleSubmit, reset } = useForm();
  const modalRef = useRef<any>(null);

  const L = (path: string) => `/${locale}${path}`;

  useEffect(() => {
    const handleScroll = () => setIsSticky(window.scrollY > 200);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // открытие модалки заявки из плавающей панели (FloatingNav)
  useEffect(() => {
    const open = () => modalRef.current?.open();
    window.addEventListener('optech:open-request', open);
    return () => window.removeEventListener('optech:open-request', open);
  }, []);


  const menuHandler = () => setOpenMenu(!openMenu);
  const menuHandler2 = () => setOpenMenu2(!openMenu2);

  const onSubmit = async (data: any) => {
    try {
      const res = await fetch(`${API_URL}/setOrder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      const r = await res.json();
      if (r.status === 200) {
        toast.success(t('your_order_success'));
        reset();
        window.setTimeout(() => modalRef.current?.close(), 2000);
      }
    } catch {
      toast.error(t('your_order_error'));
    }
  };

  const changeLanguage = (e: any) => {
    const next = e.target.value;
    const rest = pathname.replace(/^\/(ru|en|kz)/, '') || '/';
    if (typeof window !== 'undefined') window.dispatchEvent(new Event('optech:pt-start'));
    router.push(`/${next}${rest}`);
  };


  return (
    <header>
      <Modal ref={modalRef}>
        <h2>{t('set_your_order_title')}</h2>
        <form onSubmit={handleSubmit(onSubmit)}>
          <fieldset className="inputField">
            <i className="fa-solid fa-user"></i>
            <input required className="modalInput" type="text" id="name" placeholder={t('modal_name')} autoComplete="off" {...register('name')} />
          </fieldset>
          <fieldset className="inputField">
            <i className="fa-solid fa-phone-volume"></i>
            <input required className="modalInput" type="tel" id="phone" autoComplete="off" placeholder={t('modal_phone')} {...register('phone')} />
          </fieldset>
          <fieldset className="inputField">
            <i className="fas fa-envelope"></i>
            <input required className="modalInput" type="email" id="email" autoComplete="off" placeholder={t('modal_email')} {...register('email')} />
          </fieldset>
          <fieldset className="inputField">
            <i className="fas fa-comment"></i>
            <textarea required className="modalInput modalTextarea" id="comment" autoComplete="off" placeholder={t('modal_comment')} {...register('comment')} />
          </fieldset>
          <button type="submit" className="modalInputBtn">{t('send_request_btn')}</button>
          <ToastContainer position="top-center" autoClose={5000} hideProgressBar={false} newestOnTop={false} closeOnClick rtl={false} pauseOnFocusLoss draggable pauseOnHover />
          <div className="modalClose" onClick={() => modalRef.current?.close()}>x</div>
        </form>
      </Modal>

      <div className="headerHeadTopWrap">
        <div className="container">
          <div className="headerHeadTop">
            <nav>
              <ul>
                <li><Link href={L('/license')}>{t('licence_page_title')}</Link></li>
                <li><Link href={L('/about')}>{t('about_page_title')}</Link></li>
                <li><Link href={L('/contacts')}>{t('contacts_page_title')}</Link></li>
              </ul>
            </nav>
            <div className="headerHeadTopRight">
              <div className="headerHeadLink">
                <i className="fas fa-phone-volume" aria-hidden="true"></i>
                <a href={`tel:${settings.phone}`}>{settings.phone}</a>
              </div>
              <div className="headerHeadLink">
                <i className="fas fa-envelope" aria-hidden="true"></i>
                <a href={`mailto:${settings.email}`}>{settings.email}</a>
              </div>
              <div className="headerLang">
                <select className="selectLang" onChange={changeLanguage} value={locale}>
                  <option value="ru">РУ</option>
                  <option value="en">EN</option>
                  <option value="kz">КЗ</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="headerHeadWrap">
        <div className="container">
          <div className="headerHead">
            <div className="headerLogo">
              <Link href={L('/')}>
                <OptechLogo width={266} height={52} className="headerLogoMobile" />
              </Link>
              <p>{t('slogan')}</p>
              <div className="logoSloganLong">{t('distributor_slogan')}</div>
            </div>
            <div className={locale === 'kz' ? 'headerHeadNav kazakhLanguage' : 'headerHeadNav'}>
              <nav>
                <ul>
                  <li>
                    <Link href={L('/')} className="">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M575.8 255.5c0 18-15 32.1-32 32.1l-32 0 .7 160.2c0 2.7-.2 5.4-.5 8.1l0 16.2c0 22.1-17.9 40-40 40l-16 0c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1L416 512l-24 0c-22.1 0-40-17.9-40-40l0-24 0-64c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32 14.3-32 32l0 64 0 24c0 22.1-17.9 40-40 40l-24 0-31.9 0c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2l-16 0c-22.1 0-40-17.9-40-40l0-112c0-.9 0-1.9 .1-2.8l0-69.7-32 0c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z" /></svg>
                    </Link>
                  </li>
                  <li><Link href={L('/brands')} className="">{t('brands_page_title')}</Link></li>
                  <li><Link href={L('/projects')} className="">{t('projects_page_title')}</Link></li>
                  <li><Link href={L('/articles')} className="">{t('news_page_title')}</Link></li>
                  <li><Link href={L('/basa-znani')} className="">{t('knowledge_base_page_title')}</Link></li>
                  <li><Link href={L('/solutions')} className="">{t('solutions_page_title')}</Link></li>
                  <li><Link href={L('/services')} className="">{t('service_page_title')}</Link></li>
                </ul>
              </nav>
            </div>
            <div className="headerHeadBtn" onClick={() => modalRef.current?.open()}>
              {renderWithBr(t('submit_btn')).map((p, i) => (p === null ? <br key={i} /> : <span key={i}>{p}</span>))}
            </div>
          </div>
        </div>
      </div>

      <div className="headerNavSearchWrap">
        <div className="container">
          <div className="headerNavSearch">
            <MegaMenu
              categories={categories}
              locale={locale}
              backendUrl={BACKEND_URL}
              label={t('catalog_page_title')}
            />
            <div className="headerSmartSearch">
              <SmartSearch locale={locale} placeholder={t('search_by_catalog')} />
            </div>

            {/* Мобильная компактная полоса — язык, затем контакты в 2 строки
                (line-height:1), затем лупа поиска, всё в один горизонтальный
                ряд. Заменяет собой нагромождение отдельных строк
                (лицензии/о нас/контакты, телефон, email, язык, слоган),
                которые раньше просто "проседали" вниз на узких экранах —
                см. .headerMobileBar в response.css. Ссылки на лицензии/о
                нас/контакты при этом перенесены в панель «Меню». */}
            <div className="headerMobileBar">
              <div className="headerMobileBar__lang">
                <select className="selectLang" onChange={changeLanguage} value={locale}>
                  <option value="ru">РУ</option>
                  <option value="en">EN</option>
                  <option value="kz">КЗ</option>
                </select>
              </div>

              <div className="headerMobileBar__text">
                <a href={`tel:${settings.phone}`}>
                  <i className="fas fa-phone-volume" aria-hidden="true"></i>
                  {settings.phone}
                </a>
                <a href={`mailto:${settings.email}`}>
                  <i className="fas fa-envelope" aria-hidden="true"></i>
                  {settings.email}
                </a>
              </div>

              <button
                type="button"
                className="headerSearchMobileBtn"
                onClick={() => setMobileSearchOpen(true)}
                aria-label={t('search_by_catalog')}
              >
                <svg viewBox="0 0 512 512" aria-hidden>
                  <path
                    fill="currentColor"
                    d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"
                  />
                </svg>
              </button>
            </div>

            {/* Слоган — тонкой строкой под компактным рядом (в сам ряд
                физически не влезал без переноса). */}
            <div className="headerMobileSlogan">{t('distributor_slogan')}</div>
          </div>
        </div>
      </div>

      {mobileSearchOpen && (
        <MobileSearchOverlay
          locale={locale}
          placeholder={t('search_by_catalog')}
          onClose={() => setMobileSearchOpen(false)}
        />
      )}

      <div className={`${openMenu2 ? 'modalOverlayMy' : ''}`} onClick={menuHandler2}></div>

      <div className="headerMobile">
        <div className="headerMobileCatalog">
          <div className="headerCatalogBtnMobile">
            <div className={`header__burger ${openMenu ? 'active' : ''}`} onClick={menuHandler}><span></span></div>
            {/* Левый бургер открывает НОВОЕ мегаменю (аккордеон с картинками):
                категории верхнего уровня раскрывают детей, без перехода. */}
            <MegaMenu
              categories={categories}
              locale={locale}
              backendUrl={BACKEND_URL}
              label={t('catalog_page_title')}
              controlled
              externalOpen={openMenu}
              onClose={() => setOpenMenu(false)}
            />
          </div>
        </div>
        <div className="headerMobileData">
          <div className="headerMobileDataBtn" onClick={menuHandler2}>Меню</div>
          <MobileNavDrawer
            open={openMenu2}
            onClose={() => setOpenMenu2(false)}
            items={[
              { href: L('/'), label: '', icon: 'home' },
              { href: L('/brands'), label: t('brands_page_title') },
              { href: L('/projects'), label: t('projects_page_title') },
              { href: L('/articles'), label: t('news_page_title') },
              { href: L('/basa-znani'), label: t('knowledge_base_page_title') },
              { href: L('/solutions'), label: t('solutions_page_title') },
              { href: L('/services'), label: t('service_page_title') },
              { href: L('/license'), label: t('licence_page_title') },
              { href: L('/about'), label: t('about_page_title') },
              { href: L('/contacts'), label: t('contacts_page_title') },
            ]}
            onRequestClick={() => modalRef.current?.open()}
            requestLabel={t('submit_btn').replace(/<\d+\s*\/>/g, ' ')}
          />
        </div>
      </div>
    </header>
  );
}

const Modal = forwardRef((props: any, ref) => {
  const [openModal, setOpenModal] = useState(false);
  // Рендерим модалку через портал прямо в document.body — раньше она была
  // инлайн-потомком <header>, и КАКОЙ БЫ ни был z-index у backdrop, шапка
  // (кнопка каталога, поиск и т.д.) технически могла оставаться поверх неё
  // из-за собственных стекинг-контекстов элементов шапки. Портал убирает
  // саму возможность такого конфликта: модалка больше не потомок шапки,
  // а прямой child body, как и мега-меню в MegaMenu.tsx (тот же паттерн).
  const [mounted, setMounted] = useState(false);
  // .modal-content-wrapper — overflow-y:auto (форма может быть выше видимой
  // области). Автозаполнение браузера / автофокус на поле может само
  // проскроллить эту область к какому-то полю в середине формы — тогда
  // заголовок и первое поле оказываются "уехавшими" выше видимой части
  // модалки. Сбрасываем скролл на самый верх при каждом открытии, чтобы
  // модалка всегда открывалась с заголовка, а не с середины формы.
  const wrapperRef = useRef<HTMLDivElement>(null);

  useEffect(() => setMounted(true), []);

  useEffect(() => {
    if (openModal && wrapperRef.current) {
      wrapperRef.current.scrollTop = 0;
    }
  }, [openModal]);

  useImperativeHandle(ref, () => ({
    open: () => setOpenModal(true),
    close: () => setOpenModal(false),
  }));

  const content = (
    <AnimatePresence>
      {openModal && (
        <>
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1, transition: { duration: 0.3 } }}
            exit={{ opacity: 0, transition: { delay: 0.3 } }}
            onClick={() => setOpenModal(false)}
            className="modal-backdrop"
          />
          <motion.div
            ref={wrapperRef}
            initial={{ scale: 0 }}
            animate={{ scale: 1, transition: { duration: 0.3 } }}
            exit={{ scale: 0, transition: { delay: 0.3 } }}
            className="modal-content-wrapper"
          >
            <motion.div
              initial={{ x: 100, opacity: 0 }}
              animate={{ x: 0, opacity: 1, transition: { delay: 0.3, duration: 0.3 } }}
              exit={{ x: 100, opacity: 0, transition: { duration: 0.3 } }}
              className="modal-content"
            >
              {props.children}
            </motion.div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );

  if (!mounted) return null;
  return createPortal(content, document.body);
});

Modal.displayName = 'Modal';