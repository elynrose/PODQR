<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Community Wall') }}
        </h2>
    </x-slot>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/font-awesome/css/font-awesome.min.css') }}">
    <style>
        .wall-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .post-form {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e1e8ed;
        }
        
        .post-input {
            border: none;
            outline: none;
            width: 100%;
            min-height: 100px;
            resize: none;
            font-size: 16px;
            font-family: inherit;
        }
        
        .post-input:focus {
            box-shadow: none;
        }
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e1e8ed;
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-btn {
            background: none;
            border: none;
            color: #1da1f2;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .file-upload-btn:hover {
            background-color: rgba(29, 161, 242, 0.1);
        }
        
        .post-btn {
            background: #1da1f2;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .post-btn:hover {
            background: #1991db;
        }
        
        .post-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .char-count {
            color: #657786;
            font-size: 14px;
        }
        
        .char-count.warning {
            color: #f39c12;
        }
        
        .char-count.danger {
            color: #e74c3c;
        }
        
        .post {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e1e8ed;
            transition: box-shadow 0.2s;
        }
        
        .post:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1da1f2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #14171a;
            margin: 0;
        }
        
        .post-time {
            color: #657786;
            font-size: 14px;
            margin: 0;
        }
        
        .post-content {
            color: #14171a;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 15px;
            word-wrap: break-word;
        }
        
        .post-attachment {
            margin-bottom: 15px;
        }
        
        .attachment-image {
            max-width: 100%;
            border-radius: 10px;
            cursor: pointer;
        }
        
        .attachment-file {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f7f9fa;
            border-radius: 10px;
            border: 1px solid #e1e8ed;
            text-decoration: none;
            color: #1da1f2;
            transition: background-color 0.2s;
        }
        
        .attachment-file:hover {
            background: #e8f5fd;
            text-decoration: none;
            color: #1991db;
        }
        
        .file-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            margin: 0;
        }
        
        .file-size {
            font-size: 12px;
            color: #657786;
            margin: 0;
        }
        
        .post-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #657786;
            font-size: 14px;
        }
        
        .view-count {
            display: flex;
            align-items: center;
        }
        
        .view-count i {
            margin-right: 5px;
        }
        
        .post-actions-footer {
            display: flex;
            gap: 15px;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: #657786;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: rgba(29, 161, 242, 0.1);
            color: #1da1f2;
        }
        
        .delete-btn:hover {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .selected-file {
            background: #e8f5fd;
            border: 1px solid #1da1f2;
            border-radius: 10px;
            padding: 10px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .selected-file-info {
            display: flex;
            align-items: center;
        }
        
        .selected-file-icon {
            font-size: 20px;
            margin-right: 10px;
            color: #1da1f2;
        }
        
        .remove-file-btn {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 16px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #657786;
        }
        
        .no-posts {
            text-align: center;
            padding: 40px;
            color: #657786;
        }
        
        .no-posts i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
    @endpush

    <div class="wall-container">
        <!-- Post Form -->
        <div class="post-form">
            <form id="postForm" enctype="multipart/form-data">
                @csrf
                <textarea 
                    id="postContent" 
                    name="content" 
                    class="post-input" 
                    placeholder="What's happening?"
                    maxlength="255"
                ></textarea>
                
                <div id="selectedFile" class="selected-file" style="display: none;">
                    <div class="selected-file-info">
                        <i class="fa fa-file selected-file-icon"></i>
                        <span id="selectedFileName"></span>
                    </div>
                    <button type="button" class="remove-file-btn" id="removeFile">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                
                <div class="post-actions">
                    <div class="file-upload">
                        <input type="file" id="attachment" name="attachment" accept="image/*,.pdf,.doc,.docx,.txt">
                        <button type="button" class="file-upload-btn" id="fileUploadBtn">
                            <i class="fa fa-paperclip"></i>
                        </button>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span id="charCount" class="char-count">255</span>
                        <button type="submit" class="post-btn" id="postBtn" disabled>
                            Post
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Posts Container -->
        <div id="postsContainer">
            @if($posts->count() > 0)
                @foreach($posts as $post)
                    <div class="post" data-post-id="{{ $post->id }}">
                        <div class="post-header">
                            <div class="user-avatar">
                                {{ strtoupper(substr($post->user->name, 0, 1)) }}
                            </div>
                            <div class="user-info">
                                <h4 class="user-name">{{ $post->user->name }}</h4>
                                <p class="post-time">{{ $post->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            {{ $post->content }}
                        </div>
                        
                        @if($post->hasAttachment())
                            <div class="post-attachment">
                                @if($post->isImage())
                                    <img src="{{ $post->attachment_url }}" alt="Attachment" class="attachment-image">
                                @else
                                    <a href="{{ $post->attachment_url }}" target="_blank" class="attachment-file">
                                        <i class="fa fa-file file-icon"></i>
                                        <div class="file-info">
                                            <p class="file-name">{{ $post->attachment_name }}</p>
                                            <p class="file-size">{{ strtoupper($post->getFileExtension()) }} File</p>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @endif
                        
                        <div class="post-footer">
                            <div class="view-count">
                                <i class="fa fa-eye"></i>
                                <span>{{ $post->view_count }} views</span>
                            </div>
                            
                            @if(Auth::id() === $post->user_id || Auth::user()->can('admin'))
                                <div class="post-actions-footer">
                                    <button class="action-btn delete-btn" onclick="deletePost({{ $post->id }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="no-posts">
                    <i class="fa fa-comments"></i>
                    <h3>No posts yet</h3>
                    <p>Be the first to share something!</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Set CSRF token for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Character count
            $('#postContent').on('input', function() {
                var maxLength = 255;
                var currentLength = $(this).val().length;
                var remaining = maxLength - currentLength;
                
                $('#charCount').text(remaining);
                
                if (remaining <= 20) {
                    $('#charCount').removeClass('warning danger').addClass('danger');
                } else if (remaining <= 50) {
                    $('#charCount').removeClass('warning danger').addClass('warning');
                } else {
                    $('#charCount').removeClass('warning danger');
                }
                
                // Enable/disable post button
                if (currentLength > 0) {
                    $('#postBtn').prop('disabled', false);
                } else {
                    $('#postBtn').prop('disabled', true);
                }
            });

            // File upload
            $('#attachment').on('change', function() {
                var file = this.files[0];
                if (file) {
                    $('#selectedFileName').text(file.name);
                    $('#selectedFile').show();
                    
                    // Update icon based on file type
                    var icon = 'fa-file';
                    if (file.type.startsWith('image/')) {
                        icon = 'fa-image';
                    } else if (file.type.includes('pdf')) {
                        icon = 'fa-file-pdf-o';
                    } else if (file.type.includes('word')) {
                        icon = 'fa-file-word-o';
                    } else if (file.type.includes('text')) {
                        icon = 'fa-file-text-o';
                    }
                    
                    $('.selected-file-icon').removeClass().addClass('fa ' + icon + ' selected-file-icon');
                }
            });

            // Remove file
            $('#removeFile').on('click', function() {
                $('#attachment').val('');
                $('#selectedFile').hide();
            });

            // File upload button click
            $('#fileUploadBtn').on('click', function() {
                $('#attachment').click();
            });

            // Submit post
            $('#postForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var $btn = $('#postBtn');
                var originalText = $btn.text();
                
                $btn.prop('disabled', true).text('Posting...');
                
                $.ajax({
                    url: '{{ route("wall.store") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Add new post to the top
                            addPostToDOM(response.post);
                            
                            // Reset form
                            $('#postContent').val('');
                            $('#attachment').val('');
                            $('#selectedFile').hide();
                            $('#charCount').text('255');
                            $('#postBtn').prop('disabled', true);
                            
                            // Remove "no posts" message if it exists
                            $('.no-posts').remove();
                            
                            showAlert('Post created successfully!', 'success');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error creating post.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showAlert(msg, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });

        // Add post to DOM
        function addPostToDOM(post) {
            var postHtml = `
                <div class="post" data-post-id="${post.id}">
                    <div class="post-header">
                        <div class="user-avatar">
                            ${post.user.name.charAt(0).toUpperCase()}
                        </div>
                        <div class="user-info">
                            <h4 class="user-name">${post.user.name}</h4>
                            <p class="post-time">Just now</p>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        ${post.content}
                    </div>
                    
                    ${post.attachment_path ? `
                        <div class="post-attachment">
                            ${post.attachment_type === 'image' ? 
                                `<img src="${post.attachment_url}" alt="Attachment" class="attachment-image">` :
                                `<a href="${post.attachment_url}" target="_blank" class="attachment-file">
                                    <i class="fa fa-file file-icon"></i>
                                    <div class="file-info">
                                        <p class="file-name">${post.attachment_name}</p>
                                        <p class="file-size">${post.attachment_name.split('.').pop().toUpperCase()} File</p>
                                    </div>
                                </a>`
                            }
                        </div>
                    ` : ''}
                    
                    <div class="post-footer">
                        <div class="view-count">
                            <i class="fa fa-eye"></i>
                            <span>0 views</span>
                        </div>
                        
                        <div class="post-actions-footer">
                            <button class="action-btn delete-btn" onclick="deletePost(${post.id})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#postsContainer').prepend(postHtml);
        }

        // Delete post
        function deletePost(postId) {
            if (confirm('Are you sure you want to delete this post?')) {
                $.ajax({
                    url: '/wall/' + postId,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            $('[data-post-id="' + postId + '"]').fadeOut(300, function() {
                                $(this).remove();
                                
                                // Show "no posts" message if no posts remain
                                if ($('.post').length === 0) {
                                    $('#postsContainer').html(`
                                        <div class="no-posts">
                                            <i class="fa fa-comments"></i>
                                            <h3>No posts yet</h3>
                                            <p>Be the first to share something!</p>
                                        </div>
                                    `);
                                }
                            });
                            showAlert('Post deleted successfully!', 'success');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error deleting post.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showAlert(msg, 'error');
                    }
                });
            }
        }
    </script>
    @endpush
</x-app-layout> 