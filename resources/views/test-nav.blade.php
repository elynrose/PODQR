<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Test</title>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .navbar { background: white; border-bottom: 1px solid #ddd; padding: 10px; }
        .navbar-brand { font-weight: bold; color: #333; text-decoration: none; }
        .nav-links { display: flex; gap: 20px; margin-top: 10px; }
        .nav-link { color: #666; text-decoration: none; }
        .nav-link:hover { color: #333; }
        .content { padding: 20px; }
        .debug-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">PODQR</a>
        <div class="nav-links">
            <a href="#" class="nav-link">Dashboard</a>
            <a href="#" class="nav-link">Wall</a>
            <a href="#" class="nav-link">T-Shirt Designer</a>
            <a href="#" class="nav-link">QR Generator</a>
            <a href="#" class="nav-link">Designs</a>
            <a href="#" class="nav-link">Orders</a>
            @if(Auth::check() && Auth::user()->isAdmin())
            <a href="#" class="nav-link">Users</a>
            <a href="#" class="nav-link">Categories</a>
            <a href="#" class="nav-link">Clothes Types</a>
            <a href="#" class="nav-link">Shirt Sizes</a>
            @endif
        </div>
    </div>
    
    <div class="content">
        <h1>Navigation Test Page</h1>
        <p>This page tests if the navigation logic is working correctly.</p>
        
        <div class="debug-info">
            <h3>Debug Information:</h3>
            <p><strong>User:</strong> {{ Auth::user()->name ?? 'Not logged in' }}</p>
            <p><strong>Is Admin:</strong> {{ Auth::user()->isAdmin() ? 'Yes' : 'No' }}</p>
            <p><strong>User Type:</strong> {{ Auth::user()->user_type ?? 'N/A' }}</p>
            <p><strong>Is Premium:</strong> {{ Auth::user()->isPremium() ? 'Yes' : 'No' }}</p>
            <p><strong>Is Partner:</strong> {{ Auth::user()->isPartner() ? 'Yes' : 'No' }}</p>
        </div>
        
        <div class="debug-info">
            <h3>Navigation Logic Test:</h3>
            <p>Admin check: @if(Auth::check() && Auth::user()->isAdmin()) ✅ Working @else ❌ Not working @endif</p>
            <p>Auth check: @if(Auth::check()) ✅ Working @else ❌ Not working @endif</p>
        </div>
    </div>
</body>
</html> 