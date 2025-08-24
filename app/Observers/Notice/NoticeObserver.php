<?php

namespace App\Observers\Notice;

use App\Models\Notice;
use App\Services\Notice\NoticeService;

class NoticeObserver
{
    /**
     * Handle the Cat "created" event.
     */
    public function created(Notice $notice): void
    {

        app(NoticeService::class)->dispatchEvents($notice);

    }

    /**
     * Handle the Cat "updated" event.
     */
    public function updated(Notice $notice): void
    {

    }

    /**
     * Handle the Cat "deleted" event.
     */
    public function deleted(Notice $notice): void
    {

    }

    /**
     * Handle the Cat "restored" event.
     */
    public function restored(Notice $notice): void
    {
        //
    }

    /**
     * Handle the Cat "force deleted" event.
     */
    public function forceDeleted(Notice $notice): void
    {
        //
    }
}
