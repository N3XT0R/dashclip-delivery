<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Builder;

interface AssignmentQueryInterface
{
    /**
     * Gibt alle Assignments für einen spezifischen Channel zurück.
     */
    public function forChannel(Channel $channel): Builder;

    /**
     * Gibt alle verfügbaren Assignments zurück.
     */
    public function available(): Builder;

    /**
     * Gibt alle heruntergeladenen Assignments zurück.
     */
    public function downloaded(): Builder;

    /**
     * Gibt alle abgelaufenen Assignments zurück.
     */
    public function expired(): Builder;

    /**
     * Gibt alle zurückgewiesenen Assignments zurück.
     */
    public function returned(): Builder;
}
