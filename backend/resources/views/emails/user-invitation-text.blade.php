You're invited to join {{ $companyName }}

{{ $inviterName }} has invited you to join {{ $companyName }} on our platform.

Click the link below to accept your invitation and get started:

{{ $inviteLink }}

⏰ This invitation expires on: {{ $expiresAt->format('F j, Y \a\t g:i A') }} ({{ $expiresAt->diffForHumans() }})

---

If you didn't expect this invitation, you can safely ignore this email.

© {{ date('Y') }} {{ $companyName }}. All rights reserved.
