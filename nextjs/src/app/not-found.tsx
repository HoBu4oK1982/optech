import CanvasNotFound from '@/components/ui/CanvasNotFound';

/**
 * 404 для путей вне /{locale}. Рендерит собственную оболочку документа,
 * потому что корневой layout её больше не отдаёт (см. app/layout.tsx).
 */
export default function RootNotFound() {
  return (
    <html lang="ru">
      <body>
        <CanvasNotFound />
      </body>
    </html>
  );
}
