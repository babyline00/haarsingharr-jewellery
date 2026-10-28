@include('errors.minimal', [
    'code' => 403,
    'title' => 'Access denied',
    'message' => 'You do not have permission to view this page. If you think this is a mistake, ask an administrator to check your role.',
])
