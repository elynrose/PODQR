<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Design: ') }} {{ $design->name }}
            </h2>
            <div class="d-flex gap-2">
                <a href="{{ route('designs.show', $design->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Design
                </a>
                <button type="button" 
                        class="btn btn-outline-danger btn-sm delete-design" 
                        data-design-id="{{ $design->id }}"
                        data-design-name="{{ $design->name }}">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $firstCategory = $categories->first();
        $selectedCategoryId = null;
    @endphp

    @push('styles')
    <link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Pacifico|VT323|Quicksand|Inconsolata' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="{{ asset('css/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/colorpicker/css/bootstrap-colorpicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.min.css?v=1.1.4') }}">
    <style>
        /* Essential T-shirt designer functionality styles only */
        /* Bootstrap 5 button group fixes for designer */
        #wrap .btn-group[data-bs-toggle="buttons"] .btn input[type="radio"],
        #wrap .btn-group[data-bs-toggle="buttons"] .btn input[type="checkbox"] {
            position: absolute;
            clip: rect(0,0,0,0);
            pointer-events: none;
        }
        /* Custom button styles for designer functionality */
        #wrap .btn-default {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #212529;
        }
        #wrap .btn-default:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #212529;
        }
        #wrap .btn-default.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        /* Designer modal close button styling */
        #wrap .btn-close {
            background: none;
            border: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: .5;
        }
        #wrap .btn-close:hover {
            opacity: .75;
        }
        
        /* Clothes type selection styling */
        #clothesTypeSelect {
            font-size: 0.9rem;
        }
        
        #clothesTypePreview .card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        
        #clothesTypePreview .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        #clothesTypePreview .card-body {
            padding: 0.75rem;
        }
        
        #previewImage {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
        
        /* Color button styling */
        .colorButton {
            width: 40px;
            height: 40px;
            border: 2px solid #dee2e6;
            margin: 2px;
            border-radius: 50%;
            position: relative;
            flex: 0 0 calc(25% - 4px) !important;
            max-width: calc(25% - 4px) !important;
        }
        
        #colorButtonGroup {
            display: flex !important;
            flex-wrap: wrap !important;
            max-width: 200px !important;
            gap: 2px;
        }
        
        .colorButton.active {
            border-color: #0d6efd;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
        }
        
        .colorButton:hover {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }
        
        /* Download dropdown positioning fixes */
        .div_reviewbtn .dropup {
            position: relative;
            display: inline-block;
        }
        
        .div_reviewbtn .dropdown-toggle {
            position: relative;
        }
        
        .div_reviewbtn .dropdown-menu-end {
            right: 0;
            left: auto !important;
            min-width: 150px;
            position: absolute;
            z-index: 1000;
        }
        
        /* Ensure dropdown doesn't go off-screen */
        @media (max-width: 768px) {
            .div_reviewbtn .dropdown-menu-end {
                right: auto !important;
                left: 0 !important;
            }
        }
        
        /* Fix cvtoolbox positioning to prevent overlap with left column */
        .centerLayout {
            position: relative;
            overflow: visible;
        }
        
        .cvtoolbox {
            position: absolute;
            left: 0;
            right: 0;
            top: 5px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
            padding: 0 15px;
            display: none;
            z-index: 10;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .cvtoolbox_2nd {
            top: 41px;
        }
        
        .cvtoolbox_3rd {
            top: 77px;
        }
        
        .cvtoolbox_info {
            position: absolute;
            width: 250px;
            top: 45px;
            height: 0;
            text-align: center;
            left: 0;
            right: 0;
            margin-left: auto;
            margin-right: auto;
            z-index: 10;
        }
        
        /* Ensure left column has higher z-index than toolbox */
        .leftLayout {
            position: relative;
            z-index: 20;
        }
    </style>
    @endpush

    <div id="wrap">
        <div class="container-fluid">
            <div class="row">
                <!-- left column -->
                <div class="col-md-2">
                    <div class="leftLayout" id="leftLayoutContainer">
                        <div>Clothes Type</div>
                        <div class="mb-3">
                            <select class="form-select" id="clothesTypeSelect">
                                <option value="">Select a clothes type...</option>
                                @foreach($categories as $category)
                                    <optgroup label="{{ $category->name }}" data-category-id="{{ $category->id }}">
                                        @foreach($clothesTypes->where('category_id', $category->id) as $clothesType)
                                            <option value="{{ $clothesType->id }}" 
                                                    data-front-image="{{ $clothesType->front_image_url }}"
                                                    data-back-image="{{ $clothesType->back_image_url }}"
                                                    data-colors="{{ $clothesType->colors ? json_encode($clothesType->colors) : '[]' }}">
                                                {{ $clothesType->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>Shirt Size *</div>
                        <div class="mb-3">
                            <select class="form-select" id="shirtSizeSelect" required>
                                <option value="">Select a size...</option>
                                @foreach($shirtSizes as $shirtSize)
                                    <option value="{{ $shirtSize->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $shirtSize->name }} - {{ $shirtSize->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Clothes Type Preview -->
                        <div id="clothesTypePreview" class="mb-3" style="display: none;">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Selected Item</h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="text-center">
                                        <img id="previewImage" src="" alt="Preview" class="img-fluid" style="max-height: 150px;">
                                        <div class="mt-2">
                                            <strong id="previewName"></strong>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted" id="previewCategory"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="div_colors_title" style="display: none;">Color</div>
                        <div class="btn-group" data-bs-toggle="buttons" id="colorButtonGroup" style="display: none; flex-wrap: wrap; max-width: 200px;">
                            <!-- Color buttons generated dynamically by JavaScript -->
                        </div>
                        <div class="btn-toolbar">
                            <div class="add_image btn-group">
                                <iframe id="ifr_upload" name="ifr_upload" height="0" width="0" frameborder="0"></iframe>
                                <form id="frm_upload" action="" method="post" enctype="multipart/form-data" target="ifr_upload">
                                <label class="btn btn-default btn-file">
                                    <i class="fa fa-picture-o"></i>&nbsp;&nbsp;Photo<input type="file" name="image_upload" accept=".gif,.jpg,.jpeg,.png,.ico">
                                </label>
                                </form>
                            </div>
                            <div class="add_text">
                                <button type="button" class="btn btn-default btn-block" id="btn_addtext"><i class="fa fa-font"></i>&nbsp;&nbsp;Add text</button>
                            </div>
<!--<div class="add_qr btn-group">
                                <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#qrModal"><i class="fa fa-qrcode"></i>&nbsp;&nbsp;QR Code</button>
                            </div>
                            <div class="add_album btn-group">
                                <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#albumModal"><i class="fa fa-th"></i>&nbsp;&nbsp;Album</button>
                            </div>-->
                        </div>
                        <div class="message">
                        </div>
                    </div>
                </div>	
                <!-- center column -->
                <div class="col-md-8">
                    <div class="centerLayout" id="centerLayoutContainer">
                        <div class="shirt"><img src="{{ asset('images/shirts/men1_blue_front.png') }}" id="img_shirt" /></div>
                        <div class="cvtoolbox">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default" id="toolbox_centerh"><i class="fa fa-arrows-h fa-lg"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_up"><i class="fa fa-arrow-up"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_centerv"><i class="fa fa-arrows-v fa-lg"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_flipx"><i class="fa fa-road fa-lg"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_flipy"><i class="fa fa-road fa-lg fa-rotate-90"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_remove"><i class="fa fa-trash-o fa-lg"></i></button>
                            </div>
                        </div>
                        <div class="cvtoolbox cvtoolbox_2nd">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default" id="toolbox_left"><i class="fa fa-arrow-left"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_center"><i class="fa fa-arrows fa-lg"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_right"><i class="fa fa-arrow-right"></i></button>
                                <button type="button" class="btn btn-default nobtn">&nbsp;</button>
                                <button type="button" class="btn btn-default nobtn">&nbsp;</button>
                                <button type="button" class="btn btn-default nobtn">&nbsp;</button>
                            </div>
                        </div>
                        <div class="cvtoolbox cvtoolbox_3rd">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default" id="toolbox_totop"><i class="fa fa-step-backward fa-lg fa-rotate-90"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_down"><i class="fa fa-arrow-down"></i></button>
                                <button type="button" class="btn btn-default" id="toolbox_tobottom"><i class="fa fa-step-forward fa-lg fa-rotate-90"></i></button>
                                <button type="button" class="btn btn-default nobtn">&nbsp;</button>
                                <button type="button" class="btn btn-default nobtn">&nbsp;</button>
                                <button type="button" class="btn btn-default nobtn">&nbsp;</button>
                            </div>
                        </div>
                        <div class="cvtoolbox_info"><div><span></span></div></div>
                        <div id="div_canvas_front" style="margin-top: 155px;">
                            <canvas id="mainCanvas_front" width="260" height="350" class="shirt_canvas"></canvas>
                        </div>
                        <div id="div_canvas_back" style="margin-top: 155px;">
                            <canvas id="mainCanvas_back" width="260" height="350" class="shirt_canvas"></canvas>
                        </div>
                        <div class="btn-group twosides" data-bs-toggle="buttons" id="sideButtonGroup">
                            <div class="btn active">
                                <input type="radio" name="form_shirt_side" value="front" autocomplete="off" checked />
                                <div class="sidename"><i class="fa fa-bookmark-o"></i> Front</div>
                            </div>
                            <div class="btn">
                                <input type="radio" name="form_shirt_side" value="back" autocomplete="off" />
                                <div class="sidename"><i class="fa fa-bookmark"></i> Back</div>
                            </div>
                        </div>
                        <div class="div_reviewbtn">
                            <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#reviewModal"><i class="fa fa-eye"></i> Preview</button>
                            <button type="button" class="btn btn-primary" id="btnSave"><i class="fa fa-save"></i> Save</button>
                            <div class="dropup">
                                <button class="btn btn-default dropdown-toggle" type="button" id="dropdownDownload" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-download"></i> Download
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownDownload">
                                    <li><a class="dropdown-item" href="#" id="btnDownloadDesign">Download Design Only</a></li>
                                    <li><a class="dropdown-item" href="#" id="btnDownloadShirt">Download Shirt Design</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>	
                <!-- right column -->
                <div class="col-md-2">
                    <div class="rightLayout" id="rightLayoutContainer">
                        <div class="texttoolbox">
                        </div>
                        <div class="message">
                        </div>
                    </div>
                </div>	
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="reviewModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <h4 class="modal-title">&nbsp;</h4>
          </div>
          <div class="modal-body">
            <div class="shirt"><img src="" /></div>
            <div class="shirtdesign"><img src="" /></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Album Modal -->
    <div id="albumModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <h4 class="modal-title">Album</h4>
          </div>
          <div class="modal-body">
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image2.png') }})"><img bgsrc="{{ asset('images/album/image2.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image3.png') }})"><img bgsrc="{{ asset('images/album/image3.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image4.png') }})"><img bgsrc="{{ asset('images/album/image4.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image5.png') }})"><img bgsrc="{{ asset('images/album/image5.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image6.png') }})"><img bgsrc="{{ asset('images/album/image6.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image7.png') }})"><img bgsrc="{{ asset('images/album/image7.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image8.png') }})"><img bgsrc="{{ asset('images/album/image8.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
            <a href="#" class="album-item"><div style="background-image: url({{ asset('images/album/image9.png') }})"><img bgsrc="{{ asset('images/album/image9.png') }}" src="{{ asset('images/blank.png') }}" /></div></a>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <h4 class="modal-title">Generate QR Code</h4>
          </div>
          <div class="modal-body">
            <form id="qrForm">
                <div class="form-group mb-3">
                    <label for="qrText">QR Code Content:</label>
                    <input type="text" class="form-control" id="qrText" name="text" placeholder="Enter URL, text, or contact info..." required>
                </div>
                <div class="form-group mb-3">
                    <label for="qrSize">Size:</label>
                    <select class="form-control" id="qrSize" name="size">
                        <option value="200">Small (200px)</option>
                        <option value="300" selected>Medium (300px)</option>
                        <option value="400">Large (400px)</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="qrColor">QR Code Color:</label>
                    <input type="color" class="form-control" id="qrColor" name="color" value="#000000">
                </div>
                <div class="form-group mb-3">
                    <label for="qrBackground">Background Color:</label>
                    <input type="color" class="form-control" id="qrBackground" name="background" value="#FFFFFF">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="generateQrBtn">Generate QR Code</button>
                </div>
                <!-- Hidden field to store QR code ID -->
                <input type="hidden" id="qrCodeId" name="qr_code_id" value="">
            </form>
            <div id="qrPreview" style="text-align: center; margin-top: 20px; display: none;">
                <img id="qrImage" src="" alt="Generated QR Code" style="max-width: 100%; border: 1px solid #ddd;">
                <br><br>
                <button type="button" class="btn btn-success" id="addQrToDesign">Add to Design</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Design Modal -->
    <div id="saveDesignModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <h4 class="modal-title">Save Design</h4>
          </div>
          <div class="modal-body">
            <form id="saveDesignForm">
                <div class="form-group mb-3">
                    <label for="designName">Design Name:</label>
                    <input type="text" class="form-control" id="designName" name="design_name" 
                           placeholder="Enter a name for your design..." required>
                </div>
                <div class="form-group mb-3">
                    <label for="designDescription">Description (Optional):</label>
                    <textarea class="form-control" id="designDescription" name="description" 
                              rows="3" placeholder="Describe your design..."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" id="saveDesignBtn">
                        <i class="fa fa-save"></i> Save Design
                    </button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
    <!-- Load jQuery first -->
    <script type="text/javascript" src="{{ asset('js/jquery.min.js') }}"></script>
    
    <!-- Load other required scripts -->
    <script type="text/javascript" src="{{ asset('js/placeholders.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/fabric4.min.js?v=1.1') }}"></script>
    <script type="text/javascript" src="{{ asset('js/fontfaceobserver.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/merge-images.js') }}"></script>
    <script src="{{ asset('js/main.js?v=1.4') }}"></script>

    <script>
    $(document).ready(function() {
        // Test if all required libraries are loaded
        console.log('Design page loaded successfully!');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Bootstrap 5 Loaded' : 'Not loaded');
        console.log('Fabric.js version:', typeof fabric !== 'undefined' ? 'Loaded' : 'Not loaded');
        console.log('MergeImages function:', typeof mergeImages !== 'undefined' ? 'Loaded' : 'Not loaded');
        
        // Clothes Type Selection Functionality
        $('#clothesTypeSelect').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var clothesTypeId = $(this).val();
            // Get the parent optgroup's data-category-id
            var categoryId = selectedOption.parent('optgroup').data('category-id');
            
            if (clothesTypeId) {
                var frontImage = selectedOption.data('front-image');
                var backImage = selectedOption.data('back-image');
                var colors = selectedOption.data('colors');
                var clothesTypeName = selectedOption.text();
                var categoryName = selectedOption.closest('optgroup').attr('label');
                
                // Update preview
                $('#previewImage').attr('src', frontImage);
                $('#previewName').text(clothesTypeName);
                $('#previewCategory').text(categoryName);
                $('#clothesTypePreview').show();
                
                // Update main shirt image
                $('#img_shirt').attr('src', frontImage);
                
                // Update color buttons for the selected category
                updateColorButtonsForCategory(categoryId);
                
                // Store selected clothes type data for the designer
                window.selectedClothesType = {
                    id: clothesTypeId,
                    name: clothesTypeName,
                    category: categoryName,
                    frontImage: frontImage,
                    backImage: backImage,
                    colors: colors
                };
                
                console.log('Selected clothes type:', window.selectedClothesType);
            } else {
                // Hide preview if no selection
                $('#clothesTypePreview').hide();
                window.selectedClothesType = null;
            }
        });
        
        // Helper: update color buttons for a given category
        function updateColorButtonsForCategory(categoryId) {
            var colorMap = window.categoryColorMap[categoryId] || {};
            var colorGroup = $('#colorButtonGroup');
            var colorTitle = $('#div_colors_title');
            
            if (Object.keys(colorMap).length > 0) {
                // Show color section
                colorTitle.show();
                colorGroup.show();
                
                colorGroup.empty();
                var first = true;
                Object.entries(colorMap).forEach(function([color, hex]) {
                    var button = $('<div class="btn colorButton" style="background-color: ' + hex + '; flex: 0 0 calc(25% - 4px); margin: 2px;">' +
                        '<input type="radio" name="form_shirt_color" value="' + color + '" autocomplete="off" data-color="' + color + '" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
                        '</div>');
                    if (first) {
                        button.addClass('active');
                        button.find('input').prop('checked', true);
                        first = false;
                    }
                    colorGroup.append(button);
                });
                window.reattachColorButtonHandlers();
            } else {
                // Hide color section if no colors available
                colorTitle.hide();
                colorGroup.hide();
            }
        }

        // Add label-id to optgroups for category detection
        $('#clothesTypeSelect optgroup').each(function() {
            var label = $(this).attr('label');
            var category = window.categories ? window.categories.find(c => c.name === label) : null;
            if (category) {
                $(this).attr('label-id', category.id);
            }
        });

        // Make categories available to JS
        window.categories = @json($categories);
        window.categoryColorMap = @json($categoryColorMap);
        
        // Load existing design data
        $(document).ready(function() {
            console.log('=== LOADING EXISTING DESIGN DATA ===');
            
            // Load design details
            var design = @json($design);
            console.log('Design data:', design);
            
            // Set design name and description
            $('#designName').val(design.name);
            $('#designDescription').val(design.description);
            
            // Set clothes type
            if (design.clothes_type_id) {
                $('#clothesTypeSelect').val(design.clothes_type_id).trigger('change');
                
                // Set color after clothes type is loaded and color buttons are created
                if (design.color_code) {
                    setTimeout(function() {
                        console.log('Setting color to:', design.color_code);
                        var colorRadio = $('input[name="form_shirt_color"][value="' + design.color_code + '"]');
                        if (colorRadio.length > 0) {
                            colorRadio.prop('checked', true).trigger('change');
                            // Update active state
                            $('#colorButtonGroup .btn').removeClass('active');
                            colorRadio.closest('.btn').addClass('active');
                            
                            // Switch to a clothes type that has this color and update the image
                            switchToClothesTypeWithColor(design.color_code);
                            
                            console.log('Color set successfully to:', design.color_code);
                        } else {
                            console.log('Color button not found for:', design.color_code);
                        }
                    }, 1000); // Wait for clothes type change to complete and color buttons to be created
                }
            }
            
            // Set shirt size
            if (design.shirt_size_id) {
                $('#shirtSizeSelect').val(design.shirt_size_id);
            }
            
            // Load QR code if available
            if (design.qr_code && design.qr_code.id) {
                console.log('=== LOADING EXISTING QR CODE ===');
                console.log('QR Code ID:', design.qr_code.id);
                console.log('QR Code Name:', design.qr_code.name);
                console.log('QR Code File Path:', design.qr_code.file_path);
                
                // Set the QR code ID in the hidden field
                $('#qrCodeId').val(design.qr_code.id);
                
                // Load QR code to canvas when canvas is ready
                function checkCanvasAndLoadQrCode() {
                    console.log('=== CHECKING CANVAS READINESS FOR QR CODE ===');
                    console.log('Canvas front exists:', typeof canvas_front !== 'undefined');
                    console.log('Canvas back exists:', typeof canvas_back !== 'undefined');
                    console.log('Fabric.js available:', typeof fabric !== 'undefined');
                    
                    if (typeof canvas_front === 'undefined' || typeof canvas_back === 'undefined') {
                        console.log('Canvas not ready yet, waiting 500ms more...');
                        setTimeout(checkCanvasAndLoadQrCode, 500);
                        return;
                    }
                    
                    console.log('Canvas is ready! Loading QR code...');
                    var qrCodeUrl = '{{ $design->qrCode && $design->qrCode->file_path ? url('/qr-codes/' . basename($design->qrCode->file_path)) : asset('images/blank.png') }}';
                    console.log('QR Code URL:', qrCodeUrl);
                    
                    loadQrCodeToCanvas(qrCodeUrl);
                }
                
                // Start checking for canvas readiness
                setTimeout(checkCanvasAndLoadQrCode, 1000);
                
                function loadQrCodeToCanvas(qrCodeUrl) {
                    console.log('=== LOADING QR CODE TO CANVAS ===');
                    console.log('URL to load:', qrCodeUrl);
                    
                    // Add QR code to both canvases
                    fabric.Image.fromURL(qrCodeUrl, function(oImgFront) {
                        console.log('=== FRONT CANVAS IMAGE LOADED ===');
                        console.log('Image object:', oImgFront);
                        console.log('Image width:', oImgFront.get('width'));
                        console.log('Image height:', oImgFront.get('height'));
                        
                        // Add to front canvas
                        canvas_front.add(oImgFront);
                        if (oImgFront.get('width') * getZoom() > canvas_front.get('width') / 2) {
                            oImgFront.scaleToWidth(canvas_front.get('width') / 2);
                        }
                        oImgFront.viewportCenter().setCoords();
                        canvas_front.renderAll();
                        
                        console.log('Front canvas objects count:', canvas_front.getObjects().length);
                        
                        // Add to back canvas
                        fabric.Image.fromURL(qrCodeUrl, function(oImgBack) {
                            console.log('=== BACK CANVAS IMAGE LOADED ===');
                            canvas_back.add(oImgBack);
                            if (oImgBack.get('width') * getZoom() > canvas_back.get('width') / 2) {
                                oImgBack.scaleToWidth(canvas_back.get('width') / 2);
                            }
                            oImgBack.viewportCenter().setCoords();
                            canvas_back.renderAll();
                            
                            console.log('Back canvas objects count:', canvas_back.getObjects().length);
                            console.log('=== QR CODE LOADING COMPLETE ===');
                        });
                    }, function(error) {
                        console.error('=== ERROR LOADING QR CODE IMAGE ===');
                        console.error('Error details:', error);
                    });
                }
            }
            
            // Load canvas data if available
            if (design.front_canvas_data) {
                console.log('=== LOADING FRONT CANVAS DATA ===');
                setTimeout(function() {
                    if (typeof canvas_front !== 'undefined') {
                        try {
                            var canvasData = JSON.parse(design.front_canvas_data);
                            canvas_front.loadFromJSON(canvasData, function() {
                                console.log('Front canvas data loaded successfully');
                                canvas_front.renderAll();
                            });
                        } catch (error) {
                            console.error('Error loading front canvas data:', error);
                        }
                    }
                }, 2000);
            }
            
            if (design.back_canvas_data) {
                console.log('=== LOADING BACK CANVAS DATA ===');
                setTimeout(function() {
                    if (typeof canvas_back !== 'undefined') {
                        try {
                            var canvasData = JSON.parse(design.back_canvas_data);
                            canvas_back.loadFromJSON(canvasData, function() {
                                console.log('Back canvas data loaded successfully');
                                canvas_back.renderAll();
                            });
                        } catch (error) {
                            console.error('Error loading back canvas data:', error);
                        }
                    }
                }, 2000);
            }
            
            // Load saved photos if available
            if (design.photos && design.photos.length > 0) {
                // Only load individual photos if there's no canvas data
                // Canvas data already contains all images, so we don't need to load them separately
                var hasCanvasData = design.front_canvas_data || design.back_canvas_data;
                
                if (!hasCanvasData) {
                    setTimeout(function() {
                        if (typeof canvas_front !== 'undefined' && typeof canvas_back !== 'undefined') {
                            design.photos.forEach(function(photo, index) {
                                // Construct the photo URL
                                var photoUrl = photo.file_path ? '{{ Storage::url("") }}' + photo.file_path : '{{ asset('images/blank.png') }}';
                                
                                // Load photo to the appropriate canvas
                                var targetCanvas = photo.side === 'back' ? canvas_back : canvas_front;
                                
                                fabric.Image.fromURL(photoUrl, function(img) {
                                    // Restore original properties if available
                                    if (photo.left !== undefined) img.set('left', photo.left);
                                    if (photo.top !== undefined) img.set('top', photo.top);
                                    if (photo.angle !== undefined) img.set('angle', photo.angle);
                                    if (photo.scaleX !== undefined) img.set('scaleX', photo.scaleX);
                                    if (photo.scaleY !== undefined) img.set('scaleY', photo.scaleY);
                                    
                                    targetCanvas.add(img);
                                    targetCanvas.renderAll();
                                }, function(error) {
                                    // Photo failed to load, continue silently
                                });
                            });
                        }
                    }, 2500); // Wait a bit longer for canvas data to load first
                }
            }
            
            // Load saved texts if available
            if (design.texts && design.texts.length > 0) {
                // Only load individual texts if there's no canvas data
                // Canvas data already contains all texts, so we don't need to load them separately
                var hasCanvasData = design.front_canvas_data || design.back_canvas_data;
                
                if (!hasCanvasData) {
                    setTimeout(function() {
                        if (typeof canvas_front !== 'undefined' && typeof canvas_back !== 'undefined') {
                            design.texts.forEach(function(textData, index) {
                                // Create text object
                                var text = new fabric.Text(textData.text, {
                                    left: textData.left || 0,
                                    top: textData.top || 0,
                                    fontFamily: textData.fontFamily || 'Arial',
                                    fontSize: textData.fontSize || 16,
                                    fill: textData.fill || '#000000',
                                    angle: textData.angle || 0,
                                    scaleX: textData.scaleX || 1,
                                    scaleY: textData.scaleY || 1
                                });
                                
                                // Add to appropriate canvas
                                var targetCanvas = textData.side === 'back' ? canvas_back : canvas_front;
                                targetCanvas.add(text);
                                targetCanvas.renderAll();
                            });
                        }
                    }, 2500); // Wait a bit longer for canvas data to load first
                }
            }
        });

        // Initialize Bootstrap 5 button groups
        var typeButtonGroup = document.getElementById('typeButtonGroup');
        var colorButtonGroup = document.getElementById('colorButtonGroup');
        var sideButtonGroup = document.getElementById('sideButtonGroup');
        
        // Manual event handling for type selection (Bootstrap 5 compatibility)
        $('#typeButtonGroup .btn').on('click', function(e) {
            e.preventDefault();
            var radio = $(this).find('input[type="radio"]');
            radio.prop('checked', true).trigger('change');
            
            // Update active state
            $('#typeButtonGroup .btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // Manual event handling for color selection (Bootstrap 5 compatibility)
        $('#colorButtonGroup .btn').on('click', function(e) {
            e.preventDefault();
            var selectedColor = $(this).find('input[type="radio"]').val();
            
            var radio = $(this).find('input[type="radio"]');
            radio.prop('checked', true).trigger('change');
            
            // Update active state
            $('#colorButtonGroup .btn').removeClass('active');
            $(this).addClass('active');
            
            // Switch to a T-shirt that has this color
            switchToClothesTypeWithColor(selectedColor);
        });
        
        // Function to switch to a clothes type that has the selected color
        function switchToClothesTypeWithColor(color) {
            // Get the currently selected clothes type
            var currentSelection = $('#clothesTypeSelect option:selected');
            var currentColors = currentSelection.data('colors');
            
            // Check if the current selection already has this color
            if (currentColors && currentColors.includes(color)) {
                // Current selection already has this color, just update the image
                var currentSide = $('input[name="form_shirt_side"]:checked').val();
                var imageUrl = currentSide === 'back' ? currentSelection.data('back-image') : currentSelection.data('front-image');
                $('#img_shirt').attr('src', imageUrl);
                return;
            }
            
            // If current selection doesn't have this color, find one that does
            var availableTypes = [];
            $('#clothesTypeSelect option').each(function() {
                var colors = $(this).data('colors');
                if (colors && colors.includes(color)) {
                    availableTypes.push({
                        id: $(this).val(),
                        name: $(this).text(),
                        frontImage: $(this).data('front-image'),
                        backImage: $(this).data('back-image')
                    });
                }
            });
            
            if (availableTypes.length > 0) {
                // Find a type that has this color and is in the same category as current selection
                var currentCategoryId = currentSelection.parent('optgroup').data('category-id');
                var sameCategoryType = availableTypes.find(function(type) {
                    var option = $('#clothesTypeSelect option[value="' + type.id + '"]');
                    return option.parent('optgroup').data('category-id') == currentCategoryId;
                });
                
                if (sameCategoryType) {
                    // Use a type from the same category
                    var selectedType = sameCategoryType;
                } else {
                    // Use the first available type
                    var selectedType = availableTypes[0];
                }
                
                // Update the main shirt image without changing the dropdown
                var currentSide = $('input[name="form_shirt_side"]:checked').val();
                var imageUrl = currentSide === 'back' ? selectedType.backImage : selectedType.frontImage;
                $('#img_shirt').attr('src', imageUrl);
            }
        }
        
        // Function to reattach color button event handlers (for dynamically created buttons)
        window.reattachColorButtonHandlers = function() {
            $('#colorButtonGroup .btn').off('click').on('click', function(e) {
                e.preventDefault();
                var selectedColor = $(this).find('input[type="radio"]').val();
                
                var radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true).trigger('change');
                
                // Update active state
                $('#colorButtonGroup .btn').removeClass('active');
                $(this).addClass('active');
                
                // Switch to a T-shirt that has this color
                switchToClothesTypeWithColor(selectedColor);
            });
        };
        
        // Call the function initially for any existing color buttons
        window.reattachColorButtonHandlers();
        
        // Manual event handling for side selection (Bootstrap 5 compatibility)
        $('#sideButtonGroup .btn').on('click', function(e) {
            e.preventDefault();
            console.log('Side button clicked:', $(this).find('input[type="radio"]').val());
            var radio = $(this).find('input[type="radio"]');
            radio.prop('checked', true).trigger('change');
            
            // Update active state
            $('#sideButtonGroup .btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // Also listen for the radio change events directly
        $('input[name="form_shirt_type"]').on('change', function() {
            console.log('Type radio change event triggered:', this.value);
        });
        
        $('input[name="form_shirt_color"]').on('change', function() {
            console.log('Color radio change event triggered:', this.value);
        });
        
        $('input[name="form_shirt_side"]').on('change', function() {
            console.log('Side radio change event triggered:', this.value);
        });
        
        // Set CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Generate QR Code
        $('#generateQrBtn').click(function() {
            var formData = {
                text: $('#qrText').val(),
                size: $('#qrSize').val(),
                color: $('#qrColor').val(),
                background: $('#qrBackground').val(),
                name: 'Design QR Code ' + new Date().toLocaleString()
            };

            $.ajax({
                url: '{{ route("qr-generate-and-save") }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#qrImage').attr('src', response.data_url);
                        $('#qrPreview').show();
                        
                        // Store the QR code ID for later use in design saving
                        $('#qrCodeId').val(response.qr_code_id);
                        
                        // Show success message
                        showAlert('QR code generated and saved successfully!', 'success', 3000);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 403) {
                        showAlert(xhr.responseJSON.message || 'QR code limit reached. Please upgrade to premium for unlimited QR codes.', 'danger');
                    } else {
                        showAlert('Error generating QR code. Please try again.', 'danger');
                    }
                }
            });
        });

        // Add QR Code to Design
        $('#addQrToDesign').click(function() {
            var qrDataUrl = $('#qrImage').attr('src');
            if (qrDataUrl) {
                // Clear both canvases first
                canvas_front.clear();
                canvas_back.clear();
                
                // Add QR code to both canvases
                fabric.Image.fromURL(qrDataUrl, function(oImgFront) {
                    // Add to front canvas
                    canvas_front.add(oImgFront);
                    if (oImgFront.get('width') * getZoom() > canvas_front.get('width') / 2) {
                        oImgFront.scaleToWidth(canvas_front.get('width') / 2);
                    }
                    oImgFront.viewportCenter().setCoords();
                    canvas_front.renderAll();
                    
                    // Add to back canvas
                    fabric.Image.fromURL(qrDataUrl, function(oImgBack) {
                        canvas_back.add(oImgBack);
                        if (oImgBack.get('width') * getZoom() > canvas_back.get('width') / 2) {
                            oImgBack.scaleToWidth(canvas_back.get('width') / 2);
                        }
                        oImgBack.viewportCenter().setCoords();
                        canvas_back.renderAll();
                        
                        console.log('QR code added to both canvases successfully');
                    });
                });
                
                var modal = bootstrap.Modal.getInstance(document.getElementById('qrModal'));
                modal.hide();
            }
        });

        // Save Design Functionality
        $('#btnSave').on('click', function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            @guest
                showAlert('Please log in to save your design.', 'danger');
                return;
            @endguest
            
            // Validate required fields
            var clothesTypeId = $('#clothesTypeSelect').val();
            var shirtSizeId = $('#shirtSizeSelect').val();
            
            if (!clothesTypeId) {
                showAlert('Please select a clothes type before saving.', 'danger');
                return;
            }
            
            if (!shirtSizeId) {
                showAlert('Please select a shirt size before saving.', 'danger');
                return;
            }
            
            // Show save design modal
            var saveModal = new bootstrap.Modal(document.getElementById('saveDesignModal'));
            saveModal.show();
        });

        // Handle save design form submission
        $('#saveDesignForm').on('submit', function(e) {
            e.preventDefault();
            
            var saveBtn = $('#saveDesignBtn');
            var originalText = saveBtn.html();
            saveBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            
            // Get current design data
            var clothesTypeId = $('#clothesTypeSelect').val();
            var shirtSizeId = $('#shirtSizeSelect').val();
            var selectedColor = $('input[name="form_shirt_color"]:checked').val();
            var qrCodeId = $('#qrCodeId').val();
            
            // Get canvas data
            var frontCanvasData = null;
            var backCanvasData = null;
            
            if (typeof canvas_front !== 'undefined') {
                frontCanvasData = JSON.stringify(canvas_front.toJSON());
            }
            if (typeof canvas_back !== 'undefined') {
                backCanvasData = JSON.stringify(canvas_back.toJSON());
            }
            
            // Generate design-only images (similar to download functionality)
            var frontDesignImage = null;
            var backDesignImage = null;
            
            if (typeof canvas_front !== 'undefined') {
                try {
                    frontDesignImage = canvas_front.toDataURL({
                        format: 'png', 
                        multiplier: Math.ceil(10000 / (getZoom()*canvas_exportwidth/canvas_review_width)) / 10000
                    });
                } catch (error) {
                    console.warn('Front canvas toDataURL failed (possibly due to CORS):', error);
                    // Continue without front design image
                }
            }
            
            if (typeof canvas_back !== 'undefined') {
                try {
                    backDesignImage = canvas_back.toDataURL({
                        format: 'png', 
                        multiplier: Math.ceil(10000 / (getZoom()*canvas_exportwidth/canvas_review_width)) / 10000
                    });
                } catch (error) {
                    console.warn('Back canvas toDataURL failed (possibly due to CORS):', error);
                    // Continue without back design image
                }
            }
            
            // Prepare form data
            var formData = {
                name: $('#designName').val(),
                description: $('#designDescription').val(),
                clothes_type_id: clothesTypeId,
                shirt_size_id: shirtSizeId,
                color_code: selectedColor,
                qr_code_id: qrCodeId,
                front_canvas_data: frontCanvasData,
                back_canvas_data: backCanvasData,
                front_design_image: frontDesignImage,
                back_design_image: backDesignImage,
                status: 'saved',
                cover_image_data: null // Will be populated if cover image is generated
            };
            
            // Generate cover image if possible
            if (typeof mergeImages !== 'undefined') {
                // Determine which canvas to use
                var activeCanvas = canvas_front; // Always use front canvas for cover image
                
                // Check if all required variables are available
                if (typeof activeCanvas !== 'undefined' && 
                    typeof shirtWidth !== 'undefined' && 
                    typeof canvas_exportwidth !== 'undefined' && 
                    typeof canvas_review_width !== 'undefined' && 
                    typeof canvas_top !== 'undefined' && 
                    typeof getZoom === 'function') {
                    
                    try {
                        // Get current shirt image
                        var shirtImage = $('#img_shirt').attr('src');
                        
                        // Get DOM elements
                        var shirtImgElem = document.getElementById('img_shirt');
                        var canvasElem = document.getElementById('mainCanvas_front');
                        var shirtRect = shirtImgElem.getBoundingClientRect();
                        var canvasRect = canvasElem.getBoundingClientRect();

                        // Set mergeImages output size to 600x600
                        var mergeWidth = 600;
                        var mergeHeight = 600;
                        var shirtBoxX = 170; // (600-260)/2
                        var shirtBoxY = 125; // (600-350)/2
                        var shirtBoxWidth = 260;
                        var shirtBoxHeight = 350;

                        // Export the canvas at 260x350
                        var multiplier = 1; // canvas is already 260x350

                        // Use these in mergeImages
                        mergeImages([
                          { src: shirtImgElem.src, x: 0, y: 0 },
                          { 
                            src: (function() {
                                try {
                                    return canvas_front.toDataURL({ format: 'png', multiplier: multiplier });
                                } catch (error) {
                                    console.warn('Canvas toDataURL failed for cover image:', error);
                                    return null;
                                }
                            })(),
                            x: shirtBoxX,
                            y: shirtBoxY
                          }
                        ], {
                          width: mergeWidth,
                          height: mergeHeight
                        }).then(function(b64) {
                            formData.cover_image_data = b64;
                            sendSaveRequest(formData);
                        }).catch(function(error) {
                            console.warn('Merge images failed:', error);
                            sendSaveRequest(formData);
                        });
                    } catch (error) {
                        sendSaveRequest(formData);
                    }
                } else {
                    sendSaveRequest(formData);
                }
            } else {
                sendSaveRequest(formData);
            }
            
            sendSaveRequest(formData);
        });
        
        function sendSaveRequest(formData) {
            // Send AJAX request
            $.ajax({
                url: '{{ route("designs.update", $design->id) }}',
                type: 'PUT',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showAlert('Design updated successfully!', 'success', 3000);
                        
                        // Close modal
                        var saveModal = bootstrap.Modal.getInstance(document.getElementById('saveDesignModal'));
                        saveModal.hide();
                        
                        // Redirect to design view page after a short delay
                        setTimeout(() => {
                            window.location.href = '{{ route("designs.show", $design->id) }}';
                        }, 1000);
                    } else {
                        showAlert('Error updating design: ' + response.message, 'danger');
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Error updating design.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                        
                        // Show specific validation errors if available
                        if (xhr.responseJSON.errors) {
                            var errorDetails = '';
                            $.each(xhr.responseJSON.errors, function(field, errors) {
                                errorDetails += field + ': ' + errors.join(', ') + '\n';
                            });
                            errorMessage += '\n\nDetails:\n' + errorDetails;
                        }
                    }
                    showAlert(errorMessage, 'danger');
                },
                complete: function() {
                    saveBtn.html(originalText).prop('disabled', false);
                }
            });
        }

        // Handle delete design
        $('.delete-design').on('click', function() {
            var designId = $(this).data('design-id');
            var designName = $(this).data('design-name');
            
            $('#deleteDesignName').text(designName);
            $('#confirmDeleteDesign').data('design-id', designId);
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteDesignModal'));
            deleteModal.show();
        });
        
        // Confirm delete
        $('#confirmDeleteDesign').on('click', function() {
            var designId = $(this).data('design-id');
            
            $.ajax({
                url: '/designs/' + designId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = '{{ route("designs.index") }}';
                    } else {
                        showAlert('Error deleting design: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('Error deleting design. Please try again.', 'danger');
                }
            });
        });
    });
    </script>
    @endpush

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDesignModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title mb-0">Delete Design</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Delete "<span id="deleteDesignName" class="fw-medium"></span>"?</p>
                    <small class="text-danger">This action cannot be undone.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteDesign">Delete</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 