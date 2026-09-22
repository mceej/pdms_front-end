<?php

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

(new AdminSession())->signOut();

respond(['signedIn' => false]);
