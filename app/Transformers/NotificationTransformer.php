<?php

namespace App\Transformers;

use Illuminate\Notifications\DatabaseNotification;
use League\Fractal\TransformerAbstract;

class NotificationTransformer extends TransformerAbstract
{
//    protected $availableIncludes = ['user', 'topic'];

    public function transform(DatabaseNotification $notification)
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'dta' => $notification->data,
            'read_at' => $notification->read_at ? $notification->read_at->toDateTimeString() : null,
            'created_at' => $notification->created_at->toDateTimeString(),
            'updated_at' => $notification->updated_at->toDateTimeString()
        ];
    }


}
