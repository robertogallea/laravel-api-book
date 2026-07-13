<?php

namespace App\Enums;

enum Role: string
{
    case Organizer = 'organizer';
    case Participant = 'participant';
    case Admin = 'admin';
}
