<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\MailStatus;
use App\Models\MailLog;

class MailRepository
{
    public function findMailByInReplyTo(string $inReplyTo): ?MailLog
    {
        return MailLog::where('message_id', 'like', '%'.$inReplyTo.'%')
            ->first();
    }

    public function updateStatus(MailLog $log, MailStatus $status): bool
    {
        return $log->update([
            'status' => $status,
            'replied_at' => now(),
        ]);
    }

    public function create(array $attributes): MailLog
    {
        return MailLog::create($attributes);
    }
}