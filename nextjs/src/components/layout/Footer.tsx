'use client';

import { useState, useEffect, forwardRef, useImperativeHandle, useRef } from 'react';
import { createPortal } from 'react-dom';
import Link from 'next/link';
import Icon from '@/components/ui/Icon';
import { motion, AnimatePresence } from 'framer-motion';
import { useForm } from 'react-hook-form';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { API_URL } from '@/lib/constants';
import { makeT, renderWithBr } from '@/i18n/dict';
import OptechLogo from '@/components/ui/OptechLogo';

export default function Footer({
  locale,
  settings = {},
  categories = [],
}: {
  locale: string;
  settings?: any;
  categories?: any[];
}) {
  const t = makeT(locale);
  const { register, handleSubmit, reset } = useForm();
  const modalRef = useRef<any>(null);
  const footerRef = useRef<HTMLElement>(null);
  const L = (path: string) => `/${locale}${path}`;

  // Футер зафиксирован снизу экрана (см. .footer--fixed-under в styles.css) —
  // контент "выезжает" и открывает его при скролле, как на freon.kz. Чтобы
  // спейсер под контентом (.appFooterSpacer) точно совпадал по высоте с
  // реальным футером (высота которого зависит от контента/языка/брейкпоинта),
  // измеряем её через ResizeObserver и прокидываем в CSS-переменную
  // --site-footer-h на <html>. На мобильном (≤960px) эффект отключён в CSS —
  // там высота спейсера не нужна, поэтому переменную сбрасываем.
  useEffect(() => {
    const root = document.documentElement;
    const foot = footerRef.current;
    if (!foot) return;

    const mq = window.matchMedia('(min-width: 961px)');

    const applyFooterHeight = () => {
      if (!mq.matches) {
        root.style.removeProperty('--site-footer-h');
        return;
      }
      const h = Math.max(1, Math.ceil(foot.offsetHeight));
      root.style.setProperty('--site-footer-h', `${h}px`);
    };

    const ro = new ResizeObserver(applyFooterHeight);
    ro.observe(foot);
    mq.addEventListener('change', applyFooterHeight);
    applyFooterHeight();

    return () => {
      ro.disconnect();
      mq.removeEventListener('change', applyFooterHeight);
      root.style.removeProperty('--site-footer-h');
    };
  }, []);

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

  return (
    <footer ref={footerRef} className="footer--fixed-under">
      <Modal ref={modalRef}>
        <h2>{t('set_your_order_title')}</h2>
        <form onSubmit={handleSubmit(onSubmit)}>
          <fieldset className="inputField">
            <Icon name="user" className="fa-solid fa-user" />
            <input required className="modalInput" type="text" id="name" placeholder={t('modal_name')} autoComplete="off" {...register('name')} />
          </fieldset>
          <fieldset className="inputField">
            <Icon name="phone" className="fa-solid fa-phone-volume" />
            <input required className="modalInput" type="tel" id="phone" autoComplete="off" placeholder={t('modal_phone')} {...register('phone')} />
          </fieldset>
          <fieldset className="inputField">
            <Icon name="envelope" className="fas fa-envelope" />
            <input required className="modalInput" type="email" id="email" autoComplete="off" placeholder={t('modal_email')} {...register('email')} />
          </fieldset>
          <fieldset className="inputField">
            <Icon name="comment" className="fas fa-comment" />
            <textarea required className="modalInput modalTextarea" id="comment" autoComplete="off" placeholder={t('modal_comment')} {...register('comment')} />
          </fieldset>
          <button type="submit" className="modalInputBtn">{t('send_request_btn')}</button>
          <ToastContainer position="top-center" autoClose={5000} hideProgressBar={false} newestOnTop={false} closeOnClick rtl={false} pauseOnFocusLoss draggable pauseOnHover />
          <div className="modalClose" onClick={() => modalRef.current?.close()}>x</div>
        </form>
      </Modal>

      <div className="container">
        <div className="footer">
          <div className="footerCategoryWrap">
            <h3>{t('product_category_footer')}</h3>
            <ul>
              {categories.map((category: any) => (
                <li key={category.id}>
                  <Link href={L(`/catalog/${category.slug}`)}>{category.name}</Link>
                </li>
              ))}
            </ul>
            <h3>{t('information_footer')}</h3>
            <ul>
              <li><Link href={L('/license')}>{t('licence_page_title')}</Link></li>
              <li><Link href={L('/about')}>{t('about_page_title')}</Link></li>
              <li><Link href={L('/contacts')}>{t('contacts_page_title')}</Link></li>
            </ul>
          </div>
          <div className="footerLogoWrap">
            <div className="footerLogo">
              <Link href={L('/')}>
                <OptechLogo width={266} height={52} />
              </Link>
              <br />
              <p>{t('slogan')}</p>
            </div>
            <div className="footerSocial">
              <a href="https://www.linkedin.com/company/optech-2011/" target="_blank" rel="noreferrer"><Icon name="linkedin" className="fab fa-linkedin" /></a>
              <a href="https://facebook.com" target="_blank" rel="noreferrer"><Icon name="facebook" className="fab fa-facebook" /></a>
              <a href="https://www.instagram.com/optech.kz/" target="_blank" rel="noreferrer"><Icon name="instagram" className="fab fa-instagram-square" /></a>
              <a href="https://www.youtube.com/@optechkz" target="_blank" rel="noreferrer"><Icon name="youtube" className="fab fa-youtube" /></a>
            </div>
            <div className="footerLegal">
              <Link href={L('/terms')}>{t('terms_page_title')}</Link>
              <Link href={L('/privacy')}>{t('privacy_page_title')}</Link>
            </div>
          </div>
          <div className="footerContactsWrap">
            <h3>{t('contacts_page_title')}</h3>
            <div className="footerContactItem">
              <a href={`tel:${settings.phone}`}><Icon name="phone" className="fas fa-phone-volume" />{settings.phone}</a>
            </div>
            <div className="footerContactItem">
              <a href={`tel:${settings.city_phone}`}><Icon name="phone" className="fas fa-phone-volume" />{settings.city_phone}</a>
            </div>
            <div className="footerContactItem">
              <a href={`mailto:${settings.email}`}><Icon name="envelope" className="fas fa-envelope" />{settings.email}</a>
            </div>
            <div className="footerContactItem">
              <p><Icon name="clock" className="fas fa-clock" />{settings.work_time}</p>
            </div>
            <div className="footerContactItem">
              <a href="https://go.2gis.com/sijyi" target="_blank" rel="noreferrer"><Icon name="location" className="fas fa-map-marker-alt" />{settings.address}</a>
            </div>
            <div className="footerBtn" onClick={() => modalRef.current?.open()}>
              {renderWithBr(t('submit_btn')).map((p, i) => (p === null ? <br key={i} /> : <span key={i}>{p}</span>))}
            </div>
          </div>
        </div>
        <div className="copyrightWrap">
          <div className="footerCopyYear">2026</div>
          <div className="footerCopyright">
            COPYRIGHT © {t('copyright_footer')} - <Link href={L('/')}>https://optech.kz</Link>
          </div>
        </div>
      </div>

      <div id="mobile_bar">
        <div className="mobile_bar_wrapper">
          <div className="mobile_bar_item">
            <a href="https://wa.me/+77717460602" target="_blank" rel="noreferrer">
              <span className="mobile_bar_icons_inner">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" id="whatsapp-icon" width="100%" height="28px"><title>Forma 1</title><path d="M28 13.639c0 7.533-6.154 13.64-13.745 13.64-2.41 0-4.675-.616-6.645-1.697L0 28l2.481-7.318a13.502 13.502 0 0 1-1.972-7.043C.509 6.106 6.663 0 14.255 0 21.847 0 28 6.106 28 13.639zM14.255 2.172c-6.373 0-11.557 5.144-11.557 11.467 0 2.51.818 4.833 2.201 6.724l-1.444 4.258 4.442-1.411a11.546 11.546 0 0 0 6.358 1.896c6.372 0 11.556-5.143 11.556-11.466 0-6.323-5.184-11.468-11.556-11.468zm6.941 14.609c-.085-.14-.31-.223-.646-.391-.338-.167-1.995-.976-2.303-1.087-.309-.111-.534-.167-.758.167-.224.335-.87 1.088-1.067 1.311-.197.223-.393.251-.73.084-.337-.167-1.423-.521-2.71-1.659-1.001-.886-1.678-1.98-1.874-2.316-.196-.334-.021-.515.148-.681.152-.15.337-.391.505-.586.169-.195.225-.334.337-.557.113-.224.056-.419-.028-.586-.084-.167-.759-1.812-1.039-2.482-.281-.669-.561-.557-.758-.557-.196 0-.421-.029-.646-.029-.225 0-.59.084-.899.419-.308.334-1.179 1.143-1.179 2.788 0 1.645 1.207 3.235 1.376 3.458.168.222 2.33 3.708 5.756 5.047 3.426 1.338 3.426.892 4.043.836.618-.056 1.993-.809 2.275-1.589.281-.782.281-1.451.197-1.59z"></path></svg>
              </span>WhatsApp
            </a>
          </div>
          <div className="mobile_bar_item">
            <a target="_parent" href="tel:+77717460602">
              <span className="mobile_bar_icons_inner">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 27" id="phone-icon" width="100%" height="100%"><title>Shape 1</title><path d="M22.35 16.726c-.554-.576-1.222-.884-1.929-.884-.702 0-1.376.302-1.952.878l-1.803 1.795c-.149-.08-.297-.154-.44-.228-.205-.103-.399-.2-.565-.302-1.689-1.071-3.224-2.468-4.697-4.274-.713-.9-1.192-1.658-1.541-2.428.468-.427.902-.871 1.324-1.299.16-.159.32-.325.48-.484 1.198-1.197 1.198-2.747 0-3.944L9.669 4.001c-.177-.177-.36-.359-.531-.542A26.208 26.208 0 0 0 8.065 2.4c-.553-.548-1.215-.838-1.912-.838-.696 0-1.369.29-1.94.838l-.011.011-1.941 1.954a4.17 4.17 0 0 0-1.238 2.65c-.137 1.664.354 3.214.73 4.229.925 2.49 2.306 4.798 4.366 7.271 2.5 2.98 5.507 5.334 8.943 6.992 1.313.621 3.065 1.356 5.022 1.482.12.005.245.011.36.011 1.318 0 2.425-.473 3.292-1.413.006-.012.018-.017.023-.029.297-.359.639-.684.999-1.031.245-.234.496-.479.742-.735.565-.587.862-1.271.862-1.972 0-.707-.303-1.385-.879-1.955zm-8.8-16.303a7.231 7.231 0 0 1 3.937 2.04 7.256 7.256 0 0 1 2.044 3.932.765.765 0 0 0 .759.638c.045 0 .085-.006.131-.011a.77.77 0 0 0 .633-.889 8.783 8.783 0 0 0-2.471-4.759 8.807 8.807 0 0 0-4.765-2.467.774.774 0 0 0-.89.627.76.76 0 0 0 .622.889zm12.395 5.487a14.455 14.455 0 0 0-4.069-7.835A14.487 14.487 0 0 0 16.072.012a.768.768 0 0 0-.885.627.774.774 0 0 0 .634.889 12.97 12.97 0 0 1 7.014 3.63 12.904 12.904 0 0 1 3.635 7.003.765.765 0 0 0 .89.627.756.756 0 0 0 .628-.878z"></path></svg>
              </span>{t('footer_call_title')}
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}

const Modal = forwardRef((props: any, ref) => {
  const [openModal, setOpenModal] = useState(false);
  // Портал в document.body — та же причина, что и в Header.tsx: модалка
  // больше не потомок <footer>, поэтому её невозможно "запереть" под
  // стекинг-контекстом какого-либо элемента страницы.
  // (ВОССТАНОВЛЕНО: этот фикс был по ошибке потерян в одной из более
  // поздних дельт, где Footer.tsx пересобирался от старой версии файла.)
  const [mounted, setMounted] = useState(false);
  // .modal-content-wrapper — overflow-y:auto (форма может быть выше видимой
  // области). Автозаполнение браузера / автофокус на поле может само
  // проскроллить эту область к какому-то полю в середине формы — тогда
  // заголовок и первое поле оказываются "уехавшими" выше видимой части
  // модалки. Сбрасываем скролл на самый верх при каждом открытии.
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