<?php

namespace App\Domains\Quote\Public\Visibility;

use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;
use App\Domains\Quote\Public\Api\QuotePublicApi;

class QuotesTabVisibility implements ProfileTabVisibility
{
    public function __construct(
        private readonly QuotePublicApi $quoteApi,
    ) {
    }

    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return $this->quoteApi->canViewQuoteBook($ownerUserId, $viewerId);
    }
}
