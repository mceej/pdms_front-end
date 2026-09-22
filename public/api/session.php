<?php

require_once __DIR__ . '/bootstrap.php';

requireMethod('GET');

respond(['signedIn' => (new AdminSession())->isSignedIn()]);
