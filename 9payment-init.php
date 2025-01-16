<?php

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Sentry;

\Sentry\init([
   'dsn' => 'https://96dabb113dd408619027d43684347542@o4504984764350464.ingest.us.sentry.io/4508024410472448',

   'traces_sample_rate' => 1.0,
   
   'profiles_sample_rate' => 1.0,
 ]);
 