<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#f4f2ec;font-family:Arial,Helvetica,sans-serif;color:#22251d;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e7e1d3;">
    <tr><td style="background:#4B5D34;padding:18px 24px;color:#fff;font-size:18px;font-weight:bold;">GlowRez — New contact message</td></tr>
    <tr><td style="padding:24px;">
      <p style="margin:0 0 6px;font-size:12px;color:#6c6d5e;text-transform:uppercase;letter-spacing:1px;">From</p>
      <p style="margin:0 0 16px;font-size:15px;"><strong>{{ $senderName }}</strong> &lt;{{ $senderEmail }}&gt;</p>

      <p style="margin:0 0 6px;font-size:12px;color:#6c6d5e;text-transform:uppercase;letter-spacing:1px;">Subject</p>
      <p style="margin:0 0 16px;font-size:15px;">{{ $subjectLine }}</p>

      <p style="margin:0 0 6px;font-size:12px;color:#6c6d5e;text-transform:uppercase;letter-spacing:1px;">Message</p>
      <p style="margin:0;font-size:15px;line-height:1.7;white-space:pre-wrap;">{{ $body }}</p>
    </td></tr>
    <tr><td style="padding:14px 24px;background:#f7f5ef;color:#6c6d5e;font-size:12px;">Reply directly to this email to reach {{ $senderName }}.</td></tr>
  </table>
</body>
</html>
