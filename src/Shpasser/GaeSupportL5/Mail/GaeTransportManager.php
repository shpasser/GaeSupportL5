<?php

namespace Shpasser\GaeSupportL5\Mail;

use Illuminate\Mail\TransportManager;
use Shpasser\GaeSupportL5\Mail\Transport\GaeTransport;

class GaeTransportManager extends TransportManager
{
    protected function createGaeDriver()
    {
        return new GaeTransport($this->app);
    }
}
