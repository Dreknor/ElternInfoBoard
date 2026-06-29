<?php

namespace App\Repositories;

use App\Model\Module;
use App\Model\Post;
use CURLFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WordpressRepository
{
    private string $url;

    private string $user;

    private string $password;

    public function __construct()
    {
        $this->url = config('wordpress.wp_url');
        $this->user = config('wordpress.wp_username');
        $this->password = config('wordpress.wp_password');
    }

    public function remote_post($slug, $title, $content, $status, $post_id = null, $media_id = null)
    {

        // the standard end point for posts in an initialised Curl
        $process = curl_init('https://'.$this->url.'/wp-json/wp/v2/posts/'.$post_id);

        // create an array of data to use, this is basic - see other examples for more complex inserts
        $data = [
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
            'status' => ($status == 1) ? 'publish' : 'draft',
            'featured_media' => ($media_id != null) ? $media_id : null,
        ];
        $data_string = json_encode($data);

        // create the options starting with basic authentication
        curl_setopt($process, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_POST, 1);
        // make sure we are POSTing
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, ($post_id != null) ? 'PUT' : 'POST');
        // this is the data to insert to create the post
        curl_setopt($process, CURLOPT_POSTFIELDS, $data_string);
        // allow us to use the returned data from the request
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        // we are sending json
        curl_setopt($process, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($data_string)]
        );

        // process the request
        $return = curl_exec($process);

        // cURL-Fehlerbehandlung: Netzwerk-/Verbindungsfehler abfangen
        if ($return === false) {
            $error = curl_error($process);
            $errno = curl_errno($process);
            curl_close($process);
            Log::error('WordPress-Push fehlgeschlagen (remote_post)', [
                'errno' => $errno,
                'error' => $error,
                'post_id' => $post_id,
            ]);

            return null;
        }

        $httpCode = curl_getinfo($process, CURLINFO_HTTP_CODE);
        curl_close($process);

        if ($httpCode >= 400) {
            Log::error('WordPress-Push: HTTP-Fehlerstatus (remote_post)', [
                'http_code' => $httpCode,
                'post_id' => $post_id,
                'response' => is_string($return) ? Str::limit($return, 500) : null,
            ]);

            return null;
        }

        return $return;
    }

    public function should_post($post)
    {
        $wp_push_is_enabled = Module::firstWhere('setting', 'Push to WordPress')->options['active'];

        if ($wp_push_is_enabled == 1 and auth()->user()->can('push to wordpress')) {
            $this->pushPost($post);
        }
    }

    /**
     * Pusht einen Post zu WordPress
     */
    public function pushPost(Post $post): void
    {
        // Erstelle zunächst den Post ohne Bilder (oder aktualisiere ihn)
        $wp_call = $this->remote_post(Str::slug($post->header), $post->header, $post->news, $post->released, $post->published_wp_id);

        // Abbruch, wenn der Aufruf fehlgeschlagen ist (Fehler bereits geloggt)
        if ($wp_call === null) {
            return;
        }

        $return = json_decode($wp_call);

        // Antwort validieren, bevor auf Eigenschaften zugegriffen wird
        if (! is_object($return)) {
            Log::error('WordPress-Push: Unerwartete Antwort beim Erstellen/Aktualisieren des Posts', [
                'post_id' => $post->id,
                'response' => is_string($wp_call) ? Str::limit($wp_call, 500) : null,
            ]);

            return;
        }

        // Nur die ID setzen, wenn es ein neuer Post ist
        if ($post->published_wp_id == null) {
            if (! isset($return->id)) {
                Log::error('WordPress-Push: Keine Post-ID in der Antwort erhalten', [
                    'post_id' => $post->id,
                ]);

                return;
            }

            $post->update([
                'published_wp_id' => $return->id,
            ]);
        }

        $media_id = null;
        $firstImageUsedAsHeader = false;

        // Header-Bild hochladen
        if (count($post->getMedia('header')) > 0) {
            $result = $this->push_image($post, $post->getMedia('header')->first());
            if ($result) {
                $media_id = json_decode($result)->id;
            }
        } else {
            // Wenn kein Header-Bild vorhanden ist, verwende das erste angehängte Bild
            $allImages = $post->getMedia('images');
            if ($allImages->isEmpty()) {
                $allImages = $post->getMedia('files')->filter(function ($file) {
                    return Str::contains($file->mime_type, 'image');
                });
            }

            if ($allImages->isNotEmpty()) {
                $result = $this->push_image($post, $allImages->first());
                if ($result) {
                    $media_id = json_decode($result)->id;
                    $firstImageUsedAsHeader = true;
                }
            }
        }

        // Alle Bilder aus der 'images' Collection hochladen und in den Content einbinden
        $content = $this->embedImagesInContent($post, $firstImageUsedAsHeader);

        // Post mit Bildern aktualisieren
        $wp_call = $this->remote_post(Str::slug($post->header), $post->header, $content, $post->released, $post->published_wp_id, $media_id);
    }

    public function push_image(Post $post, Media $image)
    {
        if ($post->published_wp_id != null and Str::contains($image->mime_type, 'image')) {

            $url = 'https://'.$this->url.'/wp-json/wp/v2/media/';
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new CURLFILE($image->getPath()),
                'post' => $post->published_wp_id,
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Disposition: form-data; filename='.$image->file_name,
            ]);
            $result = curl_exec($ch);

            if ($result === false) {
                Log::error('WordPress-Push: Bild-Upload fehlgeschlagen', [
                    'errno' => curl_errno($ch),
                    'error' => curl_error($ch),
                    'post_id' => $post->published_wp_id,
                    'media' => $image->file_name,
                ]);
                curl_close($ch);

                return null;
            }

            curl_close($ch);

            return $result;
        }

        return null;
    }

    /**
     * Lädt alle Bilder eines Posts zu WordPress hoch und bindet sie in den Content ein
     */
    private function embedImagesInContent(Post $post, bool $skipFirstImage = false): string
    {
        $content = $post->news;

        // Alle Bilder aus der 'images' Collection hochladen
        $images = $post->getMedia('images');

        // Auch Bilder aus der 'files' Collection berücksichtigen (falls sie Bilder sind)
        $files = $post->getMedia('files')->filter(function ($file) {
            return Str::contains($file->mime_type, 'image');
        });

        // Kombiniere beide Collections
        $allImages = $images->merge($files);

        // Wenn das erste Bild als Header verwendet wurde, überspringe es
        if ($skipFirstImage && $allImages->isNotEmpty()) {
            $allImages = $allImages->slice(1);
        }

        if (count($allImages) > 0) {
            $uploadedImages = [];

            foreach ($allImages as $image) {
                if ($post->published_wp_id != null) {
                    $result = $this->push_image($post, $image);
                    if ($result) {
                        $imageData = json_decode($result);
                        if (isset($imageData->source_url)) {
                            $uploadedImages[] = [
                                'url' => $imageData->source_url,
                                'alt' => $image->name ?? '',
                                'caption' => $image->custom_properties['caption'] ?? '',
                            ];
                        }
                    }
                }
            }

            // Bilder in den Content einfügen
            if (count($uploadedImages) > 0) {
                $imageHtml = "\n\n<!-- wp:gallery -->\n<figure class=\"wp-block-gallery\">\n";

                foreach ($uploadedImages as $imgData) {
                    $caption = ! empty($imgData['caption']) ? '<figcaption>'.htmlspecialchars($imgData['caption']).'</figcaption>' : '';
                    $imageHtml .= sprintf(
                        '<figure class="wp-block-image"><img src="%s" alt="%s" />%s</figure>'."\n",
                        htmlspecialchars($imgData['url']),
                        htmlspecialchars($imgData['alt']),
                        $caption
                    );
                }

                $imageHtml .= "</figure>\n<!-- /wp:gallery -->\n";

                // Bilder am Ende des Contents hinzufügen
                $content .= $imageHtml;
            }
        }

        return $content;
    }
}
