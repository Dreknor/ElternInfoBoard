<?php

namespace App\Repositories;

use App\Model\Settings;
use Illuminate\Support\Str;

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

    public function remote_post($slug, $title, $content, $status, $post_id = null) {

        // the standard end point for posts in an initialised Curl
        $process = curl_init('https://'.$this->url.'/wp-json/wp/v2/posts/'.$post_id);

        // create an array of data to use, this is basic - see other examples for more complex inserts
        $data = array(
            'slug' => $slug ,
            'title' => $title ,
            'content' => $content,
            'status' => ($status == 1)? 'publish' : 'draft',
            );
        $data_string = json_encode($data);

        // create the options starting with basic authentication
            curl_setopt($process, CURLOPT_USERPWD, $this->user . ":" . $this->password);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            curl_setopt($process, CURLOPT_POST, 1);
        // make sure we are POSTing
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, "POST");
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
            $wp_call = $repository->remote_post(Str::slug($post->header), $post->header, $post->news, $post->released);
            $return = json_decode($wp_call);
            $post->update([
                'published_wp_id' => $return->id
            ]);
        }
    }

}
