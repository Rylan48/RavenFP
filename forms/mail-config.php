<?php

return [
    'tenant_id' => getenv('RAVEN_AZURE_TENANT_ID'),
    'client_id' => getenv('RAVEN_AZURE_CLIENT_ID'),
    'client_secret' => getenv('RAVEN_AZURE_CLIENT_SECRET'),

    'from_email' => 'info@ravenfp.com',
    'from_name' => 'Raven Fire Protection',
    'recipient' => 'info@ravenfp.com',

    'smtp_host' => 'smtp.office365.com',
    'smtp_port' => 587,
];