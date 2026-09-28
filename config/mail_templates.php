<?php

/*
|--------------------------------------------------------------------------
| Transactional e-mail defaults
|--------------------------------------------------------------------------
|
| The copy a fresh workspace starts with. Rows in `email_templates` override
| these; "restore default" in the settings screen reads straight from here,
| which is why the shapes have to stay in step with the table.
|
| `variables` is the whitelist the editor validates against — a placeholder
| that is not listed would be delivered to the recipient as literal text.
|
*/

return [

    'team.invitation' => [
        'label' => 'Zaproszenie do zespołu',
        'subject' => 'Zaproszenie do {{workspace}}',
        'body_html' => '<p>Cześć,</p>'
            . '<p>{{inviter}} zaprasza Cię do pracy w {{workspace}}.</p>'
            . '<p><a href="{{link}}">Dołącz do zespołu</a></p>'
            . '<p>Zaproszenie wygasa {{expires_at}}.</p>',
        'variables' => ['workspace', 'inviter', 'link', 'expires_at'],
    ],

    'ticket.created' => [
        'label' => 'Nowe zgłoszenie',
        'subject' => 'Nowe zgłoszenie #{{ticket_number}}: {{subject}}',
        'body_html' => '<p>Wpłynęło nowe zgłoszenie od {{client}}.</p>'
            . '<p><strong>{{subject}}</strong></p>'
            . '<p>{{message}}</p>'
            . '<p><a href="{{link}}">Otwórz zgłoszenie</a></p>',
        'variables' => ['ticket_number', 'subject', 'client', 'message', 'link'],
    ],

    'task.assigned' => [
        'label' => 'Przypisanie zadania',
        'subject' => 'Przypisano Ci zadanie: {{task}}',
        'body_html' => '<p>Cześć {{user}},</p>'
            . '<p>{{assigner}} przypisał(a) Ci zadanie <strong>{{task}}</strong> '
            . 'w projekcie {{project}}.</p>'
            . '<p>Termin: {{due_date}}</p>'
            . '<p><a href="{{link}}">Przejdź do zadania</a></p>',
        'variables' => ['user', 'assigner', 'task', 'project', 'due_date', 'link'],
    ],

    'invoice.sent' => [
        'label' => 'Wysyłka faktury',
        'subject' => 'Faktura {{invoice_number}}',
        'body_html' => '<p>Dzień dobry,</p>'
            . '<p>w załączeniu przesyłamy fakturę {{invoice_number}} '
            . 'na kwotę {{total}}.</p>'
            . '<p>Termin płatności: {{due_date}}</p>'
            . '<p>Pozdrawiamy,<br>{{company}}</p>',
        'variables' => ['invoice_number', 'total', 'due_date', 'company'],
    ],

    'password.reset' => [
        'label' => 'Reset hasła',
        'subject' => 'Resetowanie hasła',
        'body_html' => '<p>Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.</p>'
            . '<p><a href="{{link}}">Ustaw nowe hasło</a></p>'
            . '<p>Link jest ważny {{expires_minutes}} minut. '
            . 'Jeśli to nie Ty, zignoruj tę wiadomość.</p>',
        'variables' => ['link', 'expires_minutes'],
    ],

];
