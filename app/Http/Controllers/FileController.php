<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteFilesRequest;
use App\Mail\newFilesAddToPost;
use App\Model\Group;
use App\Model\Post;
use App\Repositories\GroupsRepository;
use App\Support\Collection;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache  as CacheAlias;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileController extends Controller
{
    private GroupsRepository $grousRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->middleware('password_expired');
        $this->grousRepository = $groupsRepository;
    }

    /**
     * @param Media $file
     * @return JsonResponse
     */
    public function delete(Media $file)
    {
        $file->delete();

        return response()->json([
            'message' => 'Gelöscht',
        ]);
    }

    /**
     * @param Media $file
     * @return RedirectResponse
     */
    public function destroy(Media $file)
    {
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
                $gruppen = Group::where('protected', 0)->get();
            } else {
                $gruppen = Group::all();
            }

            return view('files.indexVerwaltung', [
                'gruppen' => $gruppen->load('media'),
            ]);
        } else {
            $gruppen = $user->groups()->with('media')->get();
            $media = new Collection();

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
            'groups' => Group::all(),
        ]);
    }

    /**
     * @param Request $request
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
     * @param Request $request
     * @param Post $posts
     * @return RedirectResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function saveFileRueckmeldung(Request $request, Post $posts)
    {
        if ($request->hasFile('files')) {
            $posts->addAllMediaFromRequest()
                ->each(fn($fileAdder) => $fileAdder
                    ->usingName($request->name)
                    ->toMediaCollection('images'));

            @Mail::to($posts->autor->email)->queue(new newFilesAddToPost($request->user()->name, $posts->header));
        } else {
            return redirect()->to(url('home/'))->with([
                'type' => 'warning',
                'Meldung' => 'Upload fehlgeschlagen',
            ]);
        }

        return redirect(url('home/#'.$posts->id))->with([
            'type' => 'success',
            'Meldung' => 'Bild erfolgreich hinzugefügt',
        ]);
    }

    /**
     * @return array
     */
    protected function scanDir()
    {
        $dirs = scandir(storage_path().'/app');

        $noMedia = [];
        $Media = Media::all();

        foreach ($dirs as $dir) {
            if ($dir == '.' or $dir == '..' or $dir == '.gitignore' or $dir == 'public') {
                continue;
            }

            $MediaModel = $Media->filter(fn($item) => $item->id == $dir)->first();

            if ($MediaModel == null) {
                $scan = scandir(storage_path().'/app/'.$dir);
                foreach ($scan as $key => $item) {
                    if ($item == '.' or $item == '..' or $item == '.gitignore' or $item == 'public') {
                        unset($scan[$key]);
                    }
                }
                $noMedia[$dir] = $scan;
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
        $noMedia = $this->scanDir();
        foreach ($noMedia as $id => $mediaDir) {
            foreach ($mediaDir as $media) {
                $link = storage_path().'/app/'.$id.'/'.$media;
                unlink($link);
            }
            $link = storage_path().'/app/'.$id;
            rmdir($link);
        }

        return redirect()->back();
    }

    /**
     * @param DeleteFilesRequest $request
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
}
