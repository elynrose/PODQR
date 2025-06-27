<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('T-Shirt Designer') }}
        </h2>
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
        $selectedCategoryId = null; // Don't select any category initially
    @endphp

    @if(isset($defaultQrCode))
        <!-- QR Code loaded from URL parameter -->
    @endif

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
        
        /* QR Code Selection Modal Styling */
        .qr-code-card {
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }
        
        .qr-code-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .qr-code-card .card-body {
            padding: 1rem;
        }
        
        .qr-code-card img {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background: white;
        }
        
        .qr-code-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .qr-code-card .card-text {
            font-size: 0.8rem;
            line-height: 1.3;
        }
        
        .select-qr-btn {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
        }
        
        /* Modal sizing for QR code selection */
        #qrSelectModal .modal-dialog {
            max-width: 800px;
        }
        
        #qrSelectModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Debug styling to ensure QR code button is visible */
        .add_qr {
            display: block !important;
            visibility: visible !important;
            border: 2px solid red !important;
            margin: 5px !important;
        }
        
        .add_qr .btn {
            display: block !important;
            visibility: visible !important;
            background-color: #007bff !important;
            color: white !important;
            border: 2px solid #0056b3 !important;
        }
        
        /* Ensure all buttons in toolbar are visible */
        .btn-toolbar {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 5px !important;
            margin: 10px 0 !important;
        }
        
        .btn-toolbar > div {
            display: block !important;
            visibility: visible !important;
        }
        
        .btn-toolbar .btn {
            display: inline-block !important;
            visibility: visible !important;
            margin: 2px !important;
        }
        
        /* Make sure the add_text button is also visible */
        .add_text {
            display: block !important;
            visibility: visible !important;
        }
        
        .add_text .btn {
            display: block !important;
            visibility: visible !important;
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
                        <div class="btn-toolbar" style="display: flex; flex-wrap: wrap; gap: 5px; margin: 10px 0;">
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
                            <div class="add_qr btn-group">
                                <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#qrSelectModal"><i class="fa fa-qrcode"></i>&nbsp;&nbsp;QR Code</button>
                            </div>
                            <div class="add_ai_image btn-group">
                                <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#dalleModal"><i class="fa fa-magic"></i>&nbsp;&nbsp;AI Art</button>
                            </div>
<!--<div class="add_album btn-group">
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
                <img id="qrImage" src="" alt="Generated QR Code" style="max-width: 200%; transform: scale(2); transform-origin: center; border: 1px solid #ddd; margin: 20px auto; display: block;">
                <br><br>
                <button type="button" class="btn btn-success" id="addQrToDesign">Add to Design</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Code Selection Modal -->
    <div id="qrSelectModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <h4 class="modal-title">Select QR Code</h4>
          </div>
          <div class="modal-body">
            @if($userQrCodes->count() > 0)
                <div class="row">
                    @foreach($userQrCodes as $qrCode)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card qr-code-card" data-qr-id="{{ $qrCode->id }}" data-qr-url="{{ $qrCode->file_path ? Storage::url($qrCode->file_path) : asset('images/blank.png') }}">
                                <div class="card-body text-center">
                                    <img src="{{ $qrCode->file_path ? Storage::url($qrCode->file_path) : asset('images/blank.png') }}" alt="QR Code" class="img-fluid mb-2" style="max-height: 150px;">
                                    <h6 class="card-title">{{ $qrCode->name }}</h6>
                                    <p class="card-text small text-muted">{{ Str::limit($qrCode->content, 50) }}</p>
                                    <button type="button" class="btn btn-primary btn-sm select-qr-btn" data-qr-id="{{ $qrCode->id }}" data-qr-url="{{ $qrCode->file_path ? Storage::url($qrCode->file_path) : asset('images/blank.png') }}">
                                        <i class="fa fa-plus"></i> Add to Design
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fa fa-qrcode fa-3x text-muted mb-3"></i>
                    <h5>No QR Codes Found</h5>
                    <p class="text-muted">You haven't created any QR codes yet.</p>
                    <a href="{{ route('qr-generator') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create QR Code
                    </a>
                </div>
            @endif
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

    <!-- DALL-E AI Image Generation Modal -->
    <div id="dalleModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <h4 class="modal-title"><i class="fa fa-magic"></i> AI Art Generator</h4>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label for="aiPrompt" class="form-label">Describe the image you want to generate:</label>
                <textarea class="form-control" id="aiPrompt" rows="3" placeholder="e.g., A cute red cartoon dog playing in a garden"></textarea>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="aiSize" class="form-label">Image Size:</label>
                    <select class="form-select" id="aiSize">
                        <option value="1024x1024">Square (1024x1024)</option>
                        <option value="1792x1024">Landscape (1792x1024)</option>
                        <option value="1024x1792">Portrait (1024x1792)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="aiQuality" class="form-label">Quality:</label>
                    <select class="form-select" id="aiQuality">
                        <option value="standard">Standard</option>
                        <option value="hd">HD</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary" id="testApiBtn">
                    <i class="bi bi-wrench"></i> Test API
                </button>
                <button type="button" class="btn btn-primary" id="generateAiBtn">
                    <i class="bi bi-magic"></i> Generate Image
                </button>
            </div>
            <div id="aiStatus" class="mt-3" style="display: none;"></div>
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
        
        // Initialize Bootstrap 5 button groups
        var typeButtonGroup = document.getElementById('typeButtonGroup');
        var colorButtonGroup = document.getElementById('colorButtonGroup');
        var sideButtonGroup = document.getElementById('sideButtonGroup');
        
        if (typeButtonGroup) {
            console.log('Type button group found, initializing...');
        }
        if (colorButtonGroup) {
            console.log('Color button group found, initializing...');
        }
        if (sideButtonGroup) {
            console.log('Side button group found, initializing...');
        }
        
        // Manual event handling for type selection (Bootstrap 5 compatibility)
        $('#typeButtonGroup .btn').on('click', function(e) {
            e.preventDefault();
            console.log('Type button clicked:', $(this).find('input[type="radio"]').val());
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
            console.log('Color button clicked:', selectedColor);
            
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
                console.log('Updated shirt image for color:', color, 'using current selection');
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
                
                console.log('Updated shirt image for color:', color, 'using type:', selectedType.name);
            } else {
                console.log('No clothes type found with color:', color);
            }
        }
        
        // Function to reattach color button event handlers (for dynamically created buttons)
        window.reattachColorButtonHandlers = function() {
            $('#colorButtonGroup .btn').off('click').on('click', function(e) {
                e.preventDefault();
                var selectedColor = $(this).find('input[type="radio"]').val();
                console.log('Color button clicked (reattached):', selectedColor);
                
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
                        showAlert(xhr.responseJSON.message || 'QR code limit reached. Please upgrade to premium for unlimited QR codes.', 'warning');
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

        // Handle default QR code loading
        @if($defaultQrCode)
            console.log('=== QR CODE LOADING STARTED ===');
            $(document).ready(function() {
                console.log('Document ready, starting QR code loading...');
                
                // Load the QR code as the default image
                var qrCodeUrl = '{{ $defaultQrCode->file_path ? Storage::url($defaultQrCode->file_path) : asset('images/blank.png') }}';
                console.log('QR Code URL:', qrCodeUrl);
                console.log('QR Code ID:', {{ $defaultQrCode->id }});
                console.log('QR Code Name:', '{{ $defaultQrCode->name }}');
                
                // Function to check if canvas is ready and load QR code
                function checkCanvasAndLoad() {
                    console.log('=== CHECKING CANVAS READINESS ===');
                    console.log('Canvas front exists:', typeof canvas_front !== 'undefined');
                    console.log('Canvas back exists:', typeof canvas_back !== 'undefined');
                    console.log('Fabric.js available:', typeof fabric !== 'undefined');
                    
                    if (typeof canvas_front === 'undefined' || typeof canvas_back === 'undefined') {
                        console.log('Canvas not ready yet, waiting 500ms more...');
                        setTimeout(checkCanvasAndLoad, 500);
                        return;
                    }
                    
                    console.log('Canvas is ready! Loading QR code...');
                    console.log('Canvas front dimensions:', canvas_front.get('width'), 'x', canvas_front.get('height'));
                    console.log('Canvas back dimensions:', canvas_back.get('width'), 'x', canvas_back.get('height'));
                    console.log('Canvas front zoom:', getZoom());
                    console.log('Canvas front objects before loading:', canvas_front.getObjects().length);
                    
                    loadQrCodeToCanvas(qrCodeUrl);
                }
                
                // Start checking for canvas readiness
                setTimeout(checkCanvasAndLoad, 1000);
                
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
            });
        @endif

        // QR Code Selection Functionality
        $('.select-qr-btn').on('click', function() {
            var qrId = $(this).data('qr-id');
            var qrUrl = $(this).data('qr-url');
            
            // Close the modal
            var qrSelectModal = bootstrap.Modal.getInstance(document.getElementById('qrSelectModal'));
            qrSelectModal.hide();
            
            // Add QR code to the current canvas
            addQrCodeToCanvas(qrUrl, qrId);
        });
        
        // Function to add QR code to canvas
        function addQrCodeToCanvas(qrUrl, qrId) {
            // Determine which canvas is currently active
            var targetCanvas = $('input[name="form_shirt_side"]:checked').val() === 'back' ? canvas_back : canvas_front;
            
            if (typeof targetCanvas === 'undefined') {
                showAlert('Canvas not initialized. Please try again.', 'warning');
                return;
            }
            
            // Load the QR code image
            fabric.Image.fromURL(qrUrl, function(img) {
                // Set initial position (center of canvas)
                var canvasWidth = targetCanvas.getWidth();
                var canvasHeight = targetCanvas.getHeight();
                
                // Scale the QR code to a reasonable size (max 100px)
                var maxSize = 100;
                var scale = Math.min(maxSize / img.width, maxSize / img.height);
                img.scale(scale);
                
                // Center the QR code
                img.set({
                    left: (canvasWidth - img.width * scale) / 2,
                    top: (canvasHeight - img.height * scale) / 2,
                    selectable: true,
                    hasControls: true,
                    hasBorders: true,
                    lockUniScaling: false,
                    lockRotation: false
                });
                
                // Add to canvas
                targetCanvas.add(img);
                targetCanvas.setActiveObject(img);
                targetCanvas.renderAll();
                
                // Store QR code ID for saving
                $('#qrCodeId').val(qrId);
                
                // Show success message
                showAlert('QR code added to design!', 'success');
                
                console.log('QR code added to canvas:', {
                    qrId: qrId,
                    qrUrl: qrUrl,
                    canvas: $('input[name="form_shirt_side"]:checked').val()
                });
                
            }, function(error) {
                console.error('Error loading QR code image:', error);
                showAlert('Error loading QR code. Please try again.', 'danger');
            });
        }

        // Save Design Functionality
        $('#btnSave').on('click', function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            @guest
                showAlert('Please log in to save your design.', 'warning');
                return;
            @endguest
            
            // Validate required fields
            var clothesTypeId = $('#clothesTypeSelect').val();
            var shirtSizeId = $('#shirtSizeSelect').val();
            
            if (!clothesTypeId) {
                showAlert('Please select a clothes type before saving.', 'warning');
                return;
            }
            
            if (!shirtSizeId) {
                showAlert('Please select a shirt size before saving.', 'warning');
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
            
            // Provide default color if none selected
            if (!selectedColor) {
                selectedColor = 'Black'; // Default to black if no color selected
            }
            
            var qrCodeId = null;
            
            // Check if there's a QR code in the design
            // First check if a new QR code was generated
            var newQrCodeId = $('#qrCodeId').val();
            if (newQrCodeId) {
                qrCodeId = newQrCodeId;
            } else {
                // Fall back to default QR code if available
                @if(isset($defaultQrCode))
                    qrCodeId = {{ $defaultQrCode->id }};
                @endif
            }
            
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
                frontDesignImage = canvas_front.toDataURL({
                    format: 'png', 
                    multiplier: Math.ceil(10000 / (getZoom()*canvas_exportwidth/canvas_review_width)) / 10000
                });
            }
            
            if (typeof canvas_back !== 'undefined') {
                backDesignImage = canvas_back.toDataURL({
                    format: 'png', 
                    multiplier: Math.ceil(10000 / (getZoom()*canvas_exportwidth/canvas_review_width)) / 10000
                });
            }
            
            // Prepare form data
            var formData = {
                clothes_type_id: clothesTypeId,
                shirt_size_id: shirtSizeId,
                color_code: selectedColor,
                qr_code_id: qrCodeId,
                front_canvas_data: frontCanvasData,
                back_canvas_data: backCanvasData,
                design_name: $('#designName').val(),
                description: $('#designDescription').val(),
                front_design_image: frontDesignImage,
                back_design_image: backDesignImage,
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
                            src: canvas_front.toDataURL({ format: 'png', multiplier: multiplier }),
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
            
            function sendSaveRequest(formData) {
                // Send AJAX request
                $.ajax({
                    url: '{{ route("designs.save-from-designer") }}',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showAlert('Design saved successfully!', 'success', 3000);
                            
                            // Close modal
                            var saveModal = bootstrap.Modal.getInstance(document.getElementById('saveDesignModal'));
                            saveModal.hide();
                            
                            // Redirect to design view page
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        } else {
                            showAlert('Error saving design: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Error saving design.';
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
        });

        // DALL-E AI Image Generation Functionality
        $('#testApiBtn').on('click', function() {
            var $btn = $(this);
            var $status = $('#aiStatus');
            
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Testing...');
            $status.html('<div class="alert alert-info"><i class="bi bi-info-circle"></i> Testing API connection...</div>').show();
            
            $.get('/dalle/test')
                .done(function(response) {
                    if (response.success) {
                        var modelInfo = response.has_dalle3 ? 'DALL-E 3' : (response.has_dalle2 ? 'DALL-E 2' : 'No DALL-E models');
                        $status.html('<div class="alert alert-success"><i class="bi bi-check-circle"></i> API Test Successful!<br>Available: ' + modelInfo + '</div>');
                    } else {
                        $status.html('<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> ' + response.message + '</div>');
                    }
                })
                .fail(function(xhr) {
                    var errorMsg = 'API test failed';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    $status.html('<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ' + errorMsg + '</div>');
                })
                .always(function() {
                    $btn.prop('disabled', false).html('<i class="bi bi-wrench"></i> Test API');
                });
        });

        $('#generateAiBtn').on('click', function() {
            var prompt = $('#aiPrompt').val().trim();
            var size = $('#aiSize').val();
            var quality = $('#aiQuality').val();
            
            if (!prompt) {
                alert('Please enter a description for the image you want to generate.');
                return;
            }
            
            if (prompt.length < 10) {
                alert('Please provide a more detailed description (at least 10 characters).');
                return;
            }
            
            var $btn = $(this);
            var $status = $('#aiStatus');
            
            // Show loading state
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Generating...');
            $status.html('<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Generating AI art... This may take 10-30 seconds.</div>').show();
            
            // Make API request
            $.ajax({
                url: '/dalle/generate',
                method: 'POST',
                data: {
                    prompt: prompt,
                    size: size,
                    quality: quality,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + response.message + '</div>');
                        
                        // Add image to canvas
                        fabric.Image.fromURL(response.image_url, function(img) {
                            // Scale image to reasonable size
                            var maxSize = 200;
                            var scale = Math.min(maxSize / img.width, maxSize / img.height);
                            img.scale(scale);
                            
                            // Center image on canvas
                            img.set({
                                left: (canvas.width - img.width * scale) / 2,
                                top: (canvas.height - img.height * scale) / 2
                            });
                            
                            canvas.add(img);
                            canvas.renderAll();
                            
                            // Close modal
                            $('#dalleModal').modal('hide');
                            
                            // Show success message
                            showNotification('AI image added to your design!', 'success');
                        });
                    } else {
                        $status.html('<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ' + response.message + '</div>');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Error generating image. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    $status.html('<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ' + errorMsg + '</div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bi bi-magic"></i> Generate Image');
                }
            });
        });
    });
    </script>
    @endpush
</x-app-layout> 