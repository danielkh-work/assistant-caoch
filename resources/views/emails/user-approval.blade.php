<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>User Approval Request</title>
</head>
<body>
  <h2>New Signup Request</h2>
  <p>
    You have a new signup request from <strong>{{ $name }}</strong> ({{ $email }}).
  </p>
  <p>
    Please approve the user by clicking the button below:
  </p>
  <p>
    <a href="{{ $approvalLink }}" style="display:inline-block; padding:10px 20px; background:#28a745; color:#fff; text-decoration:none; border-radius:5px;">Approve User</a>
  </p>
  <p>If you did not expect this email, you can ignore it.</p>
</body>
</html>
