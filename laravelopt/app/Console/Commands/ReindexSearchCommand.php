<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Search\Index\DocumentIndexer;

class ReindexSearchCommand extends Command
{
    protected $signature = 'search:reindex';
    protected $description = 'Полностью перестроить поисковый индекс (search_documents + search_terms)';

    public function handle(DocumentIndexer $indexer): int
    {
        $this->info('Переиндексация поиска...');
        $start = microtime(true);

        $count = $indexer->reindexAll(function ($type, $total) {
            // лёгкий прогресс без спама
        });

        $sec = round(microtime(true) - $start, 1);
        $this->info("Готово: {$count} документов за {$sec}с.");

        return self::SUCCESS;
    }
}
