<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFilesRequest;
use App\Mail\newFilesAddToPost;
use App\Model\Group;
use App\Model\Post;
use App\Repositories\GroupsRepository;
use App\Services\App\FeedbackService;
use App\Support\Collection;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache as CacheAlias;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FileController extends Controller implements HasMiddleware
{
    private GroupsRepository $grousRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {

        $this->grousRepository = $groupsRepository;
    }

    public static function middleware(): array
    {
        return [
            'password_expired',
        ];
    }

    /**
     * @return JsonResponse
     */
    public function delete(Media $file)
    {
        $this->authorizeFileAccess($file);

        $file->delete();

        return response()->json([
            'message' => 'Gelöscht',
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function destroy(Media $file)
    {
        $this->authorizeFileAccess($file);

        $file->delete();

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Datei gelöscht',
        ]);
    }

    /**
     * @return View
     */
    public function index()
    {
        $user = auth()->user();
        $user->load('groups');

        if ($user->can('upload files')) {
            if (! $user->can('view protected')) {
                $gruppen = Group::active()->where('protected', 0)->get();
            } else {
                $gruppen = Group::active()->get();
            }

            return view('files.indexVerwaltung', [
                'gruppen' => $gruppen->load('media'),
            ]);
        } else {
            $gruppen = $user->groups()->where('active', true)->with('media')->get();
            $media = new Collection;

            foreach ($gruppen as $gruppe) {
                $gruppenMedien = $gruppe->getMedia();
                foreach ($gruppenMedien as $medium) {
                    $media->push($medium);
                }
            }

            $media = $media->unique('name')->all();

            return view('files.index', [
                'gruppen' => $gruppen,
                'medien' => $media,
            ]);
        }
    }

    /**
     * @return View
     */
    public function create()
    {
        return view('files.create', [
            'groups' => Group::active()->get(),
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        if (! $request->user()->can('upload files')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        if ($request->hasFile('files')) {
            foreach ($gruppen as $gruppe) {
                $gruppe->addMediaFromRequest('files')
                    ->preservingOriginal()
                    ->toMediaCollection();
            }

        }

        return redirect()->to('/files')->with([
            'type' => 'success',
            'Meldung' => 'Download erzeugt',
        ]);
    }

    /**
     * @return RedirectResponse
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function saveFileRueckmeldung(Request $request, Post $posts, FeedbackService $feedback)
    {
        // Nur Empfänger eines Beitrags mit offener Bild-Rückmeldung, nur Bilder (wie die App-API).
        // Vorher konnte jedes Konto beliebige Dateien (auch HTML/SVG) an jeden Beitrag hängen.
        try {
            $feedback->requireFeedback($request->user(), $posts, 'bild');
        } catch (HttpException $e) {
            return redirect()->back()->with(['type' => 'warning', 'Meldung' => $e->getMessage()]);
        }
        $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'files' => ['required', 'image', 'max:10240'],
        ]);

        if ($request->hasFile('files')) {
            $posts->addMediaFromRequest('files')
                ->usingName((string) $request->input('name', $request->user()->name))
                ->withCustomProperties(['uploaded_by' => $request->user()->id])
                ->toMediaCollection('images');

            @Mail::to($posts->autor->email)->queue(new newFilesAddToPost($request->user()->name, $posts->header, $posts->id));
        } else {
            return redirect()->to(url('home/'))->with([
                'type' => 'warning',
                'Meldung' => 'Upload fehlgeschlagen',
            ]);
        }

        return redirect(url('post/'.$posts->id))->with([
            'type' => 'success',
            'Meldung' => 'Bild erfolgreich hinzugefügt',
        ]);
    }

    /**
     * Verwaiste Medienordner suchen: Ordner `{id}/` auf einer Medien-Disk, zu denen es keinen
     * Media-Eintrag (mehr) auf dieser Disk gibt. Andere Ordner (z. B. elternabfragen/, img/)
     * werden nicht angefasst.
     *
     * @return array<string, array<int, string>> „disk:id“ => Dateien
     */
    protected function scanDir()
    {
        $disks = Media::query()->distinct()->pluck('disk')
            ->merge(Media::query()->distinct()->pluck('conversions_disk'))
            ->push(config('media-library.disk_name'))
            ->filter(fn ($disk) => filled($disk) && config("filesystems.disks.$disk") !== null)
            ->unique();

        $noMedia = [];
        foreach ($disks as $disk) {
            $storage = Storage::disk($disk);
            $dirs = collect($storage->directories())->filter(fn ($dir) => ctype_digit($dir));
            if ($dirs->isEmpty()) {
                continue;
            }

            $known = Media::query()->whereIn('id', $dirs->all())
                ->where(fn ($q) => $q->where('disk', $disk)->orWhere('conversions_disk', $disk))
                ->pluck('id')->map(fn ($id) => (string) $id)->all();

            foreach ($dirs->diff($known) as $dir) {
                $noMedia[$disk.':'.$dir] = $storage->allFiles($dir);
            }
        }

        return $noMedia;
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function showScan()
    {
        $noMedia = CacheAlias::remember('old_files', 1, function () {
            return $this->scanDir();
        });

        $oldMedia = Media::where('created_at', '<', Carbon::now()->subMonths(6))->where('model_type', Post::class)->get();

        $deletedPosts = Post::onlyTrashed()->get();

        return view('settings.scanDir', [
            'media' => $noMedia,
            'oldMedia' => $oldMedia,
            'deletedPosts' => $deletedPosts,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function deleteUnusedFiles()
    {
        foreach (array_keys($this->scanDir()) as $key) {
            [$disk, $dir] = explode(':', $key, 2);
            Storage::disk($disk)->deleteDirectory($dir);
        }

        return redirect()->back();
    }

    /**
     * @return RedirectResponse
     */
    public function removeOldFiles(DeleteFilesRequest $request)
    {
        $media = Media::where('model_type', Post::class)->whereDate('created_at', '<', $request->deleteBeforeDate)->get();

        foreach ($media as $Media) {
            $Media->delete();
        }

        return redirect()->back();
    }

    /**
     * Prüft ob der eingeloggte User die angegebene Mediendatei löschen darf.
     * Admins (upload files) dürfen alle Dateien löschen.
     */
    private function authorizeFileAccess(Media $file): void
    {
        $user = auth()->user();

        if ($user->can('upload files')) {
            return; // Admins dürfen alles löschen
        }

        $model = $file->model;

        if ($model instanceof Post) {
            // Datei gehört einem Post – User muss den Post erstellt haben
            if ($model->author === $user->id) {
                return;
            }
        }

        abort(403, 'Keine Berechtigung zum Löschen dieser Datei.');
    }
}
