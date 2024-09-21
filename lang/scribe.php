<?php

return [
    "labels" => [
        "search" => "Suche...",
        "base_url" => "Base URL",
    ],

    "auth" => [
        "none" => "Keine Authentifizierung erforderlich",
        "instruction" => [
            "query" => <<<TEXT
                Um die Anfrage zu authentifizieren, fügen Sie einen Abfrageparameter **`:parameterName`** in die Anfrage ein.
                TEXT,
            "body" => <<<TEXT
                Um die Anfrage zu authentifizieren, fügen Sie einen Parameter **`:parameterName`** im Request-Body ein.
                TEXT,
            "query_or_body" => <<<TEXT
                Um die Anfrage zu authentifizieren, fügen Sie einen Parameter **`:parameterName`** entweder in der Abfragezeichenfolge oder im Request-Body ein.
                TEXT,
            "bearer" => <<<TEXT
                Um die Anfrage zu authentifizieren, fügen Sie einen **`Authorization`**-Header mit dem Wert **`"Bearer :placeholder"`** ein.
                TEXT,
            "basic" => <<<TEXT
                Um die Anfrage zu authentifizieren, fügen Sie einen **`Authorization`**-Header im Format **`"Basic {credentials}"`** ein.
                Der Wert von `{credentials}` sollte Ihr Benutzername/Ihre ID und Ihr Passwort sein, verbunden mit einem Doppelpunkt (:),
                und dann base64-codiert.
                TEXT,
            "header" => <<<TEXT
                Um die Anfrage zu authentifizieren, fügen Sie einen **`:parameterName`**-Header mit dem Wert **`":placeholder"`** ein.
                TEXT,
        ],
        "details" => <<<TEXT
            Alle authentifizierten Endpunkte sind in der Dokumentation unten mit einem `Authentifizierung erforderlich`-Badge gekennzeichnet.
            TEXT,
    ],

    "headings" => [
        "introduction" => "Einführung",
        "auth" => "Authentifizierung der Anfragen",
    ],

    "endpoint" => [
        "request" => "Anfrage",
        "headers" => "Headers",
        "url_parameters" => "URL Parameter",
        "body_parameters" => "Body Parameter",
        "query_parameters" => "Query Parameter",
        "response" => "Antwort",
        "response_fields" => "Antwortfelder",
        "example_request" => "Beispielanfrage",
        "example_response" => "Beispielantwort",
        "responses" => [
            "binary" => "Binary data",
            "empty" => "leere Antwort",
        ],
    ],

    "try_it_out" => [
        "open" => "Try it out ⚡",
        "cancel" => "Cancel 🛑",
        "send" => "Send Request 💥",
        "loading" => "⏱ Sending...",
        "received_response" => "Received response",
        "request_failed" => "Request failed with error",
        "error_help" => <<<TEXT
            Tip: Check that you're properly connected to the network.
            If you're a maintainer of ths API, verify that your API is running and you've enabled CORS.
            You can check the Dev Tools console for debugging information.
            TEXT,
    ],

    "links" => [
        "postman" => "View Postman collection",
        "openapi" => "View OpenAPI spec",
    ],
];
