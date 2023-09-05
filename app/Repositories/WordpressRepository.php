<?php

namespace App\Repositories;

use App\Model\Post;
use App\Model\Settings;
use CURLFile;
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

    public function remote_post($slug, $title, $content, $status, $post_id = null, $media_id = null) {

        // the standard end point for posts in an initialised Curl
        $process = curl_init('https://'.$this->url.'/wp-json/wp/v2/posts/'.$post_id);

        // create an array of data to use, this is basic - see other examples for more complex inserts
        $data = array(
            'slug' => $slug ,
            'title' => $title ,
            'content' => $content,
            'status' => ($status == 1)? 'publish' : 'draft',
            'featured_media' => ($media_id != null)? $media_id : null
            );
        $data_string = json_encode($data);

        // create the options starting with basic authentication
            curl_setopt($process, CURLOPT_USERPWD, $this->user . ":" . $this->password);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            curl_setopt($process, CURLOPT_POST, 1);
        // make sure we are POSTing
        curl_setopt($process, CURLOPT_CUSTOMREQUEST,  ($post_id != null)? "PUT" : "POST");
        // this is the data to insert to create the post
        curl_setopt($process, CURLOPT_POSTFIELDS, $data_string);
        // allow us to use the returned data from the request
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        // we are sending json
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        // process the request
        $return = curl_exec($process);
        curl_close($process);

        // This buit is to show you on the screen what the data looks like returned and then decoded for PHP use
        return $return;
    }

    public function should_post($post){
        $wp_push_is_enabled = Settings::firstWhere('setting', 'Push to WordPress')->options['active'];

        if ($wp_push_is_enabled == 1 and auth()->user()->can('push to wordpress')){
            $repository = new WordpressRepository();
            $media_id = null;
            if (count($post->getMedia('header'))>0){
                $return = $this->push_image($post, $post->getMedia('header')->first());
                dd(son_decode($return));
                $media_id = json_decode($return)?->id;
            }

            $wp_call = $repository->remote_post(Str::slug($post->header), $post->header, $post->news, $post->released, $post->published_wp_id, $media_id);
            $return = json_decode($wp_call);
            $post->update([
                'published_wp_id' => $return->id
            ]);


        }
    }

    public function push_image(Post $post, Media $image){
        if (Str::contains($image->mime_type, 'image')) {

            $url = 'https://'.$this->url.'/wp-json/wp/v2/media/';

            $ch = curl_init($url);


            curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->password);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, [
                'file' => new CURLFILE($image->getPath()),
                'post' => $post->published_wp_id ,
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                'Content-Disposition: form-data; filename='.$image->file_name,
            ] );
            $result = curl_exec( $ch );
            curl_close( $ch );
            return $result;
        }
    }

}
