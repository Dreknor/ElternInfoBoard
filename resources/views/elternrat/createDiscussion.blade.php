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
                    <i class="fas fa-pen text-indigo-600"></i>
                    Neue Mitteilung verfassen
                </h1>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="max-w-4xl">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 border-b border-indigo-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-edit"></i>
                        Beitrag erstellen
                    </h5>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="p-6 bg-red-50 border-b border-red-200">
                        <div class="bg-white border-l-4 border-red-500 rounded-lg p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl mt-0.5"></i>
                                <div class="flex-1">
                                    <h6 class="font-semibold text-red-800 mb-2">Bitte beheben Sie folgende Fehler:</h6>
                                    <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Card Body -->
                <div class="p-6">
                    <form action="{{url('/elternrat/discussion')}}"
                          method="post"
                          class="space-y-6"
                          enctype="multipart/form-data"
                          id="nachrichtenForm">
                        @csrf

                        <!-- Header Field -->
                        <div>
                            <label for="header" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-heading text-indigo-600"></i>
                                Überschrift
                            </label>
                            <input type="text"
                                   id="header"
                                   name="header"
                                   value="{{old('header')}}"
                                   placeholder="z.B. Einladung zur nächsten Sitzung"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <!-- Text Field -->
                        <div>
                            <label for="text" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-align-left text-indigo-600"></i>
                                Nachrichtentext
                            </label>
                            <textarea name="text"
                                      id="text"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">{{old('text')}}</textarea>
                        </div>

                        <!-- Sticky Field -->
                        <div>
                            <label for="sticky" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-thumbtack text-indigo-600"></i>
                                Nachricht oben anheften
                            </label>
                            <select name="sticky"
                                    id="sticky"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white">
                                <option value="0" selected>Nicht anheften</option>
                                <option value="1">Oben anheften</option>
                            </select>
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle"></i>
                                Angeheftete Beiträge werden immer oben angezeigt
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit"
                                    id="submitBtn"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Beitrag veröffentlichen</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>
        tinymce.init({
            selector: '#text',
            lang: 'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link charmap',
                'searchreplace visualblocks code',
                'insertdatetime table paste code wordcount',
                'contextmenu',
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
            contextmenu: "link image inserttable | cell row column deletetable",
            skin: 'oxide',
            content_css: 'default'
        });
    </script>
@endpush
