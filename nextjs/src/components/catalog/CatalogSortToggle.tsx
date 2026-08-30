import Link from 'next/link';
import './catalog.css';

function Caret({ up }: { up: boolean }) {
  return (
    <svg className={`catSortToggle__caret ${up ? 'is-up' : 'is-down'}`} viewBox="0 0 12 8" aria-hidden="true">
      <path d="M1 1.5 6 6.5 11 1.5" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

/**
 * Два независимых toggle-переключателя порядка товаров на странице
 * категории — по дате («Сначала новые» / «Сначала старые») и по
 * алфавиту («А-Я» / «Я-А»). Оба пишут в один query-параметр ?sort=,
 * значения: date_asc | name_asc | name_desc (по умолчанию, без параметра —
 * новые сверху, это же и есть основная сортировка по created_at DESC на
 * бэкенде). Ручной «Порядок сортировки» из админки (sort_order) всегда
 * приоритетнее обоих режимов — см. FrontendController::getProductsByCategory.
 * Ссылки, без клиентского JS.
 */
export default function CatalogSortToggle({
  basePath,
  current,
}: {
  basePath: string;
  current?: string;
}) {
  const isDateAsc = current === 'date_asc';
  const isNameAsc = current === 'name_asc';
  const isNameDesc = current === 'name_desc';
  const isAlpha = isNameAsc || isNameDesc;

  const dateHref = isDateAsc ? basePath : `${basePath}?sort=date_asc`;
  const dateLabel = isDateAsc ? 'Сначала старые' : 'Сначала новые';

  const nameHref = isNameDesc
    ? `${basePath}?sort=name_asc`
    : isNameAsc
    ? `${basePath}?sort=name_desc`
    : `${basePath}?sort=name_asc`;
  const nameLabel = isNameDesc ? 'По алфавиту: Я-А' : 'По алфавиту: А-Я';

  return (
    <div className="catSortToggle">
      <Link
        href={dateHref}
        className={`catSortToggle__btn ${!isAlpha ? 'is-active' : ''}`}
        aria-label="Сортировать по дате добавления"
      >
        <span>{dateLabel}</span>
        <Caret up={isDateAsc} />
      </Link>

      <Link
        href={nameHref}
        className={`catSortToggle__btn ${isAlpha ? 'is-active' : ''}`}
        aria-label="Сортировать по алфавиту"
      >
        <span>{nameLabel}</span>
        <Caret up={isNameDesc} />
      </Link>
    </div>
  );
}
