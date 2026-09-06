import './globals.css';
import '@/styles/styles.css';
import '@/styles/response.css';
import '@/styles/dots.css';
import '@/components/ui/sitePreloader.css';

/**
 * Корневой layout намеренно НЕ рендерит <html>/<body>.
 *
 * Атрибут lang обязан зависеть от локали, а корневой layout сегмент [locale]
 * не получает — раньше здесь был захардкожен lang="ru", и все страницы /en и
 * /kz объявляли себя русскими. Оболочку документа теперь рендерят:
 *   - app/[locale]/layout.tsx — для всех страниц сайта;
 *   - app/not-found.tsx — для путей, не попавших ни в одну локаль.
 * Глобальные стили остаются здесь: они общие для обеих веток.
 */
export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <>{children}</>;
}
