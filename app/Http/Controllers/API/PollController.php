<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Poll;
use App\Model\Post;
use Illuminate\Http\Request;

/**
 * Class PollController
 *
 * Controller for handling poll related API requests.
 */
class PollController extends Controller
{
    /**
     * Get poll details with options and results
     *
     * This method returns poll details including options and voting results.
     *
     * @group Polls
     *
     * @urlParam post required The ID of the post. Example: 1
     *
     * @responseField poll object Poll details including options and results.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Post $post)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Check if user has access to this post
        if (!$post->users->contains($user) && !$user->can('view all')) {
            return response()->json(['error' => 'User not allowed to view this poll'], 403);
        }

        $poll = $post->poll;

        if (!$poll) {
            return response()->json(['error' => 'No poll found for this post'], 404);
        }

        // Check if user has already voted
        $userVote = $poll->votes()->where('author_id', $user->id)->first();
        $hasVoted = !is_null($userVote);

        // Get user's selected options if they voted
        $userAnswers = [];
        if ($hasVoted) {
            $userAnswers = $poll->answers()
                ->where('poll_id', $poll->id)
                ->whereIn('option_id', function ($query) use ($poll, $user) {
                    $query->select('poll_votes.id')
                        ->from('poll_votes')
                        ->where('poll_votes.poll_id', $poll->id)
                        ->where('poll_votes.author_id', $user->id);
                })
                ->pluck('option_id')
                ->toArray();
        }

        // Get options with vote counts
        $options = $poll->options->map(function ($option) use ($poll) {
            $voteCount = $poll->answers()->where('option_id', $option->id)->count();

            return [
                'id' => $option->id,
                'option' => $option->option,
                'votes' => $voteCount,
            ];
        });

        $totalVotes = $poll->votes()->count();

        return response()->json([
            'poll' => [
                'id' => $poll->id,
                'poll_name' => $poll->poll_name,
                'description' => $poll->description,
                'ends' => $poll->ends?->toIso8601String(),
                'max_number' => $poll->max_number,
                'total_votes' => $totalVotes,
                'has_voted' => $hasVoted,
                'user_answers' => $userAnswers,
                'options' => $options,
            ],
        ], 200);
    }

    /**
     * Vote on a poll
     *
     * This method allows a user to vote on a poll by selecting one or more options.
     *
     * @group Polls
     *
     * @urlParam post required The ID of the post. Example: 1
     *
     * @bodyParam option_ids array required Array of option IDs to vote for. Example: [1, 2]
     *
     * @responseField success string The success message.
     * @responseField poll object Updated poll information.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function vote(Request $request, Post $post)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Check if user has access to this post
        if (!$post->users->contains($user) && !$user->can('view all')) {
            return response()->json(['error' => 'User not allowed to vote on this poll'], 403);
        }

        $poll = $post->poll;

        if (!$poll) {
            return response()->json(['error' => 'No poll found for this post'], 404);
        }

        // Validate request
        $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'required|integer|exists:poll__options,id',
        ]);

        $optionIds = $request->option_ids;

        // Check if user has already voted
        if ($poll->votes()->where('author_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'User has already voted on this poll',
            ], 409);
        }

        // Check if number of selected options exceeds maximum
        if (count($optionIds) > $poll->max_number) {
            return response()->json([
                'error' => 'Too many options selected. Maximum allowed: ' . $poll->max_number,
            ], 400);
        }

        // Verify all options belong to this poll
        $validOptions = $poll->options()->whereIn('id', $optionIds)->count();
        if ($validOptions !== count($optionIds)) {
            return response()->json([
                'error' => 'One or more invalid option IDs provided',
            ], 400);
        }

        // Create vote record
        $poll->votes()->create([
            'poll_id' => $poll->id,
            'author_id' => $user->id,
        ]);

        // Create answer records
        $answers = [];
        foreach ($optionIds as $optionId) {
            $answers[] = [
                'poll_id' => $poll->id,
                'option_id' => $optionId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $poll->answers()->insert($answers);

        // Get updated poll data
        $options = $poll->options->map(function ($option) use ($poll) {
            $voteCount = $poll->answers()->where('option_id', $option->id)->count();

            return [
                'id' => $option->id,
                'option' => $option->option,
                'votes' => $voteCount,
            ];
        });

        $totalVotes = $poll->votes()->count();

        return response()->json([
            'success' => 'Vote submitted successfully',
            'poll' => [
                'id' => $poll->id,
                'poll_name' => $poll->poll_name,
                'description' => $poll->description,
                'ends' => $poll->ends?->toIso8601String(),
                'max_number' => $poll->max_number,
                'total_votes' => $totalVotes,
                'has_voted' => true,
                'user_answers' => $optionIds,
                'options' => $options,
            ],
        ], 200);
    }
}

