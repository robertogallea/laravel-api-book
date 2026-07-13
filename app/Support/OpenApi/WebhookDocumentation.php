<?php

namespace App\Support\OpenApi;

use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * Scramble only ever looks at routes EventHub exposes: it has no way to discover a request
 * EventHub itself sends, like the webhook built in this chapter. OpenAPI 3.1 has a native
 * top-level "webhooks" keyword for exactly this case, so this hand-describes it once, mirroring
 * BookingConfirmedNotificationPayload's real shape, instead of inventing a separate format.
 */
class WebhookDocumentation
{
    public static function toArray(): array
    {
        return [
            'bookingConfirmed' => [
                'post' => [
                    'summary' => 'A booking was confirmed',
                    'description' => 'Sent when a participant successfully books seats for an '
                        .'event. Delivery is retried on failure, up to a configured maximum '
                        .'number of attempts; the request body and signature header below are '
                        .'exactly what SendBookingConfirmedWebhook sends.',
                    'parameters' => [
                        [
                            'name' => 'X-EventHub-Signature',
                            'in' => 'header',
                            'required' => true,
                            'schema' => (new StringType)
                                ->setDescription(
                                    'HMAC-SHA256 of the raw request body, keyed with the secret '
                                    .'shared with this partner, formatted as "sha256=<hex>".'
                                )
                                ->example('sha256=43275e2a...86686c0d74b0937e')
                                ->toArray(),
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => Schema::fromType(self::payloadSchema())->toArray(),
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Any 2xx response acknowledges receipt. EventHub '
                                .'does not act on the response body.',
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function payloadSchema(): ObjectType
    {
        return (new ObjectType)
            ->addProperty(
                'event',
                (new StringType)
                    ->setDescription('Always "booking.confirmed" for this webhook.')
                    ->example('booking.confirmed'),
            )
            ->addProperty('data', (new ObjectType)
                ->addProperty('booking_id', new IntegerType)
                ->addProperty('event_id', new IntegerType)
                ->addProperty('event_title', new StringType)
                ->addProperty('participant_name', new StringType)
                ->addProperty('participant_email', new StringType)
                ->addProperty('seats', new IntegerType)
                ->addProperty('confirmed_at', (new StringType)->setDescription('ISO 8601 timestamp.'))
                ->setRequired([
                    'booking_id', 'event_id', 'event_title',
                    'participant_name', 'participant_email', 'seats', 'confirmed_at',
                ]))
            ->setRequired(['event', 'data']);
    }
}
