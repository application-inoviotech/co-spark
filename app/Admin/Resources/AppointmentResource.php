<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Appointment;
use App\Models\Producer;
use App\Notifications\MemberAlert;
use App\Notifications\StatusUpdate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/** SOW: Admin Appointment Management — confirm, decline, reschedule, assign, history. */
class AppointmentResource extends Resource
{
    public static string $model = Appointment::class;

    public static string $label = 'Appointments';

    public static string $singular = 'Appointment';

    public static string $group = 'Service';

    public static array $search = ['reference', 'name', 'email', 'phone'];

    public function query(): Builder
    {
        return Appointment::with('producer.user')->latest('preferred_date');
    }

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'type_label', 'label' => 'Type'],
            ['key' => 'preferred_date', 'label' => 'Date', 'type' => 'date'],
            ['key' => 'preferred_time', 'label' => 'Time'],
            ['key' => 'producer.user.name', 'label' => 'Producer'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
        ];
    }

    public function filters(): array
    {
        return [
            'status' => ['label' => 'Status', 'options' => self::options(['pending', 'confirmed', 'declined', 'completed'])],
            'when' => ['label' => 'When', 'options' => ['upcoming' => 'Upcoming', 'past' => 'Past'],
                'scope' => fn ($q, $v) => $v === 'upcoming' ? $q->whereDate('preferred_date', '>=', today()) : $q->whereDate('preferred_date', '<', today())],
        ];
    }

    public function detail(Model $a): array
    {
        return [['title' => 'Appointment', 'rows' => [
            ['Name', $a->name], ['Email', $a->email], ['Phone', $a->phone], ['Type', $a->type_label],
            ['When', $a->preferred_date->format('l, M j, Y').' · '.$a->preferred_time], ['Reason', $a->reason], ['Message', $a->message],
            ['Producer', $a->producer?->user->name], ['Status', $this->format($a->status, 'pill')], ['Requested', $a->created_at->format('M j, Y')],
        ]]];
    }

    public function actions(Model $a): array
    {
        $producers = ['' => 'COSPARK office'] + Producer::with('user')->where('status', 'active')->get()->mapWithKeys(fn ($p) => [$p->id => $p->user->name])->all();

        return array_values(array_filter([
            $a->status === 'pending' ? ['key' => 'confirmed', 'label' => 'Confirm', 'style' => 'copper'] : null,
            ['key' => 'reschedule', 'label' => 'Reschedule', 'style' => 'line', 'fields' => [
                ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'rules' => ['required', 'date', 'after_or_equal:today'], 'default' => $a->preferred_date->toDateString()],
                ['name' => 'time', 'label' => 'Time', 'type' => 'select', 'options' => array_combine(config('cospark.appointment_times'), config('cospark.appointment_times')), 'rules' => ['required', Rule::in(config('cospark.appointment_times'))], 'default' => $a->preferred_time],
            ]],
            ['key' => 'assign', 'label' => 'Assign producer', 'style' => 'line', 'fields' => [['name' => 'producer_id', 'label' => 'Producer', 'type' => 'select', 'options' => $producers, 'rules' => ['nullable', Rule::exists('producers', 'id')], 'default' => (string) $a->producer_id]]],
            $a->status === 'confirmed' ? ['key' => 'completed', 'label' => 'Mark completed', 'style' => 'line'] : null,
            in_array($a->status, ['pending', 'confirmed'], true) ? ['key' => 'declined', 'label' => 'Decline', 'style' => 'danger'] : null,
        ]));
    }

    public function runAction(Model $a, string $action, array $data): string
    {
        $when = fn () => $a->preferred_date->format('l, M j').' at '.$a->preferred_time;

        switch ($action) {
            case 'reschedule':
                $a->update(['preferred_date' => $data['date'], 'preferred_time' => $data['time'], 'status' => 'confirmed']);
                $this->tell($a, 'Your appointment was rescheduled', "{$a->type_label} on {$when()}.");

                return 'Appointment rescheduled.';
            case 'assign':
                $a->update(['producer_id' => $data['producer_id'] ?: null]);
                $a->load('producer.user')->producer?->user->notify(new MemberAlert('Appointments', "Appointment assigned · {$a->name}", "{$a->type_label} · {$when()}", 'appointments', mail: true, portal: 'producer'));

                return 'Producer assigned.';
            default:
                $a->update(['status' => $action]);
                if ($action !== 'completed') {
                    $this->tell($a, 'Appointment '.($action === 'confirmed' ? 'confirmed' : 'not available'), $action === 'confirmed'
                        ? "{$a->type_label} on {$when()}. Reference {$a->reference}." : 'We could not offer this time. Please book another slot or call the office.');
                }

                return 'Appointment '.$action.'.';
        }
    }

    /** Members see the update in their portal notifications (and by email); guests get the email. */
    private function tell(Appointment $a, string $subject, string $text): void
    {
        $a->user
            ? $a->user->notify(new MemberAlert('Appointments', $subject, $text, 'appointments', mail: true))
            : Notification::route('mail', $a->email)->notify(new StatusUpdate($subject, $a->name, [$text]));
    }
}
