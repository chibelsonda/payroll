@component('mail::message')
<div style="text-align:center; margin-bottom: 16px;">
  <img src="{{ config('app.logo_url', 'https://via.placeholder.com/160x40?text=CyfrinTech') }}" alt="CyfrinTech" style="max-width: 180px; height: auto;">
</div>

# Welcome to CyfrinTech

Please confirm your email address to continue.

<div style="text-align:center; margin: 24px 0;">
  <a href="{{ $url }}" style="display:inline-block; background-color:#60a5fa; color:#ffffff; padding:12px 20px; border-radius:8px; text-decoration:none; font-weight:600;">
    Verify Email
  </a>
</div>

If you did not create an account, no further action is required.

Thanks,<br>
CyfrinTech Team
@endcomponent
