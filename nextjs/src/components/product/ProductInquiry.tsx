'use client';

import Icon from '@/components/ui/Icon';
import { useState, useEffect, useCallback, useRef } from 'react';
import { createPortal } from 'react-dom';
import { AnimatePresence, motion } from 'framer-motion';
import { submitPriceRequest } from '@/lib/api';
import './product.css';

export type InquiryLabels = {
  requestPrice: string;
  consultTitle: string;
  yourName: string;
  yourPhone: string;
  yourEmail: string;
  leaveComment: string;
  submit: string;
  sending: string;
  success: string;
};

export default function ProductInquiry({
  productName,
  labels,
}: {
  productName: string;
  labels: InquiryLabels;
}) {
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name: '', phone: '', email: '', comment: '' });
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);
  // Портал в document.body — модалка на карточке товара раньше была
  // вложена внутри контента страницы и могла "запираться" стекинг-
  // контекстом какого-нибудь предка (напр. анимированной обёртки
  // переходов между страницами). Портал полностью убирает эту зависимость.
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);
  // .modal-content-wrapper — overflow-y:auto (форма может быть выше видимой
  // области). Автозаполнение браузера / автофокус на поле может само
  // проскроллить эту область к какому-то полю в середине формы — тогда
  // заголовок и первое поле оказываются "уехавшими" выше видимой части
  // модалки. Сбрасываем скролл на самый верх при каждом открытии.
  const wrapperRef = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (open && wrapperRef.current) {
      wrapperRef.current.scrollTop = 0;
    }
  }, [open]);

  const close = useCallback(() => {
    setOpen(false);
    window.setTimeout(() => setSent(false), 400);
  }, []);

  useEffect(() => {
    if (!open) return;
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && close();
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.style.overflow = prev;
      window.removeEventListener('keydown', onKey);
    };
  }, [open, close]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.phone || sending) return;
    setSending(true);
    try {
      await submitPriceRequest({ ...form, product: productName });
      setSent(true);
      setForm({ name: '', phone: '', email: '', comment: '' });
    } catch (err) {
      console.error(err);
    } finally {
      setSending(false);
    }
  };

  const modal = (
    <AnimatePresence>
      {open && (
        <>
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1, transition: { duration: 0.3 } }}
            exit={{ opacity: 0, transition: { delay: 0.3 } }}
            onClick={close}
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
              <h2>{labels.consultTitle}</h2>

              {sent ? (
                <p style={{ color: '#fff', lineHeight: 1.6 }}>{labels.success}</p>
              ) : (
                <form onSubmit={onSubmit}>
                  <fieldset className="inputField">
                    <Icon name="user" className="fa-solid fa-user" />
                    <input
                      required
                      className="modalInput"
                      type="text"
                      placeholder={labels.yourName}
                      autoComplete="off"
                      value={form.name}
                      onChange={(e) => setForm({ ...form, name: e.target.value })}
                    />
                  </fieldset>
                  <fieldset className="inputField">
                    <Icon name="phone" className="fa-solid fa-phone-volume" />
                    <input
                      required
                      className="modalInput"
                      type="tel"
                      placeholder={labels.yourPhone}
                      autoComplete="off"
                      value={form.phone}
                      onChange={(e) => setForm({ ...form, phone: e.target.value })}
                    />
                  </fieldset>
                  <fieldset className="inputField">
                    <Icon name="envelope" className="fas fa-envelope" />
                    <input
                      className="modalInput"
                      type="email"
                      placeholder={labels.yourEmail}
                      autoComplete="off"
                      value={form.email}
                      onChange={(e) => setForm({ ...form, email: e.target.value })}
                    />
                  </fieldset>
                  <fieldset className="inputField">
                    <Icon name="comment" className="fas fa-comment" />
                    <textarea
                      className="modalInput modalTextarea"
                      placeholder={labels.leaveComment}
                      autoComplete="off"
                      value={form.comment}
                      onChange={(e) => setForm({ ...form, comment: e.target.value })}
                    />
                  </fieldset>
                  <button type="submit" className="modalInputBtn" disabled={sending}>
                    {sending ? labels.sending : labels.submit}
                  </button>
                </form>
              )}

              <div className="modalClose" onClick={close}>
                x
              </div>
            </motion.div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );

  return (
    <>
      <button type="button" className="btnRequest" onClick={() => setOpen(true)}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden>
          <rect x="3" y="5" width="18" height="14" rx="2" />
          <path d="m3 7 9 6 9-6" />
        </svg>
        {labels.requestPrice}
      </button>

      {mounted && createPortal(modal, document.body)}
    </>
  );
}
