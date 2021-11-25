<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePollRequest;
use App\Http\Requests\UpdatePollRequest;
use App\Model\Poll;
use App\Model\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PollController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return redirect() - back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return redirect()->back();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePollRequest $request
     * @return Response
     */
    public function store(Post $post, StorePollRequest $request)
    {
        $poll = new Poll($request->validated());
        $poll->author_id = auth()->id();
        $poll->post_id = $post->id;
        $poll->save();

        $options = [];
        foreach ($request->options as $option) {
            $options[] = [
                'option' => $option,
                'poll_id' => $poll->id
            ];
        }
        $poll->options()->insert($options);

        return redirect(url('/'));

    }

    /**
     *
     *
     *
     */
    public function vote(Post $post, Request $request)
    {
        $poll = $post->poll;
        $string = $poll->id . '_answers';
        $answers = $request->$string;

        if ($poll->votes->where('author_id', auth()->id())->first() != null) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Stimme wurde bereits abgegeben.',
            ]);
        }

        if (count($answers) > $poll->max_number) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Zuviele Antworten ausgewählt.',
            ]);
        }

        $poll->votes()->insert([
            'poll_id' => $poll->id,
            'author_id' => auth()->id(),
        ]);

        $query = [];
        foreach ($answers as $answer) {
            $query[] = [
                'poll_id' => $poll->id,
                'option_id' => $answer
            ];
        }

        $poll->answers()->insert($query);


        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Stimme wurde abgegeben.',
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePollRequest $request
     * @param \App\Poll $poll
     * @return Response
     */
    public function update(UpdatePollRequest $request, Poll $poll)
    {
        $poll->update($request->validated());


        $options = [];
        foreach ($request->options as $option) {
            if ($option != "") {
                $options[] = [
                    'option' => $option,
                    'poll_id' => $poll->id
                ];
            }
        }

        if (count($options) > 1) {
            $poll->options()->delete();
            $poll->options()->insert($options);
        }


        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Umfrage geändert'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Poll $poll
     * @return Response
     */
    public function destroy(Poll $poll)
    {
        //
    }
}
