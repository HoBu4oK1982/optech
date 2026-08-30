import './globals.css';
import '@/styles/styles.css';
import '@/styles/response.css';
import '@/styles/dots.css';
import '@/components/ui/sitePreloader.css';

import SitePreloader from '@/components/ui/SitePreloader';
import YandexMetrika from '@/components/analytics/YandexMetrika';

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="ru" suppressHydrationWarning>
      <head>
        <link rel="icon" href="/assets/images/favicon.ico" sizes="any" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
        <meta name="theme-color" content="#121123" />
      </head>
      <body>
        <YandexMetrika />
        <SitePreloader />
        {children}
      </body>
    </html>
  );
}
