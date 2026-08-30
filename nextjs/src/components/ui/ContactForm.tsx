'use client';

import { useState, FormEvent } from 'react';
import { submitOrder, submitPriceRequest } from '@/lib/api';
import { getTranslations } from '@/i18n/translations';

interface ContactFormProps {
  locale: string;
  productName?: string; // if provided, this is a price request form
}

export default function ContactForm({ locale, productName }: ContactFormProps) {
  const t = getTranslations(locale);
  const [form, setForm] = useState({ name: '', phone: '', email: '', comment: '' });
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSending(true);
    try {
      if (productName) {
        await submitPriceRequest({ ...form, product: productName });
      } else {
        await submitOrder(form);
      }
      setSent(true);
      setForm({ name: '', phone: '', email: '', comment: '' });
    } catch (err) {
      console.error(err);
    } finally {
      setSending(false);
    }
  };

  if (sent) {
    return (
      <div className="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
        <svg className="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p className="text-green-700 font-medium">{t.common.successMessage}</p>
      </div>
    );
  }

  return (
    <div onSubmit={handleSubmit}>
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t.common.name} *</label>
          <input
            type="text"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            className="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t.common.phone} *</label>
          <input
            type="tel"
            required
            value={form.phone}
            onChange={(e) => setForm({ ...form, phone: e.target.value })}
            className="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t.common.email}</label>
          <input
            type="email"
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            className="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t.common.comment}</label>
          <textarea
            value={form.comment}
            onChange={(e) => setForm({ ...form, comment: e.target.value })}
            rows={3}
            className="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        <button
          type="button"
          onClick={handleSubmit}
          disabled={sending || !form.name || !form.phone}
          className="w-full bg-primary text-white py-3 rounded-lg font-medium hover:bg-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {sending ? t.common.loading : (productName ? t.common.requestPrice : t.common.send)}
        </button>
      </div>
    </div>
  );
}
