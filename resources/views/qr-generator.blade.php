<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('QR Code Generator') }}
        </h2>
    </x-slot>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/font-awesome/css/font-awesome.min.css') }}">
    <style>
        .qr-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            height: 100%;
        }
        .qr-preview {
            text-align: center;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            background-color: #fafafa;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .qr-preview img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-custom {
            margin: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: inline-block;
            margin-left: 10px;
        }
        .controls-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .preview-section {
            position: sticky;
            top: 20px;
        }
        .placeholder-text {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            margin-top: 50px;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .action-buttons .btn {
            margin-bottom: 10px;
            width: 100%;
        }
        .action-buttons .btn {
            margin: 0 5px 10px 0;
            width: auto;
        }
        .action-buttons .btn:last-child {
            margin-right: 0;
        }
        @media (max-width: 768px) {
            .preview-section {
                position: static;
                margin-top: 20px;
            }
        }
    </style>
    @endpush

    <div class="container-fluid">
        <div class="row">
            <!-- Left Column - Controls -->
            <div class="col-lg-6">
                <div class="qr-container">
                    <h2 class="text-center mb-4">
                        <i class="fa fa-qrcode"></i> QR Code Generator
                    </h2>
                    
                    <form id="qrForm">
                        <div class="controls-section">
                            @if($userType !== 'free')
                                <div class="form-group">
                                    <label for="qrText"><strong>QR Code Content:</strong></label>
                                    <input type="text" class="form-control" id="qrText" name="text" 
                                           placeholder="Enter URL, text, phone number, email, or any content..." required>
                                    <small class="form-text text-muted">
                                        Examples: https://example.com, +1234567890, john@email.com, or any text
                                    </small>
                                </div>
                            @else
                                <div class="form-group">
                                    <label><strong>Your Unique QR Code:</strong></label>
                                    <div class="alert alert-info mt-2 mb-2">
                                        <i class="fa fa-info-circle"></i> 
                                        <strong>Free User:</strong> Your QR code will link to your unique profile page.
                                        <br><small class="text-muted">{{ $userUniqueUrl }}</small>
                                    </div>
                                    <input type="hidden" id="qrText" name="text" value="{{ $userUniqueUrl }}" required>
                                </div>
                            @endif
                        </div>

                        <div class="controls-section">
                            <h5><i class="fa fa-cogs"></i> Settings</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="qrSize"><strong>Size:</strong></label>
                                        <select class="form-control" id="qrSize" name="size">
                                            <option value="200">Small (200px)</option>
                                            <option value="300">Medium (300px)</option>
                                            <option value="400">Large (400px)</option>
                                            <option value="500">Extra Large (500px)</option>
                                            <option value="1000">High Quality (1000px)</option>
                                            <option value="2000">Ultra High (2000px)</option>
                                            <option value="4000" selected>Maximum Quality (4000px)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="qrFormat"><strong>Format:</strong></label>
                                        <select class="form-control" id="qrFormat" name="format">
                                            <option value="svg" selected>SVG</option>
                                            <option value="png">PNG</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="controls-section">
                            <h5><i class="fa fa-palette"></i> Colors</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="qrColor"><strong>QR Code Color:</strong></label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" class="form-control" id="qrColor" name="color" value="#000000" style="width: 60px;">
                                            <span class="color-preview" id="qrColorPreview" style="background-color: #000000;"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="qrBackground"><strong>Background Color:</strong></label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" class="form-control" id="qrBackground" name="background" value="#FFFFFF" style="width: 60px;">
                                            <span class="color-preview" id="qrBackgroundPreview" style="background-color: #FFFFFF;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="btn btn-primary btn-sm" id="generateQrBtn">
                                <i class="fa fa-magic"></i> Generate QR Code
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="saveQrBtn">
                                <i class="fa fa-save"></i> Save QR Code
                            </button>
                            @if($userType === 'free')
                                <div class="alert alert-warning mt-3">
                                    <i class="fa fa-star"></i> 
                                    <strong>Upgrade to Premium:</strong> Create up to 20 custom QR codes with any content!
                                    <br><small>Free users are limited to one profile QR code.</small>
                                </div>
                            @elseif($userType === 'premium')
                                <div class="alert alert-info mt-3">
                                    <i class="fa fa-crown"></i> 
                                    <strong>Premium User:</strong> You can create up to 20 QR codes.
                                    <br><small>Upgrade to Partner for unlimited QR codes and dashboard access.</small>
                                </div>
                            @elseif($userType === 'partner')
                                <div class="alert alert-success mt-3">
                                    <i class="fa fa-diamond"></i> 
                                    <strong>Partner User:</strong> You have unlimited QR codes and full dashboard access!
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column - Preview -->
            <div class="col-lg-6">
                <div class="preview-section">
                    <div class="qr-container">
                        <h3 class="text-center mb-4">
                            <i class="fa fa-eye"></i> Preview
                        </h3>
                        
                        <div id="qrPreview" class="qr-preview">
                            <div class="placeholder-text">
                                <i class="fa fa-qrcode fa-3x mb-3"></i>
                                <p>Your QR code will appear here</p>
                                <small>Enter content and click "Generate QR Code" to see the preview</small>
                            </div>
                        </div>

                        <div id="qrActions" class="text-center mt-3" style="display: none;">
                           
                            <div class="mt-3">
                                <form method="POST" action="{{ route('qr-save-and-design') }}" style="display: inline;" id="designForm">
                                    @csrf
                                    <input type="hidden" name="text" id="designQrText">
                                    <input type="hidden" name="size" id="designQrSize">
                                    <input type="hidden" name="color" id="designQrColor">
                                    <input type="hidden" name="background" id="designQrBackground">
                                    <button type="submit" class="btn btn-warning btn-sm" id="useInDesignBtn">
                                        <i class="fa fa-tshirt"></i> Use in T-Shirt Designer
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Existing QR Codes Section -->
                        @if(isset($qrCodes) && $qrCodes->count() > 0)
                        <div class="mt-4">
                            <h5 class="text-center mb-3">
                                <i class="fa fa-history"></i> Your QR Codes
                            </h5>
                            <div class="row">
                                @foreach($qrCodes as $qrCode)
                                <div class="col-12 mb-2">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ Storage::url($qrCode->file_path) }}" alt="QR Code" style="width: 50px; height: 50px;">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1">{{ $qrCode->name }}</h6>
                                                    <small class="text-muted">{{ Str::limit($qrCode->content, 30) }}</small>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <form method="POST" action="{{ route('qr-save-and-design') }}" style="display: inline;">
                                                        @csrf
                                                        <input type="hidden" name="text" value="{{ $qrCode->content }}">
                                                        <input type="hidden" name="size" value="{{ $qrCode->size }}">
                                                        <input type="hidden" name="color" value="{{ $qrCode->color }}">
                                                        <input type="hidden" name="background" value="{{ $qrCode->background_color }}">
                                                        <button type="submit" class="btn btn-warning btn-sm">
                                                            <i class="fa fa-tshirt"></i> Use in Designer
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm delete-qr-btn" data-qr-id="{{ $qrCode->id }}" data-qr-name="{{ $qrCode->name }}">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
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

            // Update color previews
            $('#qrColor').on('change', function() {
                $('#qrColorPreview').css('background-color', $(this).val());
            });

            $('#qrBackground').on('change', function() {
                $('#qrBackgroundPreview').css('background-color', $(this).val());
            });

            // Generate QR Code
            $('#generateQrBtn').click(function() {
                var text = $('#qrText').val();
                if (!text) {
                    showAlert('Please enter some content for the QR code.', 'warning');
                    return;
                }

                var formData = {
                    text: text,
                    size: $('#qrSize').val(),
                    color: $('#qrColor').val(),
                    background: $('#qrBackground').val()
                };

                $('#generateQrBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');

                $.ajax({
                    url: '{{ route("qr-generate-data-url") }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#qrPreview').html('<img id="qrImage" src="' + response.data_url + '" alt="Generated QR Code">');
                            $('#qrActions').show();
                            
                            // Populate hidden fields for T-shirt designer
                            $('#designQrText').val($('#qrText').val());
                            $('#designQrSize').val($('#qrSize').val());
                            $('#designQrColor').val($('#qrColor').val());
                            $('#designQrBackground').val($('#qrBackground').val());
                        }
                    },
                    error: function(xhr) {
                        showAlert('Error generating QR code. Please try again.', 'error');
                    },
                    complete: function() {
                        $('#generateQrBtn').prop('disabled', false).html('<i class="fa fa-magic"></i> Generate QR Code');
                    }
                });
            });

            // Download QR Code
            $('#downloadQrBtn').click(function() {
                var link = document.createElement('a');
                link.download = 'qr-code.svg';
                link.href = $('#qrImage').attr('src');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Copy QR Code to Clipboard
            $('#copyQrBtn').click(function() {
                var img = $('#qrImage')[0];
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                ctx.drawImage(img, 0, 0);
                
                canvas.toBlob(function(blob) {
                    var item = new ClipboardItem({ "image/png": blob });
                    navigator.clipboard.write([item]).then(function() {
                        showAlert('QR Code copied to clipboard!', 'success');
                    }).catch(function(err) {
                        showAlert('Failed to copy to clipboard. Please download instead.', 'warning');
                    });
                });
            });

            // Reset Form
            $('#resetQrBtn').click(function() {
                $('#qrForm')[0].reset();
                $('#qrPreview').html('<div class="placeholder-text"><i class="fa fa-qrcode fa-3x mb-3"></i><p>Your QR code will appear here</p><small>Enter content and click "Generate QR Code" to see the preview</small></div>');
                $('#qrActions').hide();
                $('#qrColorPreview').css('background-color', '#000000');
                $('#qrBackgroundPreview').css('background-color', '#FFFFFF');
                @if($userType === 'free')
                // Restore the hidden field value for free users
                $('#qrText').val('{{ $userUniqueUrl }}');
                @endif
            });

            // Enter key to generate
            $('#qrText').keypress(function(e) {
                if (e.which == 13) {
                    $('#generateQrBtn').click();
                }
            });

            // Save QR Code
            $('#saveQrBtn').click(function() {
                var text = $('#qrText').val();
                if (!text) {
                    showAlert('Please enter some content for the QR code.', 'warning');
                    return;
                }
                var formData = {
                    text: text,
                    size: $('#qrSize').val(),
                    color: $('#qrColor').val(),
                    background: $('#qrBackground').val(),
                    name: @if($userType === 'free') 'My Profile QR Code' @else 'My QR Code' @endif
                };
                $('#saveQrBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                $.ajax({
                    url: '{{ route('qr-generate') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showAlert('QR code saved!', 'success');
                            $('#qrPreview').html('<img id="qrImage" src="' + response.data_url + '" alt="Generated QR Code">');
                            $('#qrActions').show();
                        } else {
                            showAlert(response.message || 'Error saving QR code.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error saving QR code.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showAlert(msg, 'error');
                    },
                    complete: function() {
                        $('#saveQrBtn').prop('disabled', false).html('<i class="fa fa-save"></i> Save QR Code');
                    }
                });
            });

            // Delete QR Code
            $('.delete-qr-btn').click(function() {
                var qrId = $(this).data('qr-id');
                var qrName = $(this).data('qr-name');
                
                if (confirm('Are you sure you want to delete "' + qrName + '"? This action cannot be undone.')) {
                    var $btn = $(this);
                    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deleting...');
                    
                    $.ajax({
                        url: '/qr-codes/' + qrId,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                showAlert('QR code deleted successfully!', 'success');
                                // Remove the QR code card from the DOM
                                $btn.closest('.col-12').fadeOut(300, function() {
                                    $(this).remove();
                                    // If no more QR codes, hide the section
                                    if ($('.delete-qr-btn').length === 0) {
                                        $('.mt-4').fadeOut(300);
                                    }
                                });
                            } else {
                                showAlert(response.message || 'Error deleting QR code.', 'error');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Error deleting QR code.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            showAlert(msg, 'error');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="fa fa-trash"></i> Delete');
                        }
                    });
                }
            });
        });
    </script>
    @endpush
</x-app-layout> 