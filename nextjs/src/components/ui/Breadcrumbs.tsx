import Link from 'next/link';
import { SITE_URL } from '@/lib/constants';
import './breadcrumbs.css';

interface BreadcrumbItem {
  label: string;
  href?: string;
}

interface BreadcrumbsProps {
  items: BreadcrumbItem[];
}

// home stroke icon
function HomeIcon() {
  return (
    <svg
      className="crumbs__home"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden
    >
      <path d="M3 10.5 12 3l9 7.5" />
      <path d="M5 9.5V21h14V9.5" />
      <path d="M9.5 21v-6h5v6" />
    </svg>
  );
}

export default function Breadcrumbs({ items }: BreadcrumbsProps) {
  // JSON-LD
  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.label,
      ...(item.href
        ? { item: item.href.startsWith('http') ? item.href : `${SITE_URL}${item.href}` }
        : {}),
    })),
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <nav aria-label="Breadcrumb" className="crumbs">
        <ol className="crumbs__list">
          {items.map((item, index) => {
            const isFirst = index === 0;
            const isLast = index === items.length - 1;
            const content = isFirst ? (
              <>
                <HomeIcon />
                <span className="crumbs__srlabel">{item.label}</span>
              </>
            ) : (
              item.label
            );

            return (
              <li key={index} className="crumbs__item">
                {index > 0 && (
                  <svg
                    className="crumbs__sep"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden
                  >
                    <path d="M9 6l6 6-6 6" />
                  </svg>
                )}
                {item.href && !isLast ? (
                  <Link
                    href={item.href}
                    className={`crumbs__link ${isFirst ? 'crumbs__link--home' : ''}`}
                  >
                    {content}
                  </Link>
                ) : (
                  <span className="crumbs__current">{content}</span>
                )}
              </li>
            );
          })}
        </ol>
      </nav>
    </>
  );
}
