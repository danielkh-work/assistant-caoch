<h1>Pending Users</h1>

<p>This is the pending user page.</p>

@if (session('success'))
  <div style="background: lightgreen; padding: 10px;">
    {{ session('success') }}
  </div>
@endif