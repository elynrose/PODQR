<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Debug</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .navbar-height {
            height: 60px;
        }
        .content-wrapper {
            min-height: calc(100vh - 60px);
        }
        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-light">
    <div class="min-vh-100 d-flex flex-column">
        @include('layouts.navigation')

        <!-- Page Content -->
        <main class="content-wrapper py-4">
            <div class="container">
                <h1>Navigation Debug Page</h1>
                <p>This page is to test if the navigation is displaying correctly.</p>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>User:</strong> {{ Auth::user()->name ?? 'Not logged in' }}</p>
                        <p><strong>Is Admin:</strong> {{ Auth::user()->isAdmin() ? 'Yes' : 'No' }}</p>
                        <p><strong>User Type:</strong> {{ Auth::user()->user_type ?? 'N/A' }}</p>
                        <p><strong>Is Premium:</strong> {{ Auth::user()->isPremium() ? 'Yes' : 'No' }}</p>
                        <p><strong>Is Partner:</strong> {{ Auth::user()->isPartner() ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 