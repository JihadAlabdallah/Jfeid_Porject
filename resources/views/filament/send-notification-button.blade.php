{{-- Top bar: opens Notifications with the "Send notification" form already open --}}
<x-filament::icon-button
    icon="heroicon-o-paper-airplane"
    tag="a"
    :href="\App\Filament\Resources\NotificationResource::getUrl('index', ['action' => 'send'])"
    label="Send notification"
    tooltip="Send notification"
    color="gray"
    size="lg"
/>
