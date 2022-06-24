<?php

namespace App\Services;

use App\Models\SearchHistory;

class SearchHistoryService extends BaseService
{
    public function save(int $userId, string $keyword, string $form)
    {
        $history = SearchHistory::new();
        $history->fill([
            'user_id' => $userId,
            'keyword' => $keyword,
            'form' => $form
        ]);
        $history->save();
        return $history;
    }
}
