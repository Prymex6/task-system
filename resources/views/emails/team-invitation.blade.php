<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Zaproszenie do workspace</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:#6366f1;">{{ config('app.name') }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">System zarządzania projektami</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#6366f1;padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Masz zaproszenie!</p>
                    <p style="margin:0;font-size:13px;color:#c7d2fe;">{{ $invitedBy->name }} zaprasza Cię do workspace</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">Cześć!</p>
                    <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      <strong style="color:#374151;">{{ $invitedBy->name }}</strong> zaprasza Cię do dołączenia do workspace na platformie
                      <strong style="color:#374151;">{{ config('app.name') }}</strong>.
                      Twoja rola w projekcie: <strong style="color:#6366f1;">{{ ucfirst($invitation->workspace_role) }}</strong>.
                    </p>

                    {{-- Info box --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:16px;">
                          <p style="margin:0 0 6px 0;font-size:14px;color:#374151;">
                            <strong>E-mail:</strong> {{ $invitation->email }}
                          </p>
                          <p style="margin:0;font-size:13px;color:#6b7280;">
                            Link jest ważny do {{ $invitation->expires_at->format('d.m.Y H:i') }}
                          </p>
                        </td>
                      </tr>
                    </table>

                    {{-- Button --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td align="center">
                          <a href="{{ $acceptUrl }}" style="display:inline-block;background:#6366f1;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;font-size:15px;">
                            Zaakceptuj zaproszenie &rarr;
                          </a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 8px 0;font-size:13px;color:#9ca3af;line-height:1.7;">
                      Jeśli nie oczekiwałeś zaproszenia, możesz zignorować tę wiadomość.
                    </p>
                    <p style="margin:0 0 4px 0;font-size:13px;color:#9ca3af;">Lub skopiuj link bezpośrednio:</p>
                    <p style="margin:0;font-size:12px;color:#6366f1;word-break:break-all;">{{ $acceptUrl }}</p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              {{ config('app.name') }} &ndash; System zarządzania projektami SaaS
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
