<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited!</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, sans-serif !important;}
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f4; width: 100%;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header with Logo -->
                    @if($logoUrl)
                    <tr>
                        <td align="center" style="padding: 40px 20px 20px; text-align: center;">
                            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" style="max-width: 200px; height: auto;" />
                        </td>
                    </tr>
                    @endif

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 20px 40px;">
                            <h1 style="margin: 0 0 20px 0; font-size: 28px; font-weight: 600; color: #333333; line-height: 1.3;">
                                You're invited to join {{ $companyName }}
                            </h1>

                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #666666; line-height: 1.6;">
                                <strong>{{ $inviterName }}</strong> has invited you to join <strong>{{ $companyName }}</strong> on our platform.
                            </p>

                            <p style="margin: 0 0 30px 0; font-size: 16px; color: #666666; line-height: 1.6;">
                                Click the button below to accept your invitation and get started:
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 0 0 30px; text-align: center;">
                                        <a href="{{ $inviteLink }}" style="display: inline-block; padding: 14px 32px; background-color: {{ $primaryColor }}; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: 600; text-align: center;">
                                            Accept Invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiration Notice -->
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 16px; margin: 0 0 20px 0; border-radius: 4px;">
                                <p style="margin: 0; font-size: 14px; color: #856404; line-height: 1.5;">
                                    <strong>⏰ This invitation expires on:</strong><br>
                                    {{ $expiresAt->format('F j, Y \a\t g:i A') }} ({{ $expiresAt->diffForHumans() }})
                                </p>
                            </div>

                            <!-- Alternative Link -->
                            <p style="margin: 0 0 20px 0; font-size: 14px; color: #999999; line-height: 1.6;">
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href="{{ $inviteLink }}" style="color: {{ $primaryColor }}; word-break: break-all; text-decoration: none;">{{ $inviteLink }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.5; text-align: center;">
                                If you didn't expect this invitation, you can safely ignore this email.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999999; line-height: 1.5; text-align: center;">
                                © {{ date('Y') }} {{ $companyName }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
