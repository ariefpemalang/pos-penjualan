<?php

namespace App\Services;

use App\Models\ServiceTicket;
use App\Models\ServiceTicketStatusLog;

class ServiceTicketNotifier
{
    /**
     * @return array{notified: bool, hiwa_job_id: string|null, error: string|null}
     */
    public function notifyCreated(ServiceTicket $ticket): array
    {
        return $this->send($ticket, '');
    }

    /**
     * @return array{notified: bool, hiwa_job_id: string|null, error: string|null}
     */
    public function notifyStatus(ServiceTicket $ticket, ?string $note = null): array
    {
        return $this->send($ticket, '');
    }

    /**
     * @return array{notified: bool, hiwa_job_id: string|null, error: string|null}
     */
    public function send(ServiceTicket $ticket, string $message): array
    {
        return [
            'notified' => false,
            'hiwa_job_id' => null,
            'error' => null,
        ];
    }

    public function applyToLog(ServiceTicketStatusLog $log, array $result): void
    {
        $log->update([
            'notified' => $result['notified'],
            'hiwa_job_id' => $result['hiwa_job_id'],
            'notify_error' => $result['error'],
        ]);
    }
}
