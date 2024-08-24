<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSitesBlockRequest;
use App\Http\Requests\UpdateSiteBlockImageRequest;
use App\Http\Requests\UpdateSiteBlockTextRequest;
use App\Model\Site;
use App\Model\SiteBlock;
use App\Model\SiteBlockFiles;
use App\Model\SiteBlockImages;
use App\Model\SiteBlockText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteBlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create sites');
    }

    public function store(CreateSitesBlockRequest $request)
    {
        Cache::delete('site' . $request->site_id);
        $site = Site::findOrFail($request->site_id);

        $lastBlock = $site->blocks()->orderBy('position')->first();


            $position = $site->blocks()->count() + 1;



        switch ($request->block_type) {
            case 'image':
                $block = new SiteBlockImages();
                $block->save();

                break;
            case 'files':
                $block = new SiteBlockFiles();
                $block->save();

                break;
            case 'text':
                $block = new SiteBlockText();
                $block->save();
                break;

            default:
                return redirect()->back()->with('error', 'Block Typ '. $request->block_type .' nicht gefunden');
        }

        $newBlock = new SiteBlock([
            'site_id' => $request->site_id,
            'position' => $position,
            'block_id' => $block->id,
            'block_type' => get_class($block),
            'title' => $request->title
        ]);

        $newBlock->save();

        return redirect()->route('sites.edit', $site->id)->with('success', 'Block wurde erstellt');
    }

    public function destroy(SiteBlock $block): \Illuminate\Http\RedirectResponse
    {
        Cache::delete('site' . $block->site_id);

        $block->block?->media()->each(function ($media) {
            $media->delete();
        });
        $block->block->delete();
        $block->delete();


        return redirect()->back()->with('success', 'Block wurde gelöscht');
    }

    public function update(UpdateSiteBlockTextRequest $request, SiteBlock $block): \Illuminate\Http\RedirectResponse
    {
        Cache::delete('site' . $block->site_id);

        $block->update($request->validated());

        $block->block->update($request->validated());

        return redirect()->back()->with('success', 'Block wurde aktualisiert');
    }

    public function storeImage(UpdateSiteBlockImageRequest $request, SiteBlock $block): \Illuminate\Http\RedirectResponse
    {
        Cache::delete('site' . $block->site_id);

        $block->update($request->validated());
        $block->block->addAllMediaFromRequest()->each(function ($fileAdder) {
            $fileAdder->toMediaCollection();
        });

        return redirect()->back()->with('success', 'Bilder wurde hinzugefügt');
    }

    public function storeFile(UpdateSiteBlockImageRequest $request, SiteBlock $block): \Illuminate\Http\RedirectResponse
    {
        Cache::delete('site' . $block->site_id);

        $block->update($request->validated());
        $block->block->addAllMediaFromRequest()->each(function ($fileAdder) {
            $fileAdder->toMediaCollection();
        });

        return redirect()->back()->with('success', 'Bilder wurde hinzugefügt');
    }

    #TODO: Add this method to the SiteBlockController
    public function blockPostionUp(SiteBlock $block)
    {
        Cache::delete('site' . $block->site_id);

        $site = $block->site;
        $block->position = $block->position - 1;
        $block->save();

        $block->site->blocks()->where('position', $block->position)->where('id', '!=', $block->id)->update(['position' => $block->position + 1]);

        return redirect()->route('sites.edit', $site->id);
    }
    public function blockPostionDown(SiteBlock $block)
    {
        Cache::delete('site' . $block->site_id);

        $site = $block->site;
        $block->position = $block->position + 1;
        $block->save();

        $block->site->blocks()->where('position', $block->position)->where('id', '!=', $block->id)->update(['position' => $block->position - 1]);

        return redirect()->route('sites.edit', $site->id);
    }

    public function removeMedia(SiteBlock $block, $mediaId)
    {
        Cache::delete('site' . $block->site_id);

        $block->block->media()->where('id', $mediaId)->each(function ($media) {
            $media->delete();
        });

        return redirect()->back()->with('success', 'Bild wurde gelöscht');
    }

}
