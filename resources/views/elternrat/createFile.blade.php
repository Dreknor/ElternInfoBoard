@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <a href="{{url('elternrat')}}"
                   class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Zurück</span>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-file-upload text-blue-600"></i>
                    Datei hinzufügen
                </h1>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 border-b border-blue-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Neue Datei hochladen
                    </h5>
                </div>

                <!-- Card Body -->
                <div class="p-6">
                    <form action="{{url('elternrat/file')}}"
                          method="post"
                          class="space-y-6"
                          enctype="multipart/form-data">
                        @csrf

                        <!-- Directory Selection -->
                        <div>
                            <label for="directory" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-folder text-blue-600"></i>
                                Verzeichnis auswählen
                            </label>
                            <select name="directory"
                                    id="directory"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                @foreach(config('app.directories_elternrat') as $directory)
                                    <option value="{{$directory}}">{{$directory}}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle"></i>
                                Wählen Sie das Verzeichnis, in dem die Datei gespeichert werden soll
                            </p>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label for="customFile" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-file text-blue-600"></i>
                                Datei auswählen
                            </label>
                            <div class="relative">
                                <input type="file"
                                       name="files"
                                       id="customFile"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle"></i>
                                Erlaubte Dateitypen: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG
                            </p>
                        </div>

                        <!-- File Preview Area -->
                        <div id="filePreview" class="hidden">
                            <div class="p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-file-alt text-blue-600 text-xl mt-1"></i>
                                    <div class="flex-1">
                                        <p class="font-semibold text-blue-800">Ausgewählte Datei:</p>
                                        <p id="fileName" class="text-sm text-blue-700"></p>
                                        <p id="fileSize" class="text-xs text-blue-600 mt-1"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-blue-700 hover:to-cyan-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-upload"></i>
                                <span>Datei hochladen</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // File input change handler for preview
            $('#customFile').on('change', function(e) {
                const file = e.target.files[0];

                if (file) {
                    // Show preview area
                    $('#filePreview').removeClass('hidden');

                    // Display file name
                    $('#fileName').text(file.name);

                    // Display file size
                    const fileSize = (file.size / 1024).toFixed(2);
                    const sizeUnit = fileSize > 1024 ? ((fileSize / 1024).toFixed(2) + ' MB') : (fileSize + ' KB');
                    $('#fileSize').text('Größe: ' + sizeUnit);
                } else {
                    // Hide preview if no file selected
                    $('#filePreview').addClass('hidden');
                }
            });
        });
    </script>

@endpush
