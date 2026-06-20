<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterclassRegistration;
use App\Models\Student;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function masterclass(): StreamedResponse
    {
        $rows = MasterclassRegistration::orderByDesc('created_at')->get();

        return $this->csv('masterclass-registrations.csv',
            ['First name', 'Last name', 'Email', 'WhatsApp', 'Background', 'Goal', 'Session', 'Registered'],
            $rows->map(fn ($r) => [
                $r->first_name, $r->last_name, $r->email, $r->whatsapp,
                $r->background, $r->goal, $r->session_date, optional($r->created_at)->toDateTimeString(),
            ]),
        );
    }

    public function leads(): StreamedResponse
    {
        $rows = Student::orderByDesc('created_at')->get();

        return $this->csv('leads.csv',
            ['Name', 'Email', 'WhatsApp', 'Interest', 'Source', 'Captured'],
            $rows->map(fn ($r) => [
                $r->name, $r->email, $r->whatsapp, $r->interest, $r->source, optional($r->created_at)->toDateTimeString(),
            ]),
        );
    }

    private function csv(string $filename, array $headers, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
