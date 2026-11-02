<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Model;

/** SOW: Contact Us inquiries. */
class ContactMessageResource extends Resource
{
    public static string $model = ContactMessage::class;

    public static string $label = 'Contact messages';

    public static string $singular = 'Message';

    public static string $group = 'Service';

    public static array $search = ['reference', 'email', 'last_name', 'message'];

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'first_name', 'label' => 'First name'],
            ['key' => 'last_name', 'label' => 'Last name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Received', 'type' => 'datetime'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(['new', 'handled'])]];
    }

    public function detail(Model $m): array
    {
        return [['title' => 'Message', 'rows' => [['From', "{$m->first_name} {$m->last_name}"], ['Email', $m->email], ['Phone', $m->phone], ['Message', $m->message], ['Status', $this->format($m->status, 'pill')]]]];
    }

    public function actions(Model $m): array
    {
        return $m->status === 'new' ? [['key' => 'handled', 'label' => 'Mark handled', 'style' => 'copper']] : [];
    }

    public function runAction(Model $m, string $action, array $data): string
    {
        $m->update(['status' => 'handled']);

        return 'Marked as handled.';
    }
}
