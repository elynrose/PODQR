<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->name }} - Post</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .post-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .user-username {
            color: #666;
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .post-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .post-content {
            font-size: 18px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
        }
        
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 14px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .post-attachment {
            margin: 20px 0;
        }
        
        .attachment-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .attachment-file {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .attachment-file:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .file-icon {
            font-size: 24px;
            margin-right: 15px;
            color: #667eea;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            margin: 0;
        }
        
        .file-size {
            color: #666;
            margin: 0;
            font-size: 12px;
        }
        
        .view-count {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-count i {
            color: #667eea;
        }
        
        .back-link {
            background: white;
            border-radius: 15px;
            padding: 15px 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .back-link a:hover {
            color: #5a6fd8;
        }
        
        .footer {
            text-align: center;
            color: rgba(255,255,255,0.8);
            margin-top: 40px;
            font-size: 14px;
        }
        
        .footer a {
            color: white;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="post-container">
        <!-- Back Link -->
        <div class="back-link">
            <a href="{{ url('/' . ($user->username ?: $user->id)) }}">
                <i class="fas fa-arrow-left"></i>
                Back to {{ $user->name }}'s Profile
            </a>
        </div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="user-avatar">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <h2 class="user-name">{{ $user->name }}</h2>
            <p class="user-username">@{{ $user->username ?: $user->id }}</p>
        </div>

        <!-- Post -->
        <div class="post-card">
            <div class="post-content">
                {{ $post->content }}
            </div>
            
            @if($post->hasAttachment())
                <div class="post-attachment">
                    @if($post->isImage())
                        <img src="{{ $post->attachment_url }}" alt="Attachment" class="attachment-image">
                    @else
                        <a href="{{ $post->attachment_url }}" target="_blank" class="attachment-file">
                            <i class="fas fa-file file-icon"></i>
                            <div class="file-info">
                                <p class="file-name">{{ $post->attachment_name }}</p>
                                <p class="file-size">{{ strtoupper($post->getFileExtension()) }} File</p>
                            </div>
                        </a>
                    @endif
                </div>
            @endif
            
            <div class="post-meta">
                <div class="view-count">
                    <i class="fas fa-eye"></i>
                    <span>{{ number_format($post->view_count) }} views</span>
                </div>
                <div class="post-time">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $post->created_at->format('M j, Y \a\t g:i A') }}
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Powered by <a href="{{ url('/') }}">PODQR</a></p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 